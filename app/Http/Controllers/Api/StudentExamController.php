<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedApp;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamViolation;
use App\Models\Student;
use App\Services\ExamScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {

        $student = $request->user();
        $now = now();

        $exams = Exam::with(['subject:id,code,name', 'questionBank:id,target_classes'])
            ->whereIn('status', ['scheduled', 'active', 'finished'])
            ->where(function ($q) use ($now, $student) {
                $q->where('end_at', '>=', $now->copy()->startOfDay())
                    ->orWhereHas('attempts', fn ($aq) => $aq->where('student_id', $student->id));
            })
            ->orderByDesc('start_at')
            ->get()
            ->filter(fn (Exam $exam) => $exam->isAvailableForClass($student->kelas))
            ->values();

        $attempts = ExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        $data = $exams->map(function (Exam $exam) use ($attempts, $now) {

            $attempt = $attempts->get($exam->id);

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject?->name,
                'execution_mode' => $exam->execution_mode,
                'session_name' => $exam->session_name,
                'start_at' => $exam->start_at->toIso8601String(),
                'end_at' => $exam->end_at->toIso8601String(),
                'duration_minutes' => $exam->duration_minutes,
                'require_token' => $exam->require_token,
                'violation_mode' => $exam->violation_mode,
                'max_violations' => $exam->max_violations,
                'is_open' => $exam->effectiveStatus() === 'ongoing',
                'attempt' => $attempt ? [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'receipt_code' => $attempt->receipt_code,
                    'final_score' => ($exam->scores_released || $exam->show_score) && $attempt->grading_status === 'final' ? (float) $attempt->final_score : null,
                ] : null,
            ];
        });

        $blacklistedApps = BlacklistedApp::where('is_active', true)
            ->orderBy('app_name')
            ->get(['app_name', 'package_name']);

        return response()->json([
            'server_time_ms' => $now->getTimestampMs(),
            'exams' => $data,
            'blacklisted_apps' => $blacklistedApps,
        ]);
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {

        $student = $request->user();

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'token' => ['nullable', 'string', 'max:12'],
        ]);

        if (! $exam->isAvailableForClass($student->kelas)) {
            return response()->json(['message' => 'Ujian ini bukan untuk kelas Anda.'], 403);
        }

        $now = now();
        if (! in_array($exam->status, ['scheduled', 'active'], true) || $now->lt($exam->start_at) || $now->gt($exam->end_at)) {
            return response()->json(['message' => 'Ujian belum dimulai atau sudah berakhir.'], 403);
        }

        if ($exam->require_token) {
            $exam->refreshTokenIfNeeded();
            $inputToken = strtoupper(trim((string) ($validated['token'] ?? '')));
            if ($inputToken === '' || ! hash_equals((string) $exam->token, $inputToken)) {
                return response()->json(['message' => 'Token ujian tidak valid.'], 422);
            }
        }

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->first();

        if ($attempt) {
            if (in_array($attempt->status, ['submitted', 'auto_submitted'], true)) {
                return response()->json(['message' => 'Anda sudah menyelesaikan ujian ini.'], 409);
            }
            if ($attempt->status === 'locked') {
                return response()->json(['message' => 'Sesi Anda terkunci karena pelanggaran. Hubungi proktor.'], 423);
            }
            if ($attempt->device_id && ! hash_equals($attempt->device_id, $validated['device_id'])) {
                return response()->json(['message' => 'Akun sedang aktif di perangkat lain. Minta proktor untuk Reset Login.'], 403);
            }
            if (! $attempt->device_id) {
                $attempt->update(['device_id' => $validated['device_id']]);
            }
        } else {
            $questions = $this->getCachedBankQuestions($exam->question_bank_id);
            $questionIds = $questions->pluck('id')->all();

            if ($exam->shuffle_questions) {
                shuffle($questionIds);
            }

            $optionOrders = [];
            if ($exam->shuffle_options) {
                foreach ($questions as $q) {
                    if (in_array($q->type, ['pg', 'pg_kompleks'], true) && is_array($q->options)) {
                        $keys = collect($q->options)->pluck('key')->all();
                        shuffle($keys);
                        $optionOrders[$q->id] = $keys;
                    }
                }
            }

            $mustEndAt = $now->copy()->addMinutes($exam->duration_minutes);
            if ($mustEndAt->gt($exam->end_at)) {
                $mustEndAt = $exam->end_at->copy();
            }

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'device_id' => $validated['device_id'],
                'question_order' => $questionIds,
                'option_orders' => $optionOrders,
                'status' => 'in_progress',
                'started_at' => $now,
                'must_end_at' => $mustEndAt,
            ]);
        }

        $questions = $this->getCachedBankQuestions($exam->question_bank_id)->keyBy('id');
        $orderedIds = $attempt->question_order ?? $questions->keys()->all();
        $optionOrders = $attempt->option_orders ?? [];

        $questionPayload = collect($orderedIds)
            ->map(function ($qid) use ($questions, $optionOrders) {
                $q = $questions->get($qid);
                return $q ? $q->toStudentPayload($optionOrders[$qid] ?? null) : null;
            })
            ->filter()
            ->values();

        $savedAnswers = $attempt->answers()
            ->get(['question_id', 'answer_data', 'is_doubtful', 'client_updated_at'])
            ->map(fn ($ans) => [
                'question_id' => (int) $ans->question_id,
                'answer_data' => is_array($ans->answer_data) && count($ans->answer_data) > 0 ? $ans->answer_data : null,
                'is_doubtful' => (bool) $ans->is_doubtful,
                'client_updated_at' => (int) ($ans->client_updated_at ?? 0),
            ])
            ->values();

        return response()->json([
            'server_time_ms' => $now->getTimestampMs(),
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'started_at_ms' => $attempt->started_at->getTimestampMs(),
                'must_end_at_ms' => $attempt->must_end_at->getTimestampMs(),
                'remaining_seconds' => (int) max(0, floor($now->diffInSeconds($attempt->must_end_at, false))),
                'min_submit_remaining_minutes' => (int) ($exam->min_submit_remaining_minutes ?? 0),
                'violation_count' => $attempt->violation_count,
                'max_violations' => $exam->max_violations,
                'violation_mode' => $exam->violation_mode,
            ],
            'questions' => $questionPayload,
            'saved_answers' => $savedAnswers,
        ]);
    }

    public function sync(Request $request, ExamAttempt $attempt): JsonResponse
    {
        if ($resp = $this->authorizeAttempt($request, $attempt)) {
            return $resp;
        }

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'answers' => ['present', 'array', 'max:200'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_data' => ['nullable', 'array'],
            'answers.*.is_doubtful' => ['boolean'],
            'answers.*.client_updated_at' => ['required', 'integer', 'min:0'],
            'answers.*.idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $syncedIds = $this->persistAnswersBatch($attempt, $validated['answers']);
        $now = now();

        return response()->json([
            'synced_question_ids' => $syncedIds,
            'server_time_ms' => $now->getTimestampMs(),
            'remaining_seconds' => (int) max(0, floor($now->diffInSeconds($attempt->must_end_at, false))),
            'status' => $attempt->status,
        ]);
    }

    public function violation(Request $request, ExamAttempt $attempt): JsonResponse
    {
        if ($resp = $this->authorizeAttempt($request, $attempt, allowLocked: true)) {
            return $resp;
        }

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'type' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $exam = $attempt->exam;

        DB::transaction(function () use ($attempt, $exam, $validated) {
            ExamViolation::create([
                'attempt_id' => $attempt->id,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'occurred_at' => now(),
            ]);

            $attempt->increment('violation_count');
            $attempt->refresh();

            if ($exam->violation_mode === 'strict' && $attempt->violation_count >= $exam->max_violations) {
                $attempt->update(['status' => 'locked']);
            }
        });

        return response()->json([
            'violation_count' => $attempt->violation_count,
            'max_violations' => $exam->max_violations,
            'status' => $attempt->status,
        ]);
    }

    public function submit(Request $request, ExamAttempt $attempt, ExamScoringService $scoringService): JsonResponse
    {
        if ($resp = $this->authorizeAttempt($request, $attempt, allowLocked: false)) {
            return $resp;
        }

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'is_auto' => ['boolean'],
            'answers' => ['nullable', 'array', 'max:200'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_data' => ['nullable', 'array'],
            'answers.*.is_doubtful' => ['boolean'],
            'answers.*.client_updated_at' => ['required', 'integer', 'min:0'],
            'answers.*.idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $exam = $attempt->exam;
        $minSubmitMinutes = (int) ($exam->min_submit_remaining_minutes ?? 0);
        if (empty($validated['is_auto']) && $minSubmitMinutes > 0 && $attempt->must_end_at) {
            $remainingSec = (int) max(0, floor(now()->diffInSeconds($attempt->must_end_at, false)));
            if ($remainingSec > ($minSubmitMinutes * 60)) {
                return response()->json([
                    'message' => "Tombol kirim ujian baru aktif {$minSubmitMinutes} menit sebelum waktu berakhir.",
                ], 422);
            }
        }

        if (! empty($validated['answers'])) {
            $this->persistAnswersBatch($attempt, $validated['answers']);
        }

        $attempt->update([
            'status' => ! empty($validated['is_auto']) ? 'auto_submitted' : 'submitted',
            'submitted_at' => now(),
            'receipt_code' => $attempt->receipt_code ?? ('EXM-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4))),
        ]);

        $graded = $scoringService->gradeAttempt($attempt);

        return response()->json([
            'message' => 'Ujian berhasil dikumpulkan.',
            'receipt_code' => $graded->receipt_code,
            'submitted_at' => $graded->submitted_at?->toIso8601String(),
            'grading_status' => $graded->grading_status,
            'show_score' => $exam->scores_released || $exam->show_score,
            'objective_score' => ($exam->scores_released || $exam->show_score) ? (float) $graded->objective_score : null,
            'final_score' => ($exam->scores_released || $exam->show_score) && $graded->grading_status === 'final' ? (float) $graded->final_score : null,
        ]);
    }

    private function authorizeAttempt(Request $request, ExamAttempt $attempt, bool $allowLocked = false): ?JsonResponse
    {

        $student = $request->user();

        if ($attempt->student_id !== $student->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $deviceId = (string) $request->input('device_id', '');
        if ($attempt->device_id && $deviceId !== '' && ! hash_equals($attempt->device_id, $deviceId)) {
            return response()->json(['message' => 'Perangkat tidak cocok dengan sesi aktif.'], 403);
        }

        if (in_array($attempt->status, ['submitted', 'auto_submitted'], true)) {
            return response()->json(['message' => 'Ujian sudah diselesaikan.'], 409);
        }

        if (! $allowLocked && $attempt->status === 'locked') {
            return response()->json(['message' => 'Sesi ujian terkunci. Hubungi proktor.'], 423);
        }

        return null;
    }

    private function persistAnswersBatch(ExamAttempt $attempt, array $items): array
    {
        $syncedIds = [];

        DB::transaction(function () use ($attempt, $items, &$syncedIds) {
            $existing = ExamAnswer::where('attempt_id', $attempt->id)
                ->whereIn('question_id', collect($items)->pluck('question_id'))
                ->get()
                ->keyBy('question_id');

            foreach ($items as $item) {
                $qid = (int) $item['question_id'];
                $clientTs = (int) $item['client_updated_at'];
                $current = $existing->get($qid);

                if ($current && $current->client_updated_at > $clientTs) {
                    $syncedIds[] = $qid;
                    continue;
                }

                ExamAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $qid],
                    [
                        'answer_data' => $item['answer_data'] ?? null,
                        'is_doubtful' => (bool) ($item['is_doubtful'] ?? false),
                        'client_updated_at' => $clientTs,
                        'idempotency_key' => $item['idempotency_key'] ?? null,
                    ]
                );

                $syncedIds[] = $qid;
            }

            $attempt->update(['last_sync_at' => now()]);
        });

        return $syncedIds;
    }

    private function getCachedBankQuestions(int $bankId)
    {
        $rows = Cache::remember("question_bank:{$bankId}:questions:v2", 3600, function () use ($bankId) {
            return \App\Models\Question::where('question_bank_id', $bankId)
                ->orderBy('order_no')
                ->orderBy('id')
                ->get()
                ->map(fn ($q) => $q->getAttributes())
                ->all();
        });

        return \App\Models\Question::hydrate($rows);
    }
}

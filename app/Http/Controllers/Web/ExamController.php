<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamScoreArchive;
use App\Models\ExamScoreArchiveItem;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Services\ExamScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $mode = trim((string) $request->query('mode', ''));
        $status = trim((string) $request->query('status', ''));

        $exams = Exam::with(['subject', 'questionBank', 'creator'])
            ->withCount('attempts')
            ->when($user->isGuru(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('created_by', $user->id)
                ->orWhereHas('questionBank', fn ($qb) => $qb->where('teacher_id', $user->id))))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$search}%")
                ->orWhere('session_name', 'like', "%{$search}%")
                ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))))
            ->when(in_array($mode, ['terpusat', 'mandiri'], true), fn ($q) => $q->where('execution_mode', $mode))
            ->when($status !== '', function ($q) use ($status) {
                $now = now();
                if ($status === 'draft') {
                    $q->where('status', 'draft');
                } elseif ($status === 'waiting') {
                    $q->whereNotIn('status', ['draft', 'finished'])
                        ->where('start_at', '>', $now);
                } elseif ($status === 'ongoing') {
                    $q->whereNotIn('status', ['draft', 'finished'])
                        ->where('start_at', '<=', $now)
                        ->where('end_at', '>=', $now);
                } elseif ($status === 'finished') {
                    $q->where(function ($sub) use ($now) {
                        $sub->where('status', 'finished')
                            ->orWhere(fn ($s) => $s->where('status', '!=', 'draft')->where('end_at', '<', $now));
                    });
                }
            })
            ->latest('start_at')
            ->paginate(10)
            ->withQueryString();

        $exams->getCollection()->each->refreshTokenIfNeeded();

        return view('exams.index', compact('exams', 'search', 'mode', 'status'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $banks = $this->availableBanks($user);
        $classes = Student::select('kelas')->distinct()->pluck('kelas')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $exam = null;

        return view('exams.form', compact('exam', 'banks', 'classes'));
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) $exam->created_by !== (int) $user->id) {
            abort(403, 'Anda tidak berhak menghapus jadwal ujian ini.');
        }

        if ($exam->effectiveStatus() === 'ongoing') {
            return back()->with('error', 'Jadwal ujian yang sedang berlangsung tidak dapat dihapus.');
        }

        $title = $exam->title;
        $exam->delete();

        return back()->with('success', "Jadwal ujian {$title} berhasil dihapus.");
    }

    public function edit(Request $request, Exam $exam): View|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeExamAccess($request, $exam);

        if (! $user->isSuperAdmin()) {
            if ((int) $exam->created_by !== (int) $user->id) {
                return redirect()->route('exams.index')->with('toast_error', 'Hanya pembuat jadwal ujian yang dapat mengedit jadwal ini.');
            }

            $status = $exam->effectiveStatus();
            $canEdit = $user->isAdmin()
                ? in_array($status, ['draft', 'waiting', 'finished'], true)
                : ($status === 'finished' && $exam->scores_released);

            if (! $canEdit) {
                $msg = match (true) {
                    $status === 'ongoing' => 'Ujian sedang berlangsung sehingga jadwal tidak dapat diedit.',
                    $user->isGuru() && $status === 'finished' && ! $exam->scores_released => 'Panitia belum merilis nilai ujian ini.',
                    default => 'Ujian belum selesai sehingga jadwal tidak dapat diedit.',
                };

                return redirect()->route('exams.index')->with('toast_error', $msg);
            }
        }

        $banks = $this->availableBanks($user, $exam->id);
        $classes = Student::select('kelas')->distinct()->pluck('kelas')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('exams.form', compact('exam', 'banks', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'execution_mode' => ['required', 'in:mandiri,terpusat'],
            'session_name' => ['nullable', 'string', 'max:64'],
            'target_classes' => ['nullable', 'array'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:360'],
            'min_submit_remaining_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'require_token' => ['boolean'],
            'token_refresh_minutes' => ['nullable', 'integer', 'in:0,15,30,45,60'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'violation_mode' => ['required', 'in:strict,standard'],
            'max_violations' => ['required', 'integer', 'min:1', 'max:20'],
            'show_score' => ['boolean'],
        ]);

        $bank = QuestionBank::findOrFail($validated['question_bank_id']);
        if ($user->isGuru() && $bank->teacher_id !== $user->id) {
            abort(403);
        }

        $mode = $user->isGuru() ? 'mandiri' : $validated['execution_mode'];
        $finalClasses = $this->resolveTargetClasses($bank, $validated['target_classes'] ?? []);

        $now = now();
        $startAt = \Carbon\Carbon::parse($validated['start_at']);
        $endAt = \Carbon\Carbon::parse($validated['end_at']);
        $initialStatus = $now->lt($startAt) ? 'scheduled' : ($now->gt($endAt) ? 'finished' : 'active');

        Exam::create([
            'title' => $validated['title'],
            'question_bank_id' => $bank->id,
            'subject_id' => $bank->subject_id,
            'created_by' => $user->id,
            'execution_mode' => $mode,
            'session_name' => $validated['session_name'] ?? null,
            'target_classes' => $finalClasses,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'min_submit_remaining_minutes' => (int) ($validated['min_submit_remaining_minutes'] ?? 10),
            'token' => Exam::generateToken(),
            'require_token' => $request->boolean('require_token', true),
            'token_refresh_minutes' => ! empty($validated['token_refresh_minutes']) ? (int) $validated['token_refresh_minutes'] : null,
            'token_updated_at' => now(),
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_options' => $request->boolean('shuffle_options', true),
            'violation_mode' => $validated['violation_mode'],
            'max_violations' => $validated['max_violations'],
            'show_score' => $request->boolean('show_score', false),
            'status' => $initialStatus,
        ]);

        return redirect()->route('exams.index')->with('toast_success', 'Jadwal ujian berhasil dibuat dan diaktifkan.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeExamAccess($request, $exam);

        if (! $user->isSuperAdmin()) {
            if ((int) $exam->created_by !== (int) $user->id) {
                return redirect()->route('exams.index')->with('toast_error', 'Hanya pembuat jadwal ujian yang dapat mengubah jadwal ini.');
            }

            $status = $exam->effectiveStatus();
            $canEdit = $user->isAdmin()
                ? in_array($status, ['draft', 'waiting', 'finished'], true)
                : ($status === 'finished' && $exam->scores_released);

            if (! $canEdit) {
                $msg = match (true) {
                    $status === 'ongoing' => 'Ujian sedang berlangsung sehingga jadwal tidak dapat diubah.',
                    $user->isGuru() && $status === 'finished' && ! $exam->scores_released => 'Panitia belum merilis nilai ujian ini.',
                    default => 'Ujian belum selesai sehingga jadwal tidak dapat diubah.',
                };

                return redirect()->route('exams.index')->with('toast_error', $msg);
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'execution_mode' => ['required', 'in:mandiri,terpusat'],
            'session_name' => ['nullable', 'string', 'max:64'],
            'target_classes' => ['nullable', 'array'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:360'],
            'min_submit_remaining_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'require_token' => ['boolean'],
            'token_refresh_minutes' => ['nullable', 'integer', 'in:0,15,30,45,60'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'violation_mode' => ['required', 'in:strict,standard'],
            'max_violations' => ['required', 'integer', 'min:1', 'max:20'],
            'show_score' => ['boolean'],
            'status' => ['required', 'in:draft,scheduled,active,finished'],
        ]);

        $bank = QuestionBank::findOrFail($validated['question_bank_id']);
        if ($user->isGuru() && $bank->teacher_id !== $user->id) {
            abort(403);
        }

        $mode = $user->isGuru() ? 'mandiri' : $validated['execution_mode'];
        $finalClasses = $this->resolveTargetClasses($bank, $validated['target_classes'] ?? []);

        $exam->update([
            'title' => $validated['title'],
            'question_bank_id' => $bank->id,
            'subject_id' => $bank->subject_id,
            'execution_mode' => $mode,
            'session_name' => $validated['session_name'] ?? null,
            'target_classes' => $finalClasses,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'min_submit_remaining_minutes' => (int) ($validated['min_submit_remaining_minutes'] ?? 10),
            'require_token' => $request->boolean('require_token', false),
            'token_refresh_minutes' => ! empty($validated['token_refresh_minutes']) ? (int) $validated['token_refresh_minutes'] : null,
            'shuffle_questions' => $request->boolean('shuffle_questions', false),
            'shuffle_options' => $request->boolean('shuffle_options', false),
            'violation_mode' => $validated['violation_mode'],
            'max_violations' => $validated['max_violations'],
            'show_score' => $request->boolean('show_score', false),
            'status' => $validated['status'],
        ]);

        return redirect()->route('exams.index')->with('toast_success', 'Jadwal ujian berhasil diperbarui.');
    }

    private function availableBanks($user, ?int $exceptExamId = null)
    {
        return QuestionBank::with(['subject', 'teacher:id,name'])
            ->whereDoesntHave('exams', function ($q) use ($exceptExamId) {
                if ($exceptExamId) {
                    $q->where('id', '!=', $exceptExamId);
                }
            })
            ->when($user->isGuru(), fn ($q) => $q->where('teacher_id', $user->id))
            ->when(! $user->isGuru(), fn ($q) => $q->where('is_ready', true))
            ->orderBy('title')
            ->get();
    }

    private function resolveTargetClasses(QuestionBank $bank, array $selectedClasses): array
    {
        $bankClasses = $bank->target_classes ?? [];

        return empty($bankClasses) ? ['ALL'] : $bankClasses;
    }

    public function regenerateToken(Exam $exam): RedirectResponse
    {
        if ($exam->effectiveStatus() === 'finished') {
            return back()->with('toast_error', 'Ujian sudah selesai sehingga token tidak dapat diubah.');
        }

        $exam->update([
            'token' => Exam::generateToken(),
            'token_updated_at' => now(),
        ]);

        return back()->with('toast_success', "Token baru ujian: {$exam->token}");
    }

    public function updateStatus(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:draft,scheduled,active,finished']]);
        $exam->update(['status' => $validated['status']]);

        return back()->with('toast_success', 'Status ujian diperbarui.');
    }

    public function releaseScores(Exam $exam): RedirectResponse
    {
        $exam->update([
            'scores_released' => true,
            'scores_released_at' => now(),
            'show_score' => true,
        ]);

        return back()->with('toast_success', 'Nilai ujian berhasil dirilis.');
    }

    public function monitor(Request $request, Exam $exam): View
    {
        $this->authorizeExamAccess($request, $exam);
        $exam->refreshTokenIfNeeded();
        $exam->load(['subject', 'questionBank']);

        $totalQuestions = $exam->questionBank ? $exam->questionBank->questions()->count() : 0;

        $allActiveClasses = Student::where('is_active', true)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->pluck('kelas');
        $targetClasses = $allActiveClasses
            ->filter(fn (string $c) => $exam->isAvailableForClass($c))
            ->sort(fn (string $a, string $b) => $this->compareClassNames($a, $b))
            ->values();

        $baseAttemptQuery = ExamAttempt::where('exam_id', $exam->id);
        $attemptStudentIds = (clone $baseAttemptQuery)->pluck('student_id');

        $targetStudentsBase = Student::query()->where(function ($q) use ($targetClasses, $attemptStudentIds) {
            $q->where(function ($sq) use ($targetClasses) {
                $sq->where('is_active', true);
                if ($targetClasses->isNotEmpty()) {
                    $sq->whereIn('kelas', $targetClasses);
                } else {
                    $sq->whereRaw('1 = 0');
                }
            });
            if ($attemptStudentIds->isNotEmpty()) {
                $q->orWhereIn('id', $attemptStudentIds);
            }
        });

        $totalTarget = (clone $targetStudentsBase)->count();
        $startedCount = $attemptStudentIds->unique()->count();
        $summary = [
            'total' => $totalTarget,
            'not_started' => max(0, $totalTarget - $startedCount),
            'in_progress' => (clone $baseAttemptQuery)->where('status', 'in_progress')->count(),
            'locked' => (clone $baseAttemptQuery)->where('status', 'locked')->count(),
            'submitted' => (clone $baseAttemptQuery)->whereIn('status', ['submitted', 'auto_submitted'])->count(),
        ];

        $classes = (clone $targetStudentsBase)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->pluck('kelas')
            ->sort(fn (string $a, string $b) => $this->compareClassNames($a, $b))
            ->values();
        $rooms = (clone $targetStudentsBase)
            ->whereNotNull('ruang')
            ->where('ruang', '!=', '')
            ->distinct()
            ->pluck('ruang')
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values();

        $kelas = trim((string) $request->query('kelas', ''));
        $ruang = trim((string) $request->query('ruang', ''));
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $students = (clone $targetStudentsBase)
            ->when($kelas !== '', fn ($q) => $q->where('kelas', $kelas))
            ->when($ruang !== '', fn ($q) => $q->where('ruang', $ruang))
            ->when($search !== '', fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            }))
            ->when($statusFilter === 'not_started', fn ($q) => $q->whereDoesntHave('attempts', fn ($aq) => $aq->where('exam_id', $exam->id)))
            ->when(in_array($statusFilter, ['in_progress', 'locked'], true), fn ($q) => $q->whereHas('attempts', fn ($aq) => $aq->where('exam_id', $exam->id)->where('status', $statusFilter)))
            ->when($statusFilter === 'submitted', fn ($q) => $q->whereHas('attempts', fn ($aq) => $aq->where('exam_id', $exam->id)->whereIn('status', ['submitted', 'auto_submitted'])))
            ->orderBy('kelas')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $attemptsByStudent = ExamAttempt::with(['violations'])
            ->withCount(['answers as answered_count' => fn ($q) => $q->whereNotNull('answer_data')])
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $tokenRemainingSeconds = $this->computeTokenRemainingSeconds($exam);

        return view('exams.monitor', compact(
            'exam',
            'students',
            'attemptsByStudent',
            'summary',
            'totalQuestions',
            'classes',
            'rooms',
            'kelas',
            'ruang',
            'search',
            'statusFilter',
            'tokenRemainingSeconds'
        ));
    }

    public function tokenStatus(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        $this->authorizeExamAccess($request, $exam);
        $exam->refreshTokenIfNeeded();
        $exam->refresh();

        $status = $exam->effectiveStatus();

        return response()->json([
            'token' => $status === 'finished' ? '-' : ($exam->require_token ? $exam->token : 'TANPA TOKEN'),
            'require_token' => (bool) $exam->require_token,
            'status' => $status,
            'refresh_minutes' => $exam->token_refresh_minutes ? (int) $exam->token_refresh_minutes : null,
            'remaining_seconds' => $this->computeTokenRemainingSeconds($exam),
        ]);
    }

    private function computeTokenRemainingSeconds(Exam $exam): ?int
    {
        if (! $exam->require_token || ! $exam->token_refresh_minutes || $exam->effectiveStatus() !== 'ongoing') {
            return null;
        }

        $lastUpdated = $exam->token_updated_at ?? $exam->updated_at ?? $exam->created_at;
        if (! $lastUpdated) {
            return (int) $exam->token_refresh_minutes * 60;
        }

        $nextRefreshAt = $lastUpdated->copy()->addMinutes((int) $exam->token_refresh_minutes);

        return max(0, (int) now()->diffInSeconds($nextRefreshAt, false));
    }

    public function resetDevice(ExamAttempt $attempt): RedirectResponse
    {
        $attempt->update(['device_id' => null]);

        return back()->with('toast_success', "Sesi perangkat {$attempt->student->name} berhasil direset.");
    }

    public function unlockAttempt(ExamAttempt $attempt): RedirectResponse
    {
        $attempt->update(['status' => 'in_progress']);

        return back()->with('toast_success', "Akses ujian {$attempt->student->name} berhasil dibuka kembali.");
    }

    public function forceSubmit(ExamAttempt $attempt, ExamScoringService $scoringService): RedirectResponse
    {
        $attempt->update([
            'status' => 'auto_submitted',
            'submitted_at' => now(),
            'receipt_code' => $attempt->receipt_code ?? ('EXM-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4))),
        ]);

        $scoringService->gradeAttempt($attempt);

        return back()->with('toast_success', "Ujian {$attempt->student->name} dipaksa selesai & dinilai.");
    }

    public function grade(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->authorizeExamAccess($request, $exam);
        if ($exam->effectiveStatus() !== 'finished') {
            return redirect()->route('exams.index')->with('toast_error', 'Ujian belum selesai. Input nilai hanya dapat dilakukan setelah ujian selesai.');
        }
        if ($exam->execution_mode !== 'mandiri' && ! $exam->scores_released) {
            return redirect()->route('exams.index')->with('toast_error', 'Panitia belum merilis nilai ujian ini.');
        }
        $exam->load(['subject', 'questionBank.questions', 'scoreArchive']);
        $allQuestions = $exam->questionBank->questions;
        $objectiveQuestions = $allQuestions->where('type', '!=', 'uraian')->values();
        $essayQuestions = $allQuestions->where('type', 'uraian')->values();

        $attempts = ExamAttempt::with(['student', 'answers'])
            ->where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->orderBy('grading_status')
            ->orderBy('id')
            ->get();

        return view('exams.grade', compact('exam', 'objectiveQuestions', 'essayQuestions', 'attempts'));
    }

    public function saveEssayGrade(Request $request, ExamAttempt $attempt, ExamScoringService $scoringService): RedirectResponse
    {
        $exam = $attempt->exam;
        $this->authorizeExamAccess($request, $exam);
        if ($exam->effectiveStatus() !== 'finished') {
            return redirect()->route('exams.index')->with('toast_error', 'Ujian belum selesai. Input nilai hanya dapat dilakukan setelah ujian selesai.');
        }
        if ($exam->execution_mode !== 'mandiri' && ! $exam->scores_released) {
            return redirect()->route('exams.index')->with('toast_error', 'Panitia belum merilis nilai ujian ini.');
        }
        if ($exam->scores_finalized) {
            return back()->with('toast_error', 'Nilai ujian ini sudah difinalisasi dan dikunci.');
        }

        $validated = $request->validate([
            'scores' => ['nullable', 'array'],
            'scores.*' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'array'],
            'manual_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'attempt_note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach (($validated['scores'] ?? []) as $questionId => $score) {
            ExamAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => (int) $questionId],
                [
                    'score' => (float) $score,
                    'is_graded' => true,
                    'teacher_note' => $validated['notes'][$questionId] ?? null,
                ]
            );
        }

        $scoringService->gradeAttempt($attempt);

        $hasManualScore = isset($validated['manual_score']) && $validated['manual_score'] !== null && $validated['manual_score'] !== '';
        $manualInt = $hasManualScore ? min(100, max(0, (int) round((float) $validated['manual_score']))) : null;
        $attempt->update([
            'manual_score' => $manualInt,
            'final_score' => $hasManualScore ? $manualInt : $attempt->fresh()->final_score,
            'grading_status' => 'final',
            'teacher_note' => $validated['attempt_note'] ?? null,
        ]);

        return back()->with('toast_success', "Nilai {$attempt->student->name} berhasil disimpan.");
    }

    public function finalizeScores(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExamAccess($request, $exam);
        if ($exam->effectiveStatus() !== 'finished') {
            return redirect()->route('exams.index')->with('toast_error', 'Ujian belum selesai.');
        }
        if ($exam->execution_mode !== 'mandiri' && ! $exam->scores_released) {
            return redirect()->route('exams.index')->with('toast_error', 'Panitia belum merilis nilai ujian ini.');
        }
        if ($exam->scores_finalized) {
            return redirect()->route('score-archives.index')->with('toast_info', 'Nilai ujian ini sudah difinalisasi sebelumnya.');
        }

        $exam->load(['subject', 'questionBank.teacher', 'questionBank.questions', 'creator']);
        $teacher = $exam->questionBank?->teacher ?? $exam->creator;
        $user = $request->user();
        $questions = $exam->questionBank?->questions ?? collect();
        $totalQuestions = $questions->count();
        $objectiveQuestions = $questions->where('type', '!=', 'uraian');
        $objectiveCount = $objectiveQuestions->count();
        $essayCount = $questions->where('type', 'uraian')->count();

        $archive = DB::transaction(function () use ($exam, $teacher, $user, $totalQuestions, $objectiveQuestions, $objectiveCount, $essayCount) {
            $exam->update([
                'scores_finalized' => true,
                'scores_finalized_at' => now(),
                'scores_finalized_by' => $user->id,
            ]);

            $attempts = ExamAttempt::with(['student', 'answers'])
                ->where('exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'auto_submitted'])
                ->get();

            $archive = ExamScoreArchive::updateOrCreate(
                ['exam_id' => $exam->id],
                [
                    'question_bank_id' => $exam->question_bank_id,
                    'subject_id' => $exam->subject_id,
                    'subject_name' => $exam->subject?->name ?? '-',
                    'teacher_id' => $teacher?->id,
                    'teacher_name' => $teacher?->name ?? '-',
                    'finalized_by' => $user->id,
                    'finalized_by_name' => $user->name,
                    'exam_title' => $exam->title,
                    'session_name' => $exam->session_name,
                    'execution_mode' => $exam->execution_mode,
                    'exam_date' => $exam->start_at,
                    'total_participants' => $attempts->count(),
                    'total_questions' => $totalQuestions,
                    'objective_count' => $objectiveCount,
                    'essay_count' => $essayCount,
                    'max_violations' => (int) ($exam->max_violations ?? 3),
                    'finalized_at' => now(),
                ]
            );

            $archive->items()->delete();
            foreach ($attempts as $attempt) {
                if (! $attempt->student) {
                    continue;
                }
                $answersByQ = $attempt->answers->keyBy('question_id');
                $answeredCount = $attempt->answers->filter(fn ($a) => ! empty($a->answer_data))->count();
                $objectiveCorrect = 0;
                foreach ($objectiveQuestions as $q) {
                    $ans = $answersByQ->get($q->id);
                    if ($ans && (float) $ans->score >= (float) $q->weight && (float) $q->weight > 0) {
                        $objectiveCorrect++;
                    }
                }
                $objectiveWrong = max(0, $objectiveCount - $objectiveCorrect);

                ExamScoreArchiveItem::create([
                    'exam_score_archive_id' => $archive->id,
                    'student_id' => $attempt->student_id,
                    'student_nis' => $attempt->student->nis,
                    'student_nisn' => $attempt->student->nisn,
                    'student_name' => $attempt->student->name,
                    'student_kelas' => $attempt->student->kelas,
                    'student_ruang' => $attempt->student->ruang,
                    'answered_count' => $answeredCount,
                    'objective_correct' => $objectiveCorrect,
                    'objective_wrong' => $objectiveWrong,
                    'objective_score' => (float) $attempt->objective_score,
                    'essay_score' => (float) $attempt->essay_score,
                    'manual_score' => $attempt->manual_score !== null ? (float) $attempt->manual_score : null,
                    'final_score' => (float) $attempt->final_score,
                    'violation_count' => (int) $attempt->violation_count,
                    'attempt_status' => $attempt->status,
                    'grading_status' => 'final',
                    'teacher_note' => $attempt->teacher_note,
                ]);
            }

            return $archive;
        });

        return redirect()->route('score-archives.show', $archive)
            ->with('toast_success', 'Nilai berhasil difinalisasi, dikunci, dan diarsipkan ke Daftar Nilai.');
    }

    public function exportResults(Request $request, Exam $exam, \App\Services\ExamExcelExportService $excelExport): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeExamAccess($request, $exam);
        if ($exam->effectiveStatus() !== 'finished') {
            return redirect()->route('exams.index')->with('toast_error', 'Ujian belum selesai. Nilai hanya dapat diunduh setelah ujian selesai.');
        }
        if ($request->user()->isGuru() && $exam->execution_mode !== 'mandiri' && ! $exam->scores_released) {
            return redirect()->route('exams.index')->with('toast_error', 'Panitia belum merilis nilai ujian ini.');
        }
        $exam->load(['subject', 'questionBank.questions']);
        $questions = $exam->questionBank?->questions ?? collect();
        $totalQuestions = $questions->count();
        $objectiveQuestions = $questions->where('type', '!=', 'uraian');
        $objectiveCount = $objectiveQuestions->count();
        $essayCount = $questions->where('type', 'uraian')->count();

        $attempts = ExamAttempt::with(['student', 'answers'])
            ->where('exam_id', $exam->id)
            ->get()
            ->sort(function (ExamAttempt $a, ExamAttempt $b) {
                $kelasCmp = $this->compareClassNames(
                    (string) ($a->student->kelas ?? ''),
                    (string) ($b->student->kelas ?? '')
                );
                if ($kelasCmp !== 0) {
                    return $kelasCmp;
                }

                return strnatcasecmp(
                    (string) ($a->student->name ?? ''),
                    (string) ($b->student->name ?? '')
                );
            })
            ->values();

        $rows = $attempts->map(function (ExamAttempt $attempt) use ($objectiveQuestions, $objectiveCount, $totalQuestions) {
            $answersByQ = $attempt->answers->keyBy('question_id');
            $answeredCount = $attempt->answers->filter(fn ($a) => ! empty($a->answer_data))->count();

            $objectiveCorrect = 0;
            foreach ($objectiveQuestions as $q) {
                $ans = $answersByQ->get($q->id);
                if ($ans && (float) $ans->score >= (float) $q->weight && (float) $q->weight > 0) {
                    $objectiveCorrect++;
                }
            }

            $statusLabel = match ($attempt->status) {
                'submitted' => 'Selesai',
                'auto_submitted' => 'Selesai (Otomatis)',
                'locked' => 'Terkunci',
                default => 'Mengerjakan',
            };

            return [
                'nis' => $attempt->student->nis ?? '-',
                'name' => $attempt->student->name ?? '-',
                'kelas' => $attempt->student->kelas ?? '-',
                'ruang' => $attempt->student->ruang ?: '-',
                'answered_count' => $answeredCount,
                'objective_correct' => $objectiveCorrect,
                'objective_wrong' => max(0, $objectiveCount - $objectiveCorrect),
                'objective_score' => (float) $attempt->objective_score,
                'essay_score' => (float) $attempt->essay_score,
                'violation_count' => (int) $attempt->violation_count,
                'status_label' => $statusLabel,
                'grading_label' => $attempt->grading_status === 'final' ? 'Selesai Dinilai' : 'Menunggu Koreksi',
            ];
        });

        $spreadsheet = $excelExport->buildWorkbook([
            'title' => $exam->title,
            'subject' => $exam->subject->name ?? '-',
            'mode' => $exam->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri',
            'schedule' => $exam->start_at->translatedFormat('d M Y H:i') . ' WIB',
            'total_questions' => $totalQuestions,
            'objective_count' => $objectiveCount,
            'essay_count' => $essayCount,
            'max_violations' => (int) $exam->max_violations,
        ], $rows);

        $filename = 'nilai_' . Str::slug($exam->title) . '.xlsx';
        $tempPath = storage_path('app/' . $filename);
        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    private function compareClassNames(string $a, string $b): int
    {
        $parse = function (string $kelas): array {
            $trimmed = trim($kelas);
            if (preg_match('/^(XII|XI|X|\d+)\b[\s-]*(.*)$/i', $trimmed, $m)) {
                $prefix = strtoupper($m[1]);
                $grade = match ($prefix) {
                    'X' => 10,
                    'XI' => 11,
                    'XII' => 12,
                    default => (int) $prefix,
                };

                return [$grade, trim($m[2])];
            }

            return [999, $trimmed];
        };

        [$gradeA, $restA] = $parse($a);
        [$gradeB, $restB] = $parse($b);

        if ($gradeA !== $gradeB) {
            return $gradeA <=> $gradeB;
        }

        return strnatcasecmp($restA, $restB);
    }

    private function authorizeExamAccess(Request $request, Exam $exam): void
    {
        $user = $request->user();
        if ($user->isGuru()) {
            $isOwner = $exam->created_by === $user->id || $exam->questionBank?->teacher_id === $user->id;
            if (! $isOwner) {
                abort(403, 'Anda tidak memiliki akses ke ujian ini.');
            }
        }
    }
}

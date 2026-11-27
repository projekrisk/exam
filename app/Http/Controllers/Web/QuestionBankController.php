<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\Subject;
use App\Services\QuestionExcelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $banks = QuestionBank::with(['subject', 'teacher'])
            ->withCount(['questions', 'exams'])
            ->when($user->isGuru(), fn ($q) => $q->where('teacher_id', $user->id))
            ->when(! $user->isGuru() && ! $user->isSuperAdmin(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('is_ready', true)
                ->orWhere('teacher_id', $user->id)))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('subject', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', function ($q) use ($status) {
                if ($status === 'locked') {
                    $q->whereHas('exams');
                } elseif ($status === 'ready') {
                    $q->whereDoesntHave('exams')->where('is_ready', true);
                } elseif ($status === 'revision') {
                    $q->whereDoesntHave('exams')
                        ->where('is_ready', false)
                        ->whereNotNull('revision_note')
                        ->where('revision_note', '!=', '');
                } elseif ($status === 'draft') {
                    $q->whereDoesntHave('exams')
                        ->where('is_ready', false)
                        ->where(fn ($sub) => $sub->whereNull('revision_note')->orWhere('revision_note', ''));
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $subjects = $user->isGuru()
            ? $user->subjects()->get()->unique('id')->sortBy('name')->values()
            : Subject::orderBy('name')->get();

        $classes = ($user->isGuru()
            ? collect($user->allAssignedClasses())
            : Student::select('kelas')->distinct()->pluck('kelas'))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $subjectClassesMap = [];
        if ($user->isGuru()) {
            foreach ($subjects as $sub) {
                $bound = collect($user->assignedClassesForSubject($sub->id))
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
                $subjectClassesMap[$sub->id] = $bound;
            }
        }

        $guruBindings = $user->isGuru() ? $user->subjectBindingGroups() : [];

        return view('question_banks.index', compact('banks', 'subjects', 'classes', 'subjectClassesMap', 'guruBindings', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'binding_key' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['string', 'max:64'],
        ]);

        $subjectId = (int) $validated['subject_id'];
        $targetClasses = $validated['target_classes'] ?? [];
        if ($user->isGuru()) {
            $matchedGroup = ! empty($validated['binding_key'])
                ? collect($user->subjectBindingGroups())->firstWhere('key', $validated['binding_key'])
                : null;
            if ($matchedGroup) {
                $subjectId = (int) $matchedGroup['subject_id'];
                $targetClasses = $matchedGroup['classes'];
            } else {
                $allowedClasses = $user->assignedClassesForSubject($subjectId);
                $selected = array_values(array_intersect($targetClasses, $allowedClasses));
                $targetClasses = ! empty($selected) ? $selected : $allowedClasses;
            }
        }

        $bank = QuestionBank::create([
            'subject_id' => $subjectId,
            'teacher_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_classes' => empty($targetClasses) ? ['ALL'] : $targetClasses,
            'is_ready' => false,
        ]);

        return redirect()->route('question-banks.show', $bank)->with('toast_success', 'Bank soal berhasil dibuat. Silakan tambahkan soal.');
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);
        $user = $request->user();

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'binding_key' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['string', 'max:64'],
        ]);

        $subjectId = (int) $validated['subject_id'];
        $targetClasses = $validated['target_classes'] ?? [];
        if ($user->isGuru()) {
            $matchedGroup = ! empty($validated['binding_key'])
                ? collect($user->subjectBindingGroups())->firstWhere('key', $validated['binding_key'])
                : null;
            if ($matchedGroup) {
                $subjectId = (int) $matchedGroup['subject_id'];
                $targetClasses = $matchedGroup['classes'];
            } else {
                $allowedClasses = $user->assignedClassesForSubject($subjectId);
                $selected = array_values(array_intersect($targetClasses, $allowedClasses));
                $targetClasses = ! empty($selected) ? $selected : $allowedClasses;
            }
        }

        $questionBank->update([
            'subject_id' => $subjectId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_classes' => empty($targetClasses) ? ['ALL'] : $targetClasses,
        ]);

        return back()->with('toast_success', 'Bank soal berhasil diperbarui.');
    }

    public function show(Request $request, QuestionBank $questionBank): View
    {
        $this->authorizeBank($request, $questionBank);

        $questionBank->loadCount('exams')->load(['subject', 'teacher', 'questions']);

        return view('question_banks.show', compact('questionBank'));
    }

    public function exportPdf(Request $request, QuestionBank $questionBank): View
    {
        $this->authorizeBank($request, $questionBank);
        $questionBank->load(['subject', 'teacher', 'questions']);
        $questions = $questionBank->questions->sortBy('order_no')->values();

        $settings = array_merge([
            'school_name' => 'SMAN 1 Malingping',
            'school_logo_path' => '',
            'principal_name' => '',
            'principal_nip' => '',
            'activity_name' => 'Penilaian Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ], AppSetting::allMap());

        return view('question_banks.pdf', compact('questionBank', 'questions', 'settings'));
    }

    public function toggleReady(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);

        $newReady = ! $questionBank->is_ready;
        $questionBank->update([
            'is_ready' => $newReady,
            'revision_note' => $newReady ? null : $questionBank->revision_note,
            'revision_requested_at' => $newReady ? null : $questionBank->revision_requested_at,
        ]);

        return back()->with('toast_success', $newReady ? 'Bank soal ditandai Siap Pakai untuk Ujian.' : 'Bank soal dikembalikan ke Draft.');
    }

    public function requestRevision(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'revision_note' => ['required', 'string', 'max:1000'],
        ]);

        $questionBank->update([
            'is_ready' => false,
            'revision_note' => trim($validated['revision_note']),
            'revision_requested_at' => now(),
        ]);

        $msg = 'Status Siap Pakai dibatalkan. Catatan perbaikan dikirim ke pembuat soal.';

        if ($request->user()->isSuperAdmin() || $questionBank->teacher_id === $request->user()->id) {
            return back()->with('toast_success', $msg);
        }

        return redirect()->route('question-banks.index')->with('toast_success', $msg);
    }

    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);

        $questionBank->delete();

        return redirect()->route('question-banks.index')->with('toast_success', 'Bank soal berhasil dihapus.');
    }

    public function templateQuestions(QuestionExcelImportService $importer): BinaryFileResponse
    {
        $path = $importer->generateTemplate();

        return response()->download($path, 'template_soal.xlsx')->deleteFileAfterSend();
    }

    public function importQuestions(Request $request, QuestionBank $questionBank, QuestionExcelImportService $importer): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $result = $importer->import($request->file('file'), $questionBank);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('toast_error', 'Gagal mengimpor file soal. Pastikan file tidak rusak dan format kolom sesuai template.');
        }

        if ($result['imported'] === 0 && ! empty($result['errors'])) {
            return back()->with('toast_error', $result['errors'][0]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} butir soal.";
        if ($result['skipped'] > 0) {
            $msg .= " ({$result['skipped']} baris dilewati)";
        }

        return back()->with('toast_success', $msg);
    }

    public function storeQuestion(Request $request, QuestionBank $questionBank): RedirectResponse|JsonResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);

        $validated = $this->validateQuestionRequest($request);

        $mediaPath = $request->hasFile('media')
            ? $request->file('media')->store('questions', 'public')
            : null;

        $options = $this->buildOptionsPayload($validated['type'], $request);
        $nextOrder = ((int) $questionBank->questions()->max('order_no')) + 1;

        $question = Question::create([
            'question_bank_id' => $questionBank->id,
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'media_path' => $mediaPath,
            'options' => $options,
            'scoring_mode' => $validated['scoring_mode'] ?? 'full',
            'weight' => $validated['weight'],
            'order_no' => $nextOrder,
        ]);

        Cache::forget("question_bank:{$questionBank->id}:questions");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Soal berhasil disimpan. Siap membuat soal baru.',
                'question_id' => $question->id,
            ]);
        }

        return back()->with('toast_success', 'Soal berhasil ditambahkan.');
    }

    public function updateQuestion(Request $request, QuestionBank $questionBank, Question $question): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);
        if ($question->question_bank_id !== $questionBank->id) {
            abort(404);
        }

        $validated = $this->validateQuestionRequest($request);

        $mediaPath = $question->media_path;
        if ($request->boolean('remove_media') && $mediaPath) {
            Storage::disk('public')->delete($mediaPath);
            $mediaPath = null;
        }
        if ($request->hasFile('media')) {
            if ($mediaPath) {
                Storage::disk('public')->delete($mediaPath);
            }
            $mediaPath = $request->file('media')->store('questions', 'public');
        }

        $options = $this->buildOptionsPayload($validated['type'], $request, $question->options ?? []);

        $question->update([
            'type' => $validated['type'],
            'question_text' => $validated['question_text'],
            'media_path' => $mediaPath,
            'options' => $options,
            'scoring_mode' => $validated['scoring_mode'] ?? 'full',
            'weight' => $validated['weight'],
        ]);

        Cache::forget("question_bank:{$questionBank->id}:questions");

        return back()->with('toast_success', 'Butir soal berhasil diperbarui.');
    }

    private function validateQuestionRequest(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:pg,pg_kompleks,benar_salah,menjodohkan,uraian'],
            'question_text' => ['required', 'string'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'scoring_mode' => ['nullable', 'in:full,partial'],
            'media' => ['nullable', 'image', 'max:2048'],
            'pg_options' => ['nullable', 'array'],
            'pg_option_images' => ['nullable', 'array'],
            'pg_option_images.*' => ['nullable', 'image', 'max:2048'],
            'pg_correct' => ['nullable', 'string'],
            'pgk_correct' => ['nullable', 'array'],
            'bs_statements' => ['nullable', 'array'],
            'bs_correct' => ['nullable', 'array'],
            'match_left' => ['nullable', 'array'],
            'match_right' => ['nullable', 'array'],
            'uraian_mode' => ['nullable', 'in:manual,auto_text'],
            'uraian_answers' => ['nullable', 'string'],
        ]);
    }

    public function destroyQuestion(Request $request, QuestionBank $questionBank, Question $question): RedirectResponse
    {
        $this->authorizeBank($request, $questionBank);
        $this->ensureBankUnlocked($questionBank);

        if ($question->question_bank_id !== $questionBank->id) {
            abort(404);
        }

        if ($question->media_path) {
            Storage::disk('public')->delete($question->media_path);
        }

        $question->delete();
        Cache::forget("question_bank:{$questionBank->id}:questions");

        return back()->with('toast_success', 'Soal berhasil dihapus.');
    }

    private function authorizeBank(Request $request, QuestionBank $bank): void
    {
        $user = $request->user();
        if ($user->isGuru() && $bank->teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke bank soal ini.');
        }

        if (! $user->isSuperAdmin() && ! $user->isGuru() && ! $bank->is_ready && $bank->teacher_id !== $user->id) {
            abort(403, 'Bank soal berstatus Draf hanya dapat dilihat oleh guru pembuat atau Super Admin.');
        }
    }

    private function ensureBankUnlocked(QuestionBank $bank): void
    {
        if ($bank->isLocked()) {
            abort(403, 'Bank soal sudah dijadwalkan ujian dan terkunci sehingga tidak dapat diubah.');
        }
    }

    private function buildOptionsPayload(string $type, Request $request, array $existingOptions = []): ?array
    {
        $existingByKey = collect($existingOptions)->keyBy('key');
        $removeImages = $request->input('remove_option_images', []);

        if ($type === 'pg' || $type === 'pg_kompleks') {
            $correct = (string) $request->input('pg_correct', 'A');
            $correctKeys = array_map('strval', $request->input('pgk_correct', []));
            $items = [];

            foreach ($request->input('pg_options', []) as $key => $text) {
                $keyStr = (string) $key;
                $clean = trim((string) $text);
                $prevOpt = $existingByKey->get($keyStr, []);
                $imagePath = $prevOpt['image_path'] ?? null;

                if (! empty($removeImages[$keyStr]) && $imagePath) {
                    Storage::disk('public')->delete($imagePath);
                    $imagePath = null;
                }

                if ($request->hasFile("pg_option_images.{$keyStr}")) {
                    if ($imagePath) {
                        Storage::disk('public')->delete($imagePath);
                    }
                    $imagePath = $request->file("pg_option_images.{$keyStr}")->store('questions/options', 'public');
                }

                if ($clean !== '' || $imagePath !== null) {
                    $items[] = [
                        'key' => $keyStr,
                        'text' => $clean,
                        'image_path' => $imagePath,
                        'is_correct' => $type === 'pg'
                            ? ($keyStr === $correct)
                            : in_array($keyStr, $correctKeys, true),
                    ];
                }
            }
            if (empty($items)) {
                $items[] = [
                    'key' => '1',
                    'statement' => trim((string) ($statements[0] ?? '')),
                    'correct' => (($correctMap[0] ?? '1') === '1'),
                ];
            }

            return $items;
        }

        if ($type === 'benar_salah') {
            $statements = $request->input('bs_statements', []);
            $correctMap = $request->input('bs_correct', []);
            $items = [];
            foreach ($statements as $idx => $stmt) {
                $clean = trim((string) $stmt);
                if ($clean !== '') {
                    $key = (string) ($idx + 1);
                    $items[] = [
                        'key' => $key,
                        'statement' => $clean,
                        'correct' => (($correctMap[$idx] ?? '1') === '1'),
                    ];
                }
            }
            if (empty($items)) {
                $items[] = [
                    'key' => '1',
                    'statement' => trim((string) ($statements[0] ?? '')),
                    'correct' => (($correctMap[0] ?? '1') === '1'),
                ];
            }

            return $items;
        }

        if ($type === 'menjodohkan') {
            $lefts = $request->input('match_left', []);
            $rights = $request->input('match_right', []);
            $items = [];
            $num = 1;
            foreach ($lefts as $idx => $leftVal) {
                $l = trim((string) $leftVal);
                $r = trim((string) ($rights[$idx] ?? ''));
                if ($l !== '' && $r !== '') {
                    $items[] = [
                        'key' => (string) $num++,
                        'left' => $l,
                        'right' => $r,
                    ];
                }
            }

            return $items;
        }

        if ($type === 'uraian') {
            $mode = (string) $request->input('uraian_mode', 'manual');
            $rawAnswers = (string) $request->input('uraian_answers', '');
            $accepted = collect(preg_split('/[\r\n,;]+/', $rawAnswers))
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            return [
                'mode' => $mode,
                'accepted_answers' => $mode === 'auto_text' ? $accepted : [],
            ];
        }

        return null;
    }
}

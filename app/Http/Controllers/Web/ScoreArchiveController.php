<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ExamScoreArchive;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScoreArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $subjectId = trim((string) $request->query('subject_id', ''));

        $archives = ExamScoreArchive::query()
            ->when(
                $user->isGuru(),
                fn ($q) => $q->where('teacher_id', $user->id)->where('visible_to_teacher', true),
                fn ($q) => $q->where('visible_to_admin', true)
            )
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('exam_title', 'like', "%{$search}%")
                    ->orWhere('subject_name', 'like', "%{$search}%")
                    ->orWhere('teacher_name', 'like', "%{$search}%")
                    ->orWhere('session_name', 'like', "%{$search}%");
            }))
            ->when($subjectId !== '', fn ($q) => $q->where('subject_id', (int) $subjectId))
            ->latest('finalized_at')
            ->paginate(10)
            ->withQueryString();

        $subjects = $user->isGuru()
            ? $user->subjects()->get()->unique('id')->sortBy('name')->values()
            : Subject::orderBy('name')->get();

        return view('score_archives.index', compact('archives', 'subjects', 'search', 'subjectId'));
    }

    public function show(Request $request, ExamScoreArchive $scoreArchive): View
    {
        $this->authorizeArchiveAccess($request, $scoreArchive);

        $search = trim((string) $request->query('q', ''));
        $kelas = trim((string) $request->query('kelas', ''));

        $classes = $scoreArchive->items()
            ->select('student_kelas')
            ->distinct()
            ->pluck('student_kelas')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $items = $scoreArchive->items()
            ->when($kelas !== '', fn ($q) => $q->where('student_kelas', $kelas))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('student_name', 'like', "%{$search}%")
                    ->orWhere('student_nis', 'like', "%{$search}%")
                    ->orWhere('student_nisn', 'like', "%{$search}%");
            }))
            ->orderBy('student_kelas')
            ->orderBy('student_name')
            ->paginate(15)
            ->withQueryString();

        return view('score_archives.show', compact('scoreArchive', 'items', 'classes', 'search', 'kelas'));
    }

    public function export(Request $request, ExamScoreArchive $scoreArchive, \App\Services\ExamExcelExportService $excelExport): BinaryFileResponse
    {
        $this->authorizeArchiveAccess($request, $scoreArchive);

        $items = $scoreArchive->items()
            ->get()
            ->sort(function ($a, $b) {
                $kelasCmp = strnatcasecmp((string) $a->student_kelas, (string) $b->student_kelas);
                return $kelasCmp !== 0
                    ? $kelasCmp
                    : strnatcasecmp((string) $a->student_name, (string) $b->student_name);
            })
            ->values();

        $rows = $items->map(function ($item) {
            $statusLabel = match ($item->attempt_status) {
                'submitted' => 'Selesai',
                'auto_submitted' => 'Selesai (Otomatis)',
                'locked' => 'Terkunci',
                default => 'Selesai',
            };

            return [
                'nis' => $item->student_nis,
                'name' => $item->student_name,
                'kelas' => $item->student_kelas,
                'ruang' => $item->student_ruang ?: '-',
                'answered_count' => (int) $item->answered_count,
                'objective_correct' => (int) $item->objective_correct,
                'objective_wrong' => (int) $item->objective_wrong,
                'objective_score' => (float) $item->objective_score,
                'essay_score' => (float) $item->essay_score,
                'violation_count' => (int) $item->violation_count,
                'status_label' => $statusLabel,
                'grading_label' => $item->grading_status === 'final' ? 'Selesai Dinilai' : 'Menunggu Koreksi',
            ];
        });

        $spreadsheet = $excelExport->buildWorkbook([
            'title' => $scoreArchive->exam_title,
            'subject' => $scoreArchive->subject_name,
            'mode' => $scoreArchive->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri',
            'schedule' => ($scoreArchive->exam_date?->translatedFormat('d M Y H:i') ?? $scoreArchive->finalized_at->translatedFormat('d M Y H:i')) . ' WIB',
            'total_questions' => (int) $scoreArchive->total_questions,
            'objective_count' => (int) $scoreArchive->objective_count,
            'essay_count' => (int) $scoreArchive->essay_count,
            'max_violations' => (int) $scoreArchive->max_violations,
        ], $rows);

        $filename = 'nilai-' . Str::slug($scoreArchive->exam_title) . '.xlsx';
        $tempPath = storage_path('app/' . $filename);
        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function pdf(Request $request, ExamScoreArchive $scoreArchive): View
    {
        $this->authorizeArchiveAccess($request, $scoreArchive);

        $kelas = trim((string) $request->query('kelas', ''));
        $items = $scoreArchive->items()
            ->when($kelas !== '', fn ($q) => $q->where('student_kelas', $kelas))
            ->get()
            ->sort(function ($a, $b) {
                $kelasCmp = strnatcasecmp((string) $a->student_kelas, (string) $b->student_kelas);
                return $kelasCmp !== 0
                    ? $kelasCmp
                    : strnatcasecmp((string) $a->student_name, (string) $b->student_name);
            })
            ->values();

        $settings = array_merge([
            'school_name' => 'SMAN 1 Malingping',
            'school_logo_path' => '',
            'principal_name' => '',
            'principal_nip' => '',
            'activity_name' => 'Penilaian Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ], AppSetting::allMap());

        $scores = $items->pluck('final_score')->map(fn ($v) => (float) $v);
        $stats = [
            'count' => $items->count(),
            'avg' => $scores->isNotEmpty() ? round($scores->avg(), 2) : 0,
            'max' => $scores->isNotEmpty() ? $scores->max() : 0,
            'min' => $scores->isNotEmpty() ? $scores->min() : 0,
        ];

        return view('score_archives.pdf', compact('scoreArchive', 'items', 'settings', 'stats', 'kelas'));
    }

    public function destroy(Request $request, ExamScoreArchive $scoreArchive): RedirectResponse
    {
        $this->authorizeArchiveAccess($request, $scoreArchive);

        $title = $scoreArchive->exam_title;
        if ($request->user()->isGuru()) {
            $scoreArchive->update(['visible_to_teacher' => false]);
        } else {
            $scoreArchive->update(['visible_to_admin' => false]);
        }

        if (! $scoreArchive->visible_to_teacher && ! $scoreArchive->visible_to_admin) {
            $scoreArchive->delete();
        }

        return redirect()->route('score-archives.index')
            ->with('toast_success', "Daftar nilai {$title} berhasil dihapus dari akun Anda.");
    }

    private function authorizeArchiveAccess(Request $request, ExamScoreArchive $scoreArchive): void
    {
        $user = $request->user();
        if ($user->isAdmin() && $scoreArchive->visible_to_admin) {
            return;
        }
        if ($user->isGuru() && (int) $scoreArchive->teacher_id === (int) $user->id && $scoreArchive->visible_to_teacher) {
            return;
        }
        abort(403, 'Anda tidak berhak mengakses arsip nilai ini.');
    }
}

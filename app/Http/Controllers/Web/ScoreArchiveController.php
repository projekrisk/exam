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

    public function export(Request $request, ExamScoreArchive $scoreArchive): BinaryFileResponse
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai');

        $sheet->setCellValue('A1', 'REKAP NILAI UJIAN - ' . strtoupper($scoreArchive->exam_title));
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue(
            'A2',
            'Mata Pelajaran: ' . $scoreArchive->subject_name
            . '  |  Mode: ' . ($scoreArchive->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri')
            . '  |  Jadwal: ' . ($scoreArchive->exam_date?->translatedFormat('d M Y H:i') ?? $scoreArchive->finalized_at->translatedFormat('d M Y H:i')) . ' WIB'
        );
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $sheet->setCellValue(
            'A3',
            "Total Soal: {$scoreArchive->total_questions} (Objektif: {$scoreArchive->objective_count}, Uraian: {$scoreArchive->essay_count})  |  Total Peserta: " . $items->count()
        );
        $sheet->mergeCells('A3:N3');
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $headerRow = 5;
        $headers = [
            'No',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Ruang',
            'Dijawab',
            'Benar Objektif',
            'Salah / Kurang',
            'Skor Objektif',
            'Skor Uraian',
            'Nilai Akhir',
            'Pelanggaran',
            'Status Ujian',
            'Status Koreksi',
        ];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EFF6FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $rowIndex = $headerRow + 1;
        foreach ($items as $idx => $item) {
            $statusLabel = match ($item->attempt_status) {
                'submitted' => 'Selesai',
                'auto_submitted' => 'Selesai (Otomatis)',
                'locked' => 'Terkunci',
                default => 'Selesai',
            };
            $gradingLabel = $item->grading_status === 'final' ? 'Selesai Dinilai' : 'Menunggu Koreksi';

            $sheet->fromArray([
                $idx + 1,
                $item->student_nis,
                $item->student_name,
                $item->student_kelas,
                $item->student_ruang ?: '-',
                "{$item->answered_count} / {$scoreArchive->total_questions}",
                "{$item->objective_correct} / {$scoreArchive->objective_count}",
                (int) $item->objective_wrong,
                (float) $item->objective_score,
                (float) $item->essay_score,
                (float) $item->final_score,
                "{$item->violation_count} / {$scoreArchive->max_violations}",
                $statusLabel,
                $gradingLabel,
            ], null, "A{$rowIndex}");

            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $rowIndex++;
        }

        $lastRow = max($headerRow, $rowIndex - 1);
        $sheet->getStyle("A{$headerRow}:N{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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

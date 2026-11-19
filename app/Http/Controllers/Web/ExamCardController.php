<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Student;
use Illuminate\Http\Request;

class ExamCardController extends Controller
{
    public function index(Request $request)
    {
        $kelasList = Student::query()
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->pluck('kelas')
            ->sort(function (string $a, string $b) {
                $rank = fn (string $k): int => match (true) {
                    preg_match('/^XII(?![I])/i', $k) === 1 => 12,
                    preg_match('/^XI(?![I])/i', $k) === 1 => 11,
                    preg_match('/^X(?![IV])/i', $k) === 1 => 10,
                    default => 99,
                };
                $ra = $rank($a);
                $rb = $rank($b);

                return $ra !== $rb ? $ra <=> $rb : strnatcasecmp($a, $b);
            })
            ->values();

        $selectedKelas = $request->query('kelas', '');
        $search = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->when($selectedKelas !== '', fn ($q) => $q->where('kelas', $selectedKelas))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->orderBy('kelas')
            ->orderBy('ruang')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $settings = $this->loadSettings();

        return view('exam_cards.index', compact('students', 'kelasList', 'selectedKelas', 'search', 'settings'));
    }

    public function print(Request $request)
    {
        $settings = $this->loadSettings();

        $query = Student::query();

        if ($request->filled('student_id')) {
            $query->where('id', $request->integer('student_id'));
        } elseif ($request->filled('student_ids') && is_array($request->input('student_ids'))) {
            $ids = array_filter(array_map('intval', $request->input('student_ids')));
            $query->whereIn('id', $ids ?: [0]);
        } else {
            $query->where('is_active', true);
            $kelas = (string) $request->query('kelas', '');
            if ($kelas !== '' && $kelas !== 'all') {
                $query->where('kelas', $kelas);
            }
        }

        $students = $query
            ->orderBy('kelas')
            ->orderBy('ruang')
            ->orderBy('name')
            ->get();

        $principalName = $settings['principal_name'] ?: '-';
        $principalNip = $settings['principal_nip'] ?: '-';
        $principalQrPayload = "Ditandatangani oleh: {$principalName}\nNIP: {$principalNip}";

        return view('exam_cards.print', compact('students', 'settings', 'principalQrPayload'));
    }

    private function loadSettings(): array
    {
        return array_merge([
            'school_name' => 'SMAN 1 Malingping',
            'school_logo_path' => '',
            'principal_name' => '',
            'principal_nip' => '',
            'activity_name' => 'Penilaian Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ], AppSetting::allMap());
    }
}

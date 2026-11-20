<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamCardController extends Controller
{
    public function index(Request $request): View
    {
        $kelasList = $this->sortedClasses();

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
            ->orderBy('seat_number')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $settings = $this->loadSettings();
        $totalActiveStudents = Student::where('is_active', true)->count();
        $totalClassCount = $kelasList->count();
        $seatingConfig = $this->loadSeatingConfig();

        return view('exam_cards.index', compact(
            'students',
            'kelasList',
            'selectedKelas',
            'search',
            'settings',
            'totalActiveStudents',
            'totalClassCount',
            'seatingConfig'
        ));
    }

    public function generateRooms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_count' => ['required', 'integer', 'min:1', 'max:200'],
            'max_per_room' => ['required', 'integer', 'min:1', 'max:200'],
            'class_count' => ['required', 'integer', 'min:1', 'max:200'],
            'distribution_mode' => ['required', 'in:half_split,sequential'],
            'seat_count' => ['required', 'integer', 'min:1', 'max:200'],
            'rows' => ['required', 'integer', 'min:1', 'max:30'],
            'cols' => ['required', 'integer', 'min:1', 'max:20'],
            'seating_pattern' => ['required', 'in:left_to_right,right_to_left,front_to_back,zigzag'],
        ]);

        $roomCount = (int) $validated['room_count'];
        $maxPerRoom = (int) $validated['max_per_room'];
        $mode = $validated['distribution_mode'];

        $students = Student::where('is_active', true)
            ->get()
            ->sort(function (Student $a, Student $b) {
                $kelasCmp = $this->compareClassNames((string) $a->kelas, (string) $b->kelas);
                if ($kelasCmp !== 0) {
                    return $kelasCmp;
                }
                return strnatcasecmp((string) $a->name, (string) $b->name);
            })
            ->values();

        if ($students->isEmpty()) {
            return back()->with('toast_error', 'Tidak ada data siswa aktif untuk di-generate ke ruang.');
        }

        $total = $students->count();
        $neededRooms = (int) ceil($total / $maxPerRoom);
        $effectiveRoomCount = $neededRooms;

        $roomBuckets = [];
        for ($r = 1; $r <= $effectiveRoomCount; $r++) {
            $roomBuckets[$r] = [];
        }

        if ($mode === 'half_split' && $maxPerRoom >= 2) {
            $firstWaveCap = (int) floor($maxPerRoom / 2);
            $secondWaveCap = $maxPerRoom - $firstWaveCap;
            $queue = $students->values()->all();
            $idx = 0;

            $waveOneBuckets = [];
            $waveTwoBuckets = [];
            for ($r = 1; $r <= $effectiveRoomCount; $r++) {
                $waveOneBuckets[$r] = [];
                $waveTwoBuckets[$r] = [];
            }

            for ($r = 1; $r <= $effectiveRoomCount && $idx < $total; $r++) {
                while (count($waveOneBuckets[$r]) < $firstWaveCap && $idx < $total) {
                    $waveOneBuckets[$r][] = $queue[$idx++];
                }
            }

            for ($r = 1; $r <= $effectiveRoomCount && $idx < $total; $r++) {
                while (count($waveTwoBuckets[$r]) < $secondWaveCap && $idx < $total) {
                    $waveTwoBuckets[$r][] = $queue[$idx++];
                }
            }

            for ($r = 1; $r <= $effectiveRoomCount; $r++) {
                $a = $waveOneBuckets[$r];
                $b = $waveTwoBuckets[$r];
                $maxLen = max(count($a), count($b));
                for ($i = 0; $i < $maxLen; $i++) {
                    if (isset($a[$i])) {
                        $roomBuckets[$r][] = $a[$i];
                    }
                    if (isset($b[$i])) {
                        $roomBuckets[$r][] = $b[$i];
                    }
                }
            }
        } else {
            $r = 1;
            foreach ($students as $student) {
                if (count($roomBuckets[$r]) >= $maxPerRoom) {
                    $r++;
                }
                $roomBuckets[$r][] = $student;
            }
        }

        DB::transaction(function () use ($roomBuckets, $validated) {
            foreach ($roomBuckets as $roomNo => $bucket) {
                $roomName = 'Ruang ' . $roomNo;
                foreach ($bucket as $seatIdx => $student) {
                    $student->update([
                        'ruang' => $roomName,
                        'seat_number' => $seatIdx + 1,
                    ]);
                }
            }

            AppSetting::setValue('seating_room_count', (string) $validated['room_count']);
            AppSetting::setValue('seating_max_per_room', (string) $validated['max_per_room']);
            AppSetting::setValue('seating_seat_count', (string) $validated['seat_count']);
            AppSetting::setValue('seating_rows', (string) $validated['rows']);
            AppSetting::setValue('seating_cols', (string) $validated['cols']);
            AppSetting::setValue('seating_pattern', $validated['seating_pattern']);
        });

        $usedRooms = collect($roomBuckets)->filter(fn ($b) => count($b) > 0)->count();

        return redirect()->route('exam-cards.index')
            ->with('toast_success', "Berhasil menempatkan {$students->count()} siswa ke dalam {$usedRooms} ruang beserta nomor meja.");
    }

    public function seatingPlan(Request $request): View
    {
        $saved = $this->loadSeatingConfig();

        $seatCount = max(1, min(200, (int) $request->query('seat_count', $saved['seat_count'])));
        $rows = max(1, min(30, (int) $request->query('rows', $saved['rows'])));
        $cols = max(1, min(20, (int) $request->query('cols', $saved['cols'])));
        $pattern = (string) $request->query('pattern', $saved['seating_pattern']);
        if (! in_array($pattern, ['left_to_right', 'right_to_left', 'front_to_back', 'zigzag'], true)) {
            $pattern = 'left_to_right';
        }

        $doorSide = (string) $request->query('door_side', 'auto');
        if (! in_array($doorSide, ['auto', 'left', 'right'], true)) {
            $doorSide = 'auto';
        }
        $effectiveDoorSide = $doorSide === 'auto'
            ? ($pattern === 'right_to_left' ? 'right' : 'left')
            : $doorSide;

        $viewMode = (string) $request->query('view_mode', 'both');
        if (! in_array($viewMode, ['both', 'plan', 'labels'], true)) {
            $viewMode = 'both';
        }

        if ($request->boolean('save')) {
            AppSetting::setValue('seating_seat_count', (string) $seatCount);
            AppSetting::setValue('seating_rows', (string) $rows);
            AppSetting::setValue('seating_cols', (string) $cols);
            AppSetting::setValue('seating_pattern', $pattern);
        }

        $rooms = Student::where('is_active', true)
            ->whereNotNull('ruang')
            ->where('ruang', '!=', '')
            ->distinct()
            ->pluck('ruang')
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values();

        $selectedRoom = trim((string) $request->query('ruang', ''));
        if ($selectedRoom === '' && $rooms->isNotEmpty()) {
            $selectedRoom = $rooms->first();
        }

        $targetRooms = $selectedRoom === 'all'
            ? $rooms
            : ($selectedRoom !== '' ? collect([$selectedRoom]) : collect());

        $roomPlans = [];
        foreach ($targetRooms as $roomName) {
            $roomStudents = Student::where('is_active', true)
                ->where('ruang', $roomName)
                ->orderByRaw('CASE WHEN seat_number IS NULL THEN 1 ELSE 0 END')
                ->orderBy('seat_number')
                ->orderBy('name')
                ->get()
                ->values();

            $bySeat = [];
            foreach ($roomStudents as $idx => $st) {
                $seatNo = $st->seat_number ?: ($idx + 1);
                while (isset($bySeat[$seatNo])) {
                    $seatNo++;
                }
                $bySeat[$seatNo] = $st;
            }

            $grid = [];
            for ($r = 0; $r < $rows; $r++) {
                $rowCells = [];
                for ($c = 0; $c < $cols; $c++) {
                    $seatNo = match ($pattern) {
                        'right_to_left' => ($r * $cols) + ($cols - 1 - $c) + 1,
                        'front_to_back' => ($c * $rows) + $r + 1,
                        'zigzag' => ($r % 2 === 0)
                            ? ($r * $cols) + $c + 1
                            : ($r * $cols) + ($cols - 1 - $c) + 1,
                        default => ($r * $cols) + $c + 1,
                    };

                    $rowCells[] = [
                        'seat_no' => $seatNo <= $seatCount ? $seatNo : null,
                        'student' => $seatNo <= $seatCount ? ($bySeat[$seatNo] ?? null) : null,
                    ];
                }
                if (collect($rowCells)->contains(fn (array $cell) => $cell['student'] !== null)) {
                    $grid[] = $rowCells;
                }
            }

            // Trim columns that have no students at all so there is no empty area
            $usedColIndices = [];
            if (! empty($grid)) {
                for ($c = 0; $c < $cols; $c++) {
                    foreach ($grid as $rowCells) {
                        if (($rowCells[$c]['student'] ?? null) !== null) {
                            $usedColIndices[] = $c;
                            break;
                        }
                    }
                }
                if (! empty($usedColIndices) && count($usedColIndices) < $cols) {
                    $grid = array_map(
                        fn (array $rowCells) => array_values(array_intersect_key($rowCells, array_flip($usedColIndices))),
                        $grid
                    );
                }
            }
            $effectiveCols = ! empty($usedColIndices) ? count($usedColIndices) : $cols;

            ksort($bySeat);
            $labels = [];
            foreach ($bySeat as $sNo => $st) {
                $labels[] = [
                    'seat_no' => $sNo,
                    'student' => $st,
                ];
            }

            $roomPlans[] = [
                'room_name' => $roomName,
                'student_count' => $roomStudents->count(),
                'cols' => $effectiveCols,
                'grid' => $grid,
                'labels' => $labels,
            ];
        }

        $settings = $this->loadSettings();

        return view('exam_cards.seating', compact(
            'rooms',
            'selectedRoom',
            'roomPlans',
            'seatCount',
            'rows',
            'cols',
            'pattern',
            'doorSide',
            'effectiveDoorSide',
            'viewMode',
            'settings'
        ));
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
            ->orderBy('seat_number')
            ->orderBy('name')
            ->get();

        $principalName = $settings['principal_name'] ?: '-';
        $principalNip = $settings['principal_nip'] ?: '-';
        $principalQrPayload = "Ditandatangani oleh: {$principalName}\nNIP: {$principalNip}";

        return view('exam_cards.print', compact('students', 'settings', 'principalQrPayload'));
    }

    private function sortedClasses()
    {
        return Student::query()
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->pluck('kelas')
            ->sort(fn (string $a, string $b) => $this->compareClassNames($a, $b))
            ->values();
    }

    private function compareClassNames(string $a, string $b): int
    {
        $rank = fn (string $k): int => match (true) {
            preg_match('/^XII(?![I])/i', $k) === 1 => 12,
            preg_match('/^XI(?![I])/i', $k) === 1 => 11,
            preg_match('/^X(?![IV])/i', $k) === 1 => 10,
            default => 99,
        };
        $ra = $rank($a);
        $rb = $rank($b);

        return $ra !== $rb ? $ra <=> $rb : strnatcasecmp($a, $b);
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

    private function loadSeatingConfig(): array
    {
        $map = AppSetting::allMap();

        return [
            'room_count' => (int) ($map['seating_room_count'] ?? 20),
            'max_per_room' => (int) ($map['seating_max_per_room'] ?? 40),
            'seat_count' => (int) ($map['seating_seat_count'] ?? 40),
            'rows' => (int) ($map['seating_rows'] ?? 5),
            'cols' => (int) ($map['seating_cols'] ?? 8),
            'seating_pattern' => (string) ($map['seating_pattern'] ?? 'left_to_right'),
        ];
    }
}

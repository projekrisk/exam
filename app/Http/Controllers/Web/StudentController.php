<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $kelas = trim((string) $request->query('kelas', ''));

        $students = Student::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nis', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('ruang', 'like', "%{$search}%")))
            ->when($kelas !== '', fn ($q) => $q->where('kelas', $kelas))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $classes = Student::select('kelas')->distinct()->pluck('kelas')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('students.index', compact('students', 'classes', 'search', 'kelas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis'],
            'nisn' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:64'],
            'ruang' => ['nullable', 'string', 'max:64'],
        ]);

        Student::create($validated + ['is_active' => true]);

        return back()->with('toast_success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis,' . $student->id],
            'nisn' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string', 'max:64'],
            'ruang' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $student->update([
            'nis' => $validated['nis'],
            'nisn' => $validated['nisn'],
            'name' => $validated['name'],
            'kelas' => $validated['kelas'],
            'ruang' => $validated['ruang'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('toast_success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return back()->with('toast_success', 'Data siswa berhasil dihapus.');
    }

    public function import(Request $request, StudentExcelImportService $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $result = $importer->import($request->file('file'));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('toast_error', 'Gagal mengimpor file data siswa. Pastikan file tidak rusak dan format kolom sesuai template.');
        }

        if ($result['imported'] === 0 && ! empty($result['errors'])) {
            return back()->with('toast_error', $result['errors'][0]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} data siswa.";
        if ($result['skipped'] > 0) {
            $msg .= " ({$result['skipped']} baris dilewati)";
        }

        return back()->with('toast_success', $msg);
    }

    public function template(StudentExcelImportService $importer): BinaryFileResponse
    {
        $path = $importer->generateTemplate();

        return response()->download($path, 'template_data_siswa.xlsx')->deleteFileAfterSend();
    }
}

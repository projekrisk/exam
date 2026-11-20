<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $teachers = User::with('subjects')
            ->whereIn('role', ['guru', 'proktor'])
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $subjects = Subject::orderBy('name')->get();
        $classes = Student::select('kelas')->distinct()->pluck('kelas')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('teachers.index', compact('teachers', 'subjects', 'classes', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:32', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:guru,proktor'],
            'bindings' => ['nullable', 'array'],
            'bindings.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'bindings.*.classes' => ['nullable', 'array'],
            'bindings.*.classes.*' => ['string', 'max:64'],
        ]);

        $user = User::create([
            'username' => trim($validated['nip']),
            'nip' => trim($validated['nip']),
            'name' => $validated['name'],
            'role' => $validated['role'],
            'password' => User::DEFAULT_TEACHER_PASSWORD,
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $this->syncBindings($user, $validated['bindings'] ?? []);

        return back()->with('toast_success', 'Akun guru berhasil dibuat dengan password default ' . User::DEFAULT_TEACHER_PASSWORD . '.');
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:32', 'unique:users,username,' . $teacher->id],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:guru,proktor'],
            'bindings' => ['nullable', 'array'],
            'bindings.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'bindings.*.classes' => ['nullable', 'array'],
            'bindings.*.classes.*' => ['string', 'max:64'],
        ]);

        $teacher->update([
            'username' => trim($validated['nip']),
            'nip' => trim($validated['nip']),
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        $this->syncBindings($teacher, $validated['bindings'] ?? []);

        return back()->with('toast_success', 'Data guru berhasil diperbarui.');
    }

    private function syncBindings(User $user, array $bindings): void
    {
        DB::table('subject_user')->where('user_id', $user->id)->delete();

        $rows = [];
        $groupNo = 0;
        foreach ($bindings as $b) {
            $subjectId = (int) ($b['subject_id'] ?? 0);
            if ($subjectId <= 0) {
                continue;
            }
            $groupNo++;
            $classes = empty($b['classes']) ? [null] : array_values(array_unique($b['classes']));
            foreach ($classes as $kelas) {
                $key = $subjectId . '|' . $groupNo . '|' . ($kelas ?? '');
                $rows[$key] = [
                    'subject_id' => $subjectId,
                    'user_id' => $user->id,
                    'kelas' => $kelas,
                    'group_key' => $groupNo,
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('subject_user')->insert(array_values($rows));
        }
    }

    public function resetPassword(User $teacher): RedirectResponse
    {
        $teacher->update([
            'password' => User::DEFAULT_TEACHER_PASSWORD,
            'must_change_password' => true,
        ]);

        return back()->with('toast_success', "Password {$teacher->name} direset ke " . User::DEFAULT_TEACHER_PASSWORD . '.');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        $teacher->delete();

        return back()->with('toast_success', 'Data guru berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($q) => $q->where('role', $role))
            ->orderByRaw("CASE role WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 WHEN 'panitia' THEN 3 WHEN 'guru' THEN 4 WHEN 'proktor' THEN 5 ELSE 6 END")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search', 'role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::in(['super_admin', 'panitia', 'guru', 'proktor'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'nip' => $validated['role'] === 'guru' ? $validated['username'] : null,
            'password' => Hash::make($validated['password'] ?: User::DEFAULT_TEACHER_PASSWORD),
            'must_change_password' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Pengguna {$validated['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::in(['super_admin', 'panitia', 'guru', 'proktor'])],
        ]);

        $isActive = $request->boolean('is_active');
        if ($user->id === $request->user()->id) {
            if ($validated['role'] !== 'super_admin') {
                return back()->with('error', 'Anda tidak dapat menurunkan role akun yang sedang digunakan.');
            }
            $isActive = true;
        }

        $user->update([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);

        return back()->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function updatePassword(User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make(User::DEFAULT_TEACHER_PASSWORD),
            'must_change_password' => false,
        ]);

        return back()->with('toast_success', "Password {$user->name} berhasil direset ke " . User::DEFAULT_TEACHER_PASSWORD . '.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}

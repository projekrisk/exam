<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\BlacklistedApp;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'instansi');
        if (! in_array($tab, ['instansi', 'kegiatan', 'applist', 'reset'], true)) {
            $tab = 'instansi';
        }

        $settings = AppSetting::allMap();
        $blacklistedApps = BlacklistedApp::orderBy('app_name')->get();
        $studentCount = Student::count();
        $nonSuperAdminUserCount = User::where('role', '!=', 'super_admin')->count();

        return view('settings.index', compact('tab', 'settings', 'blacklistedApps', 'studentCount', 'nonSuperAdminUserCount'));
    }

    public function updateInstansi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'principal_nip' => ['nullable', 'string', 'max:64'],
            'school_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        AppSetting::setValue('school_name', trim($validated['school_name']));
        AppSetting::setValue('principal_name', trim((string) ($validated['principal_name'] ?? '')));
        AppSetting::setValue('principal_nip', trim((string) ($validated['principal_nip'] ?? '')));

        $currentLogo = AppSetting::getValue('school_logo_path');
        if ($request->boolean('remove_logo') && $currentLogo) {
            Storage::disk('public')->delete($currentLogo);
            AppSetting::setValue('school_logo_path', null);
            $currentLogo = null;
        }

        if ($request->hasFile('school_logo')) {
            if ($currentLogo) {
                Storage::disk('public')->delete($currentLogo);
            }
            $logoPath = $request->file('school_logo')->store('settings', 'public');
            AppSetting::setValue('school_logo_path', $logoPath);
        }

        return redirect()->route('settings.index', ['tab' => 'instansi'])
            ->with('toast_success', 'Pengaturan instansi berhasil disimpan.');
    }

    public function destroyLogo(): RedirectResponse
    {
        $currentLogo = AppSetting::getValue('school_logo_path');
        if ($currentLogo) {
            Storage::disk('public')->delete($currentLogo);
        }
        AppSetting::setValue('school_logo_path', null);

        return redirect()->route('settings.index', ['tab' => 'instansi'])
            ->with('toast_success', 'Logo sekolah berhasil dihapus.');
    }

    public function updateKegiatan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:64'],
        ]);

        AppSetting::setValue('activity_name', trim($validated['activity_name']));
        AppSetting::setValue('academic_year', trim($validated['academic_year']));

        return redirect()->route('settings.index', ['tab' => 'kegiatan'])
            ->with('toast_success', 'Pengaturan kegiatan berhasil disimpan.');
    }

    public function storeBlacklistedApp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'package_name' => ['required', 'string', 'max:191', 'unique:blacklisted_apps,package_name'],
        ], [
            'package_name.unique' => 'Package name tersebut sudah terdaftar dalam blacklist.',
        ]);

        BlacklistedApp::create([
            'app_name' => trim($validated['app_name']),
            'package_name' => trim($validated['package_name']),
            'is_active' => true,
        ]);

        return redirect()->route('settings.index', ['tab' => 'applist'])
            ->with('toast_success', 'Aplikasi berhasil ditambahkan ke daftar blacklist.');
    }

    public function toggleBlacklistedApp(BlacklistedApp $blacklistedApp): RedirectResponse
    {
        $blacklistedApp->update(['is_active' => ! $blacklistedApp->is_active]);

        return redirect()->route('settings.index', ['tab' => 'applist'])
            ->with('toast_success', 'Status aplikasi blacklist diperbarui.');
    }

    public function destroyBlacklistedApp(BlacklistedApp $blacklistedApp): RedirectResponse
    {
        $blacklistedApp->delete();

        return redirect()->route('settings.index', ['tab' => 'applist'])
            ->with('toast_success', 'Aplikasi dihapus dari daftar blacklist.');
    }

    public function resetStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_text' => ['required', 'string'],
        ]);

        if (trim((string) $request->input('confirm_text')) !== 'RESET SISWA') {
            return redirect()->route('settings.index', ['tab' => 'reset'])
                ->with('toast_error', 'Teks konfirmasi harus persis: RESET SISWA.');
        }

        $count = Student::count();
        DB::transaction(function () {
            Student::query()->delete();
        });

        return redirect()->route('settings.index', ['tab' => 'reset'])
            ->with('toast_success', "Berhasil mereset {$count} data siswa.");
    }

    public function resetUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_text' => ['required', 'string'],
        ]);

        if (trim((string) $request->input('confirm_text')) !== 'RESET PENGGUNA') {
            return redirect()->route('settings.index', ['tab' => 'reset'])
                ->with('toast_error', 'Teks konfirmasi harus persis: RESET PENGGUNA.');
        }

        $count = User::where('role', '!=', 'super_admin')->count();
        DB::transaction(function () {
            User::where('role', '!=', 'super_admin')->delete();
        });

        return redirect()->route('settings.index', ['tab' => 'reset'])
            ->with('toast_success', "Berhasil mereset {$count} akun pengguna selain Super Admin.");
    }
}

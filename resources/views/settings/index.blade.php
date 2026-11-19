@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('breadcrumb', 'Pengaturan')

@section('content')
<div class="space-y-3">
  <i data-lucide="settings" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Pengaturan</h1>
  <p class="text-notion-gray text-sm">Pengaturan sistem aplikasi ujian.</p>
</div>

<div class="border-b border-notion-border flex flex-nowrap gap-1 overflow-x-auto whitespace-nowrap -mx-1 px-1">
  <a href="{{ route('settings.index', ['tab' => 'instansi']) }}"
     class="shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'instansi' ? 'border-notion-blue text-notion-blue' : 'border-transparent text-notion-gray hover:text-notion-text' }}">
    <i data-lucide="building-2" class="w-4 h-4"></i> Instansi
  </a>
  <a href="{{ route('settings.index', ['tab' => 'kegiatan']) }}"
     class="shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'kegiatan' ? 'border-notion-blue text-notion-blue' : 'border-transparent text-notion-gray hover:text-notion-text' }}">
    <i data-lucide="calendar-range" class="w-4 h-4"></i> Kegiatan
  </a>
  <a href="{{ route('settings.index', ['tab' => 'applist']) }}"
     class="shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'applist' ? 'border-notion-blue text-notion-blue' : 'border-transparent text-notion-gray hover:text-notion-text' }}">
    <i data-lucide="shield-alert" class="w-4 h-4"></i> App List
    <span class="px-1.5 py-0.5 text-xs rounded bg-notion-hover text-notion-text tnum">{{ $blacklistedApps->where('is_active', true)->count() }}</span>
  </a>
  <a href="{{ route('settings.index', ['tab' => 'reset']) }}"
     class="shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'reset' ? 'border-notion-red text-notion-red' : 'border-transparent text-notion-gray hover:text-notion-red' }}">
    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset Data
  </a>
</div>

@if($tab === 'instansi')
<div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm w-full">
  <form method="POST" action="{{ route('settings.instansi') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1.5">Logo Sekolah</label>
      @if(!empty($settings['school_logo_path']))
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-xl bg-notion-sidebar/50 border border-notion-border">
          <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-16 h-16 rounded-lg bg-white border border-notion-border p-1.5 flex items-center justify-center shrink-0 shadow-xs">
              <img src="{{ asset('uploads/soal/' . $settings['school_logo_path']) }}" alt="Logo Sekolah" class="max-w-full max-h-full object-contain">
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-notion-text">Logo Instansi Aktif</p>
              <p class="text-xs text-notion-gray mt-0.5">Ditampilkan pada kop kartu ujian &amp; dokumen.</p>
            </div>
          </div>
          <button type="button" onclick="document.getElementById('delete-logo-form').requestSubmit()"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-red-200 bg-white text-notion-red hover:bg-notion-red hover:text-white hover:border-notion-red transition-all shrink-0">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            <span>Hapus Logo</span>
          </button>
        </div>
      @endif
      <input type="file" name="school_logo" accept="image/*" class="modern-file" data-placeholder="Pilih logo sekolah (PNG/JPG/WEBP)...">
      <p class="mt-1 text-xs text-notion-gray">Format PNG/JPG/WEBP maksimal 2 MB.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Nama Sekolah / Instansi</label>
      <input type="text" name="school_name" value="{{ old('school_name', $settings['school_name'] ?? 'SMAN 1 Malingping') }}" required
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Nama Kepala Sekolah</label>
      <input type="text" name="principal_name" value="{{ old('principal_name', $settings['principal_name'] ?? '') }}" placeholder="Contoh: Drs. H. Ahmad, M.Pd."
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">NIP Kepala Sekolah</label>
      <input type="text" name="principal_nip" value="{{ old('principal_nip', $settings['principal_nip'] ?? '') }}" placeholder="Contoh: 196801011995031001"
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono bg-white">
    </div>

    <div class="pt-2 flex justify-end">
      <button type="submit" class="px-4 py-2 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover transition-colors flex items-center gap-2">
        <i data-lucide="save" class="w-4 h-4"></i> Simpan Instansi
      </button>
    </div>
  </form>
  @if(!empty($settings['school_logo_path']))
    <form id="delete-logo-form" method="POST" action="{{ route('settings.logo.destroy') }}"
          onsubmit="return handleConfirmSubmit(event, 'Logo sekolah saat ini akan dihapus secara permanen. Lanjutkan?', 'Konfirmasi Hapus Logo', 'Ya, Hapus Logo', 'danger')"
          class="hidden">
      @csrf @method('DELETE')
    </form>
  @endif
</div>
@elseif($tab === 'kegiatan')
<div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm w-full">
  <form method="POST" action="{{ route('settings.kegiatan') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">Nama Kegiatan</label>
      <input type="text" name="activity_name" value="{{ old('activity_name', $settings['activity_name'] ?? 'Penilaian Sumatif Akhir Semester') }}" required
             placeholder="Contoh: Penilaian Sumatif Akhir Semester Ganjil"
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Tahun Ajaran</label>
      <input type="text" name="academic_year" value="{{ old('academic_year', $settings['academic_year'] ?? '2026/2027') }}" required
             placeholder="Contoh: 2026/2027"
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum bg-white">
    </div>
    <div class="pt-2 flex justify-end">
      <button type="submit" class="px-4 py-2 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover transition-colors flex items-center gap-2">
        <i data-lucide="save" class="w-4 h-4"></i> Simpan Kegiatan
      </button>
    </div>
  </form>
</div>
@elseif($tab === 'applist')
<div class="space-y-5">
  <div class="border border-notion-border rounded-lg bg-white p-5 shadow-sm">
    <h2 class="text-base font-semibold mb-1">Tambah Aplikasi Blacklist</h2>
    <p class="text-sm text-notion-gray mb-4">Aplikasi yang masuk daftar aktif akan terdeteksi di HP siswa.</p>
    <form method="POST" action="{{ route('settings.apps.store') }}" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Nama Aplikasi</label>
        <input type="text" name="app_name" required placeholder="Contoh: ChatGPT"
               class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Package Name (Android)</label>
        <input type="text" name="package_name" required placeholder="Contoh: com.openai.chatgpt"
               class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono bg-white">
      </div>
      <div>
        <button type="submit" class="w-full px-4 py-2 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 whitespace-nowrap">
          <i data-lucide="plus" class="w-4 h-4"></i> Tambah ke Blacklist
        </button>
      </div>
    </form>
  </div>

  <div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">

    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
          <tr>
            <th class="py-3 px-4 font-medium">Nama Aplikasi</th>
            <th class="py-3 px-4 font-medium">Package Name</th>
            <th class="py-3 px-4 font-medium">Status</th>
            <th class="py-3 px-4 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-notion-border">
          @forelse($blacklistedApps as $app)
          <tr class="hover:bg-notion-hover/50">
            <td class="py-3 px-4 font-semibold text-notion-text">{{ $app->app_name }}</td>
            <td class="py-3 px-4 font-mono text-sm text-notion-gray">{{ $app->package_name }}</td>
            <td class="py-3 px-4">
              @if($app->is_active)
                <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
              @else
                <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-notion-hover text-notion-gray border border-notion-border">Nonaktif</span>
              @endif
            </td>
            <td class="py-3 px-4 text-right space-x-1">
              <form method="POST" action="{{ route('settings.apps.toggle', $app) }}" class="inline">
                @csrf
                <button type="submit" class="px-2.5 py-1 bg-white border border-notion-border rounded text-sm hover:bg-notion-hover transition-colors">
                  {{ $app->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </form>
              <form method="POST" action="{{ route('settings.apps.destroy', $app) }}" onsubmit="return handleConfirmSubmit(event, 'Hapus {{ $app->app_name }} ({{ $app->package_name }}) dari daftar blacklist?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 text-notion-gray hover:text-notion-red hover:bg-red-50 rounded transition-colors" title="Hapus">
                  <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="py-10 text-center text-notion-gray text-sm">Belum ada aplikasi di daftar blacklist.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="md:hidden divide-y divide-notion-border">
      @forelse($blacklistedApps as $idx => $app)
      <div class="bg-white flex flex-col">
        <div onclick="toggleMobileRow('app', {{ $idx }})" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-notion-hover/50">
          <div class="min-w-0">
            <p class="font-semibold text-sm text-notion-text truncate">{{ $app->app_name }}</p>
            <p class="text-xs text-notion-gray font-mono truncate">{{ $app->package_name }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            @if($app->is_active)
              <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
            @else
              <span class="px-2 py-0.5 rounded text-xs font-medium bg-notion-hover text-notion-gray border border-notion-border">Nonaktif</span>
            @endif
            <i data-lucide="chevron-down" id="app-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
          </div>
        </div>
        <div id="app-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
          <div class="flex justify-between items-center gap-2">
            <span class="text-notion-gray">Package</span>
            <span class="font-mono text-xs text-notion-text break-all text-right">{{ $app->package_name }}</span>
          </div>
          <div class="pt-2 border-t border-notion-border flex justify-end gap-2">
            <form method="POST" action="{{ route('settings.apps.toggle', $app) }}">
              @csrf
              <button type="submit" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium hover:bg-notion-hover transition-colors">
                {{ $app->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
              </button>
            </form>
            <form method="POST" action="{{ route('settings.apps.destroy', $app) }}" onsubmit="return handleConfirmSubmit(event, 'Hapus {{ $app->app_name }} ({{ $app->package_name }}) dari daftar blacklist?')">
              @csrf @method('DELETE')
              <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm inline-flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
              </button>
            </form>
          </div>
        </div>
      </div>
      @empty
      <div class="p-6 text-center text-sm text-notion-gray">Belum ada aplikasi di daftar blacklist.</div>
      @endforelse
    </div>
  </div>
</div>
@elseif($tab === 'reset')
<div class="space-y-5">
  <div class="border border-red-200 rounded-lg bg-white p-6 shadow-sm w-full space-y-4">
    <div class="flex items-start gap-3">
      <div class="p-2.5 rounded-lg bg-notion-redBg text-notion-red shrink-0">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
      </div>
      <div>
        <h2 class="text-base font-semibold text-notion-red">Reset Semua Data Siswa</h2>
        <p class="text-sm text-notion-gray mt-1">
          Tindakan ini akan menghapus <strong>seluruh {{ $studentCount }} data siswa</strong> beserta riwayat sesi &amp; jawaban ujian siswa secara permanen. Gunakan fitur ini saat pergantian tahun ajaran atau sebelum impor data siswa baru.
        </p>
      </div>
    </div>

    <form method="POST" action="{{ route('settings.reset-students') }}"
          onsubmit="return handleConfirmSubmit(event, 'Semua {{ $studentCount }} data siswa beserta riwayat ujiannya akan dihapus permanen. Lanjutkan?', 'Konfirmasi Reset Semua Data Siswa', 'Ya, Reset Semua', 'danger')"
          class="space-y-3 pt-2 border-t border-notion-border">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Ketik <code class="px-1.5 py-0.5 rounded bg-notion-hover font-mono text-notion-red">RESET SISWA</code> untuk konfirmasi</label>
        <input type="text" name="confirm_text" required placeholder="RESET SISWA"
               class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono bg-white">
      </div>
      <button type="submit" class="px-4 py-2 bg-notion-red text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors inline-flex items-center gap-2">
        <i data-lucide="trash-2" class="w-4 h-4"></i> Reset Semua Data Siswa
      </button>
    </form>
  </div>

  <div class="border border-red-200 rounded-lg bg-white p-6 shadow-sm w-full space-y-4">
    <div class="flex items-start gap-3">
      <div class="p-2.5 rounded-lg bg-notion-redBg text-notion-red shrink-0">
        <i data-lucide="user-x" class="w-5 h-5"></i>
      </div>
      <div>
        <h2 class="text-base font-semibold text-notion-red">Reset Pengguna (Selain Super Admin)</h2>
        <p class="text-sm text-notion-gray mt-1">
          Tindakan ini akan menghapus <strong>seluruh {{ $nonSuperAdminUserCount }} akun pengguna</strong> (Panitia, Guru, dan Proktor) secara permanen. Seluruh akun <strong>Super Admin</strong> tetap aman dan tidak akan terhapus.
        </p>
      </div>
    </div>

    <form method="POST" action="{{ route('settings.reset-users') }}"
          onsubmit="return handleConfirmSubmit(event, 'Semua {{ $nonSuperAdminUserCount }} akun pengguna selain Super Admin akan dihapus permanen. Lanjutkan?', 'Konfirmasi Reset Pengguna', 'Ya, Reset Pengguna', 'danger')"
          class="space-y-3 pt-2 border-t border-notion-border">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Ketik <code class="px-1.5 py-0.5 rounded bg-notion-hover font-mono text-notion-red">RESET PENGGUNA</code> untuk konfirmasi</label>
        <input type="text" name="confirm_text" required placeholder="RESET PENGGUNA"
               class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono bg-white">
      </div>
      <button type="submit" class="px-4 py-2 bg-notion-red text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors inline-flex items-center gap-2">
        <i data-lucide="trash-2" class="w-4 h-4"></i> Reset Pengguna Selain Super Admin
      </button>
    </form>
  </div>
</div>
@endif
@endsection

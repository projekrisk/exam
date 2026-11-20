@extends('layouts.app')

@section('title', 'Profil Akun')
@section('breadcrumb', 'Profil')

@section('content')
@php
  $roleLabel = match($user->role) {
    'super_admin' => 'Super Admin',
    'panitia', 'admin' => 'Panitia',
    'guru' => 'Guru Mata Pelajaran',
    'proktor' => 'Proktor',
    default => ucfirst($user->role),
  };
  $roleBadge = match($user->role) {
    'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
    'panitia', 'admin' => 'bg-amber-50 text-amber-700 border-amber-200',
    'guru' => 'bg-blue-50 text-notion-blue border-blue-200',
    default => 'bg-notion-hover text-notion-gray border-notion-border',
  };
@endphp

<div class="space-y-3">
  <i data-lucide="user" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Profil Akun</h1>
  <p class="text-notion-gray text-sm">Informasi identitas akun dan rincian penugasan pada sistem ujian.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm space-y-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-notion-border">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 min-w-0">
      <div class="w-14 h-14 rounded-full bg-notion-sidebar border border-notion-border flex items-center justify-center text-lg font-bold text-notion-text shrink-0">
        {{ strtoupper(substr($user->name, 0, 2)) }}
      </div>
      <div class="min-w-0 space-y-1">
        <h2 class="text-xl font-bold text-notion-text truncate">{{ $user->name }}</h2>
        <div class="flex flex-wrap items-center gap-2">
          <span class="px-2.5 py-0.5 rounded text-xs font-medium border {{ $roleBadge }}">{{ $roleLabel }}</span>
          @if($user->is_active)
            <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
          @else
            <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-notion-hover text-notion-gray border border-notion-border">Nonaktif</span>
          @endif
        </div>
      </div>
    </div>

    <a href="{{ route('password.change') }}" class="w-full sm:w-auto px-4 py-2 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black transition-colors inline-flex items-center justify-center gap-2 shrink-0">
      <i data-lucide="key-round" class="w-4 h-4"></i> Ganti Password
    </a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
    <div class="p-3.5 rounded-lg bg-notion-sidebar/50 border border-notion-border">
      <span class="text-xs font-medium text-notion-gray block mb-1">{{ $user->isGuru() ? 'NIP / Username' : 'Username' }}</span>
      <span class="font-mono font-semibold text-notion-text">{{ $user->username }}</span>
    </div>
    <div class="p-3.5 rounded-lg bg-notion-sidebar/50 border border-notion-border">
      <span class="text-xs font-medium text-notion-gray block mb-1">Peran Akun</span>
      <span class="font-semibold text-notion-text">{{ $roleLabel }}</span>
    </div>
    <div class="p-3.5 rounded-lg bg-notion-sidebar/50 border border-notion-border">
      <span class="text-xs font-medium text-notion-gray block mb-1">Status Keaktifan</span>
      <span class="font-semibold text-notion-text">{{ $user->is_active ? 'Aktif (Dapat Login)' : 'Nonaktif' }}</span>
    </div>
  </div>
</div>

@if($user->isGuru())
  <div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
    <div class="p-4 sm:px-6 border-b border-notion-border bg-notion-sidebar/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <h2 class="text-base font-semibold text-notion-text">Penugasan Mengajar</h2>
        <p class="text-xs text-notion-gray">Daftar mata pelajaran dan kelas yang diampu sesuai pemetaan oleh admin.</p>
      </div>
      <span class="text-xs font-medium px-2.5 py-1 rounded bg-white border border-notion-border text-notion-text tnum w-fit">
        {{ count($bindings) }} Kelompok Mapel • {{ count($allClasses) }} Kelas
      </span>
    </div>

    <div class="divide-y divide-notion-border">
      @forelse($bindings as $b)
        <div class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="font-semibold text-sm text-notion-text">{{ $b['subject_name'] }}</div>
          <div class="flex flex-wrap gap-1.5">
            @forelse($b['classes'] as $kelas)
              <span class="px-2.5 py-0.5 rounded bg-notion-sidebar border border-notion-border text-xs font-medium text-notion-text">{{ $kelas }}</span>
            @empty
              <span class="text-xs text-notion-gray">Belum ada kelas terdaftar</span>
            @endforelse
          </div>
        </div>
      @empty
        <div class="p-8 text-center text-sm text-notion-gray">
          Belum ada penugasan mata pelajaran dan kelas untuk akun ini.
        </div>
      @endforelse
    </div>
  </div>
@elseif($user->isSuperAdmin())
  <div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm space-y-3">
    <h2 class="text-base font-semibold text-notion-text">Wewenang Super Admin</h2>
    <ul class="text-sm text-notion-gray space-y-1.5 list-disc list-inside">
      <li>Mengelola seluruh master data (Siswa, Guru &amp; Proktor, Mata Pelajaran) beserta impor data.</li>
      <li>Mengelola akun pengguna sistem dan pengaturan instansi, kegiatan, aplikasi terlarang, serta reset data.</li>
      <li>Mengelola bank soal, jadwal ujian, pemantauan peserta, rilis nilai, finalisasi nilai, dan pencetakan kartu ujian.</li>
    </ul>
  </div>
@elseif($user->isAdmin())
  <div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm space-y-3">
    <h2 class="text-base font-semibold text-notion-text">Wewenang Panitia Ujian</h2>
    <ul class="text-sm text-notion-gray space-y-1.5 list-disc list-inside">
      <li>Mengelola jadwal ujian terpusat, pemantauan peserta, token ujian, serta merilis nilai ujian.</li>
      <li>Meninjau kesiapan bank soal, mencetak kartu peserta ujian, dan mengelola daftar nilai terfinalisasi.</li>
      <li>Melihat data siswa, guru, dan mata pelajaran secara <em>read-only</em>.</li>
    </ul>
  </div>
@elseif($user->isProktor())
  <div class="border border-notion-border rounded-lg bg-white p-6 shadow-sm space-y-3">
    <h2 class="text-base font-semibold text-notion-text">Wewenang Proktor</h2>
    <ul class="text-sm text-notion-gray space-y-1.5 list-disc list-inside">
      <li>Memantau pelaksanaan ujian, memperbarui token aktif, serta menangani reset perangkat dan buka kunci peserta.</li>
    </ul>
  </div>
@endif
@endsection

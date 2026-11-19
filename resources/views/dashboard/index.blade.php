@extends('layouts.app')

@section('title', 'Dasbor Utama')
@section('breadcrumb', 'Dasbor Utama')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
  <div class="space-y-3">
    <i data-lucide="bar-chart-2" class="w-14 h-14 text-notion-text/80"></i>
    <h1 class="text-3xl sm:text-4xl font-bold text-notion-text tracking-tight">Dasbor</h1>
    <p class="text-notion-gray text-sm sm:text-base leading-relaxed">
      Ringkasan pelaksanaan dan pemantauan ujian.
    </p>
  </div>
  <a href="https://play.google.com/store/apps/details?id=com.smartm1.exambro&hl=id"
     target="_blank"
     rel="noopener noreferrer"
     class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-notion-text text-white text-sm font-medium rounded-md hover:bg-black transition-colors shadow-sm shrink-0 whitespace-nowrap">
    <i data-lucide="download" class="w-4 h-4"></i> Download Apk
  </a>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-5 border-t border-notion-border">
  <div class="flex flex-col">
    <span class="text-sm font-medium text-notion-gray mb-1">Total Siswa Aktif</span>
    <span class="text-2xl sm:text-3xl font-semibold text-notion-text tnum">{{ number_format($stats['students']) }}</span>
  </div>
  <div class="flex flex-col sm:border-l border-notion-border sm:pl-6">
    <span class="text-sm font-medium text-notion-gray mb-1">Bank Soal</span>
    <span class="text-2xl sm:text-3xl font-semibold text-notion-text tnum">{{ number_format($stats['banks']) }}</span>
  </div>
  <div class="flex flex-col border-t sm:border-t-0 sm:border-l border-notion-border pt-4 sm:pt-0 sm:pl-6">
    <span class="text-sm font-medium text-notion-gray mb-1">Ujian Berjalan</span>
    <span class="text-2xl sm:text-3xl font-semibold text-notion-blue tnum">{{ number_format($stats['active_exams']) }}</span>
  </div>
  <div class="flex flex-col border-t sm:border-t-0 sm:border-l border-notion-border pt-4 sm:pt-0 sm:pl-6">
    <span class="text-sm font-medium text-notion-gray mb-1">Menunggu Koreksi</span>
    <span class="text-2xl sm:text-3xl font-semibold text-amber-600 tnum">{{ number_format($stats['pending_essays']) }}</span>
  </div>
</div>

<div class="space-y-4 pt-4">
  <div class="flex items-center justify-between">
    <h2 class="text-lg font-semibold flex items-center gap-2">
      <i data-lucide="calendar-clock" class="w-5 h-5 text-notion-gray"></i>
      Jadwal Ujian Aktif
    </h2>
    <a href="{{ route('exams.index') }}" class="text-sm font-medium text-notion-blue hover:underline">Kelola Semua →</a>
  </div>

  <div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">

    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
          <tr>
            <th class="py-3 px-4 font-medium">Judul Ujian</th>
            <th class="py-3 px-4 font-medium">Mode</th>
            <th class="py-3 px-4 font-medium">Jadwal</th>
            <th class="py-3 px-4 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-notion-border">
          @forelse($activeExams as $exam)
          <tr class="hover:bg-notion-hover/50 transition-colors">
            <td class="py-3 px-4">
              <p class="font-semibold text-notion-text">{{ $exam->title }}</p>
              <p class="text-sm text-notion-gray">{{ $exam->subject->name ?? '-' }} {{ $exam->session_name ? '• '.$exam->session_name : '' }}</p>
            </td>
            <td class="py-3 px-4">
              @if($exam->execution_mode === 'terpusat')
                <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-blue-50 text-notion-blue border border-blue-200">Terpusat</span>
              @else
                <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-notion-hover text-notion-gray border border-notion-border">Mandiri Guru</span>
              @endif
            </td>
            <td class="py-3 px-4 text-sm text-notion-gray tnum">
              {{ $exam->start_at->translatedFormat('d M Y H:i') }} – {{ $exam->end_at->translatedFormat('H:i') }} WIB
            </td>
            <td class="py-3 px-4 text-right space-x-1">
              <a href="{{ route('exams.monitor', $exam) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium hover:bg-notion-hover">
                <i data-lucide="activity" class="w-4 h-4 text-notion-blue"></i> Monitor
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="py-8 text-center text-notion-gray text-sm">Belum ada jadwal ujian aktif.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="md:hidden divide-y divide-notion-border">
      @forelse($activeExams as $idx => $exam)
      <div class="bg-white flex flex-col">
        <div onclick="toggleMobileRow('dash', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50 transition-colors">
          <div>
            <p class="font-semibold text-sm text-notion-text">{{ $exam->title }}</p>
            <p class="text-sm text-notion-gray">{{ $exam->subject->name ?? '-' }}</p>
          </div>
          <i data-lucide="chevron-down" id="dash-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
        </div>
        <div id="dash-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
          <div class="flex justify-between items-center">
            <span class="text-notion-gray">Mode</span>
            <span class="font-medium">{{ $exam->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri Guru' }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-notion-gray">Jadwal</span>
            <span class="tnum">{{ $exam->start_at->translatedFormat('d M H:i') }} – {{ $exam->end_at->translatedFormat('H:i') }} WIB</span>
          </div>
          <div class="pt-2 border-t border-notion-border flex justify-end">
            <a href="{{ route('exams.monitor', $exam) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium hover:bg-notion-hover inline-flex items-center gap-1.5">
              <i data-lucide="activity" class="w-4 h-4 text-notion-blue"></i> Monitor
            </a>
          </div>
        </div>
      </div>
      @empty
      <div class="p-6 text-center text-sm text-notion-gray">Belum ada jadwal ujian aktif.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection

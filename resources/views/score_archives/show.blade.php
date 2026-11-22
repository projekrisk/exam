@extends('layouts.app')

@section('title', 'Rekap Nilai: ' . $scoreArchive->exam_title)
@section('breadcrumb', 'Detail Daftar Nilai')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-notion-border pb-5">
  <div class="space-y-2">
    <a href="{{ route('score-archives.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors shadow-sm">
      <i data-lucide="arrow-left" class="w-4 h-4 text-notion-gray"></i> Kembali
    </a>
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ $scoreArchive->exam_title }}</h1>
    <p class="text-sm text-notion-gray">
      {{ $scoreArchive->subject_name }} • Pembuat Soal: <strong>{{ $scoreArchive->teacher_name }}</strong> • Difinalisasi {{ $scoreArchive->finalized_at?->translatedFormat('d M Y H:i') }} WIB
    </p>
  </div>
  <div class="flex flex-wrap items-center gap-2 shrink-0">
    <a href="{{ route('score-archives.pdf', ['scoreArchive' => $scoreArchive, 'kelas' => $kelas]) }}" target="_blank" class="px-4 py-2 bg-white border border-notion-border text-notion-text rounded-md text-sm font-medium hover:bg-notion-hover inline-flex items-center gap-2">
      <i data-lucide="file-text" class="w-4 h-4 text-notion-red"></i> Unduh PDF
    </a>
    <a href="{{ route('score-archives.export', $scoreArchive) }}" class="px-4 py-2 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black inline-flex items-center gap-2">
      <i data-lucide="download" class="w-4 h-4"></i> Export Excel (.xlsx)
    </a>
  </div>
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 bg-notion-sidebar/40">
    <form method="GET" action="{{ route('score-archives.show', $scoreArchive) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 w-full">
      <div class="relative flex-1 min-w-[200px]">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN siswa..."
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
      </div>
      <div class="sm:w-44 shrink-0">
        <select name="kelas" onchange="this.form.submit()" class="modern-select w-full">
          <option value="">Semua Kelas</option>
          @foreach($classes as $c)
            <option value="{{ $c }}" {{ $kelas === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="px-3.5 py-2 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors whitespace-nowrap shrink-0">
        Filter
      </button>
      @if($search !== '' || $kelas !== '')
        <a href="{{ route('score-archives.show', $scoreArchive) }}" class="px-3 py-2 text-sm text-notion-gray hover:text-notion-text text-center shrink-0">Reset</a>
      @endif
    </form>
  </div>

  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium w-14 text-center">No</th>
          <th class="py-3 px-4 font-medium">NIS</th>
          <th class="py-3 px-4 font-medium">Nama Siswa</th>
          <th class="py-3 px-4 font-medium">Kelas</th>
          <th class="py-3 px-4 font-medium text-right">Objektif</th>
          <th class="py-3 px-4 font-medium text-right">Uraian</th>
          <th class="py-3 px-4 font-medium text-right">Manual</th>
          <th class="py-3 px-4 font-medium text-right">Nilai Akhir</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($items as $idx => $item)
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 text-center text-notion-gray tnum">{{ $items->firstItem() + $idx }}</td>
          <td class="py-3 px-4 font-mono text-sm text-notion-gray">{{ $item->student_nis }}</td>
          <td class="py-3 px-4 font-medium text-notion-text">
            {{ $item->student_name }}
            @if($item->teacher_note)
              <p class="text-xs text-notion-gray font-normal">{{ $item->teacher_note }}</p>
            @endif
          </td>
          <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-notion-hover border border-notion-border text-xs font-medium">{{ $item->student_kelas }}</span></td>
          <td class="py-3 px-4 text-right tnum">{{ (int) round((float) $item->objective_score) }}</td>
          <td class="py-3 px-4 text-right tnum">{{ (int) round((float) $item->essay_score) }}</td>
          <td class="py-3 px-4 text-right tnum text-notion-gray">{{ $item->manual_score !== null ? (int) round((float) $item->manual_score) : '-' }}</td>
          <td class="py-3 px-4 text-right tnum font-bold text-notion-text">{{ (int) round((float) $item->final_score) }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="8" class="py-10 text-center text-notion-gray text-sm">Tidak ada data nilai siswa.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($items as $idx => $item)
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('score', {{ $idx }})" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-notion-hover/50">
        <div class="min-w-0">
          <p class="font-semibold text-sm text-notion-text truncate">{{ $item->student_name }}</p>
          <p class="text-xs text-notion-gray font-mono">{{ $item->student_nis }} · {{ $item->student_kelas }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <span class="font-bold text-sm tnum">{{ (int) round((float) $item->final_score) }}</span>
          <i data-lucide="chevron-down" id="score-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200"></i>
        </div>
      </div>
      <div id="score-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between"><span class="text-notion-gray">Objektif</span><span class="tnum">{{ (int) round((float) $item->objective_score) }}</span></div>
        <div class="flex justify-between"><span class="text-notion-gray">Uraian</span><span class="tnum">{{ (int) round((float) $item->essay_score) }}</span></div>
        <div class="flex justify-between"><span class="text-notion-gray">Manual</span><span class="tnum">{{ $item->manual_score !== null ? (int) round((float) $item->manual_score) : '-' }}</span></div>
        @if($item->teacher_note)
          <div class="text-xs text-notion-gray pt-1 border-t border-notion-border">Catatan: {{ $item->teacher_note }}</div>
        @endif
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Tidak ada data nilai siswa.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$items" />
</div>
@endsection

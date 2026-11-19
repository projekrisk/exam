@extends('layouts.app')

@section('title', 'Daftar Nilai')
@section('breadcrumb', 'Daftar Nilai')

@section('content')
<div class="space-y-3">
  <i data-lucide="award" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Daftar Nilai</h1>
  <p class="text-notion-gray text-sm">Arsip rekap nilai siswa yang telah difinalisasi.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 bg-notion-sidebar/40 rounded-t-lg">
    <form method="GET" action="{{ route('score-archives.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 w-full">
      <div class="relative flex-1 min-w-[200px]">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama ujian, mata pelajaran, atau pembuat soal..."
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
      </div>
      <div class="sm:w-52 shrink-0">
        <select name="subject_id" onchange="this.form.submit()" class="modern-select w-full">
          <option value="">Semua Mata Pelajaran</option>
          @foreach($subjects as $sub)
            <option value="{{ $sub->id }}" {{ (string) $subjectId === (string) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="px-3.5 py-2 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors whitespace-nowrap shrink-0">
        Filter
      </button>
      @if($search !== '' || $subjectId !== '')
        <a href="{{ route('score-archives.index') }}" class="px-3 py-2 text-sm text-notion-gray hover:text-notion-text text-center shrink-0">Reset</a>
      @endif
    </form>
  </div>

  <div class="hidden md:block">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium">Ujian &amp; Mata Pelajaran</th>
          <th class="py-3 px-4 font-medium">Pembuat Soal</th>
          <th class="py-3 px-4 font-medium text-center">Peserta</th>
          <th class="py-3 px-4 font-medium">Finalisasi</th>
          <th class="py-3 px-4 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($archives as $arc)
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4">
            <a href="{{ route('score-archives.show', $arc) }}" class="font-semibold text-notion-text hover:text-notion-blue hover:underline">
              {{ $arc->exam_title }}
            </a>
            <p class="text-sm text-notion-gray">{{ $arc->subject_name }} {{ $arc->session_name ? '• ' . $arc->session_name : '' }}</p>
          </td>
          <td class="py-3 px-4 text-notion-text">{{ $arc->teacher_name }}</td>
          <td class="py-3 px-4 text-center tnum font-medium">{{ $arc->total_participants }}</td>
          <td class="py-3 px-4 text-sm text-notion-gray tnum">
            <div>{{ $arc->finalized_at?->translatedFormat('d M Y H:i') }} WIB</div>
            <span class="inline-flex items-center gap-1 text-xs text-emerald-700 font-medium">
              <i data-lucide="lock" class="w-3 h-3"></i> Terkunci
            </span>
          </td>
          <td class="py-3 px-4 text-right">
            <div class="relative inline-block text-left">
              <button type="button" onclick="toggleArchiveMenu(event, 'archive-menu-{{ $arc->id }}')" class="p-1.5 rounded-md border border-notion-border bg-white text-notion-gray hover:text-notion-text hover:bg-notion-hover transition-colors" title="Menu Aksi">
                <i data-lucide="more-vertical" class="w-4 h-4"></i>
              </button>
              <div id="archive-menu-{{ $arc->id }}" class="archive-action-menu hidden absolute right-0 mt-1.5 w-48 bg-white border border-notion-border rounded-lg shadow-lg py-1.5 z-40 text-left">
                <a href="{{ route('score-archives.show', $arc) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="eye" class="w-4 h-4 text-notion-blue"></i> Lihat Nilai
                </a>
                <a href="{{ route('score-archives.export', $arc) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="download" class="w-4 h-4 text-notion-gray"></i> Export Excel (.xlsx)
                </a>
                <div class="my-1 border-t border-notion-border"></div>
                <form method="POST" action="{{ route('score-archives.destroy', $arc) }}" onsubmit="return handleConfirmSubmit(event, 'Apakah Anda yakin ingin menghapus arsip nilai {{ $arc->exam_title }} dari daftar nilai Anda? Tindakan ini tidak dapat dibatalkan.', 'Konfirmasi Hapus Arsip Nilai', 'Ya, Hapus', 'danger')">
                  @csrf @method('DELETE')
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-red hover:bg-red-50 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                  </button>
                </form>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="py-10 text-center text-notion-gray text-sm">Belum ada nilai yang difinalisasi.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($archives as $idx => $arc)
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('arc', {{ $idx }})" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-notion-hover/50">
        <div class="min-w-0">
          <p class="font-semibold text-sm text-notion-text truncate">{{ $arc->exam_title }}</p>
          <p class="text-xs text-notion-gray truncate">{{ $arc->subject_name }} · {{ $arc->total_participants }} peserta</p>
        </div>
        <i data-lucide="chevron-down" id="arc-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="arc-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between">
          <span class="text-notion-gray">Pembuat Soal</span>
          <span class="font-medium">{{ $arc->teacher_name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-notion-gray">Difinalisasi</span>
          <span class="tnum">{{ $arc->finalized_at?->translatedFormat('d M Y H:i') }} WIB</span>
        </div>
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          <a href="{{ route('score-archives.show', $arc) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Lihat Nilai</a>
          <a href="{{ route('score-archives.export', $arc) }}" class="px-3 py-1.5 bg-notion-text text-white rounded text-sm font-medium">Export .xlsx</a>
          <form method="POST" action="{{ route('score-archives.destroy', $arc) }}" onsubmit="return handleConfirmSubmit(event, 'Apakah Anda yakin ingin menghapus arsip nilai {{ $arc->exam_title }} dari daftar nilai Anda? Tindakan ini tidak dapat dibatalkan.', 'Konfirmasi Hapus Arsip Nilai', 'Ya, Hapus', 'danger')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm font-medium inline-flex items-center gap-1">
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
            </button>
          </form>
        </div>
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada nilai yang difinalisasi.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$archives" />
</div>

@push('scripts')
<script>
  function toggleArchiveMenu(event, menuId) {
    event.stopPropagation();
    const target = document.getElementById(menuId);
    if (!target) return;
    const isHidden = target.classList.contains('hidden');
    document.querySelectorAll('.archive-action-menu').forEach(m => m.classList.add('hidden'));
    if (isHidden) target.classList.remove('hidden');
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.archive-action-menu').forEach(m => m.classList.add('hidden'));
  });
</script>
@endpush
@endsection

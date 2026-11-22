@extends('layouts.app')

@section('title', 'Jadwal Ujian')
@section('breadcrumb', 'Jadwal Ujian')

@section('content')
<div class="space-y-3">
  <i data-lucide="calendar-clock" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Jadwal Ujian</h1>
  <p class="text-notion-gray text-sm">Pengelolaan jadwal pelaksanaan dan pemantauan ujian.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-notion-sidebar/40 rounded-t-lg">
    <form method="GET" action="{{ route('exams.index') }}" class="flex flex-col sm:flex-row gap-2 w-full lg:flex-1">
      <div class="relative w-full sm:flex-1">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama ujian, mapel, atau sesi..."
               onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}"
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue">
      </div>
      <div class="w-full sm:w-40 shrink-0">
        <select name="mode" onchange="this.form.submit()" class="modern-select">
          <option value="">Semua Mode</option>
          <option value="terpusat" {{ $mode === 'terpusat' ? 'selected' : '' }}>Terpusat</option>
          <option value="mandiri" {{ $mode === 'mandiri' ? 'selected' : '' }}>Mandiri</option>
        </select>
      </div>
      <div class="w-full sm:w-40 shrink-0">
        <select name="status" onchange="this.form.submit()" class="modern-select">
          <option value="">Semua Status</option>
          <option value="ongoing" {{ $status === 'ongoing' ? 'selected' : '' }}>Berlangsung</option>
          <option value="waiting" {{ $status === 'waiting' ? 'selected' : '' }}>Menunggu</option>
          <option value="finished" {{ $status === 'finished' ? 'selected' : '' }}>Selesai</option>
          <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
        </select>
      </div>
      <button type="submit" class="hidden" aria-hidden="true"></button>
    </form>

    @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
    <a href="{{ route('exams.create') }}" class="w-full lg:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i> Jadwalkan Ujian
    </a>
    @endif
  </div>

  <div class="hidden md:block">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium">Ujian &amp; Mapel</th>
          <th class="py-3 px-4 font-medium">Mode</th>
          <th class="py-3 px-4 font-medium">Waktu &amp; Durasi</th>
          <th class="py-3 px-4 font-medium">Status</th>
          <th class="py-3 px-4 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($exams as $exam)
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4">
            <a href="{{ route('exams.monitor', $exam) }}" class="font-semibold text-notion-text hover:text-notion-blue hover:underline transition-colors">
              {{ $exam->title }}
            </a>
            <p class="text-sm text-notion-gray">{{ $exam->subject->name ?? '-' }} • {{ $exam->attempts_count }} peserta {{ $exam->session_name ? '• '.$exam->session_name : '' }}</p>
          </td>
          <td class="py-3 px-4">
            <span class="px-2.5 py-0.5 rounded text-sm font-medium {{ $exam->execution_mode === 'terpusat' ? 'bg-blue-50 text-notion-blue border border-blue-200' : 'bg-notion-hover text-notion-gray border border-notion-border' }}">
              {{ $exam->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri' }}
            </span>
          </td>
          <td class="py-3 px-4 text-sm text-notion-gray tnum">
            <div>{{ $exam->start_at->translatedFormat('d M Y H:i') }} – {{ $exam->end_at->translatedFormat('H:i') }} WIB</div>
            <div>Durasi: {{ $exam->duration_minutes }} menit</div>
          </td>
          <td class="py-3 px-4">
            @php $badge = $exam->statusBadge(); @endphp
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="px-2.5 py-0.5 rounded text-sm font-medium {{ $badge['class'] }}">
                {{ $badge['label'] }}
              </span>
              @if($exam->scores_finalized)
                <span class="px-2 py-0.5 rounded text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                  <i data-lucide="lock" class="w-3 h-3"></i> Nilai Difinalisasi
                </span>
              @elseif($exam->scores_released)
                <span class="px-2 py-0.5 rounded text-sm font-medium bg-blue-50 text-notion-blue border border-blue-200">Nilai Dirilis</span>
              @endif
            </div>
          </td>
          <td class="py-3 px-4 text-right">
            <div class="relative inline-block text-left">
              <button type="button" onclick="toggleActionMenu(event, 'exam-menu-{{ $exam->id }}')" class="p-1.5 rounded-md border border-notion-border bg-white text-notion-gray hover:text-notion-text hover:bg-notion-hover transition-colors" title="Menu Aksi">
                <i data-lucide="more-vertical" class="w-4 h-4"></i>
              </button>
              <div id="exam-menu-{{ $exam->id }}" class="exam-action-menu hidden absolute right-0 mt-1.5 w-48 bg-white border border-notion-border rounded-lg shadow-lg py-1.5 z-40 text-left">
                <a href="{{ route('exams.monitor', $exam) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="activity" class="w-4 h-4 text-notion-blue"></i> Monitor &amp; Token
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
                @php
                  $examStatus = $exam->effectiveStatus();
                  $isFinished = $examStatus === 'finished';
                  $isScheduleCreator = (int) $exam->created_by === (int) auth()->id();
                  $isGuruLockedBeforeRelease = auth()->user()->isGuru() && ! $exam->scores_released;
                  $canEditSchedule = auth()->user()->isSuperAdmin() || ($isScheduleCreator && (auth()->user()->isAdmin()
                    ? in_array($examStatus, ['draft', 'waiting', 'finished'], true)
                    : ($isFinished && ! $isGuruLockedBeforeRelease)));
                  $canGradeAndExport = $isFinished && ! $isGuruLockedBeforeRelease;
                @endphp
                @if($canEditSchedule)
                <a href="{{ route('exams.edit', $exam) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="edit-2" class="w-4 h-4 text-notion-gray"></i> Edit Jadwal
                </a>
                @else
                <button type="button" onclick="showNoticeModal('{{ ! $isScheduleCreator ? 'Akses Dibatasi' : ($isFinished && $isGuruLockedBeforeRelease ? 'Menunggu Rilis Nilai' : 'Tidak Dapat Diedit') }}', '{{ ! $isScheduleCreator ? 'Hanya pembuat jadwal ujian ini yang dapat mengedit jadwal.' : ($examStatus === 'ongoing' ? 'Jadwal ujian yang sedang berlangsung tidak dapat diedit.' : ($isFinished && $isGuruLockedBeforeRelease ? 'Edit jadwal hanya dapat dilakukan setelah panitia merilis nilai.' : 'Edit jadwal hanya dapat dilakukan setelah waktu ujian selesai.')) }}')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-gray hover:bg-notion-hover transition-colors">
                  <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Jadwal
                </button>
                @endif
                @if($isFinished && $exam->scores_finalized)
                <a href="{{ route('score-archives.index') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-700 hover:bg-emerald-50 transition-colors font-medium">
                  <i data-lucide="award" class="w-4 h-4"></i> Lihat Daftar Nilai
                </a>
                @elseif($isFinished && ($exam->execution_mode === 'mandiri' || $exam->scores_released))
                <a href="{{ route('exams.grade', $exam) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="check-square" class="w-4 h-4 text-emerald-600"></i> Input &amp; Finalisasi Nilai
                </a>
                @else
                <button type="button" onclick="showNoticeModal('{{ $isFinished && $exam->execution_mode !== 'mandiri' && !$exam->scores_released ? 'Menunggu Rilis Nilai' : 'Ujian Belum Selesai' }}', '{{ $isFinished && $exam->execution_mode !== 'mandiri' && !$exam->scores_released ? 'Input nilai dan finalisasi hanya dapat dilakukan setelah panitia merilis nilai.' : 'Input nilai hanya dapat dilakukan setelah waktu ujian selesai.' }}')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-gray hover:bg-notion-hover transition-colors">
                  <i data-lucide="check-square" class="w-4 h-4"></i> Input &amp; Finalisasi Nilai
                </button>
                @endif
                @endif
                @if(auth()->user()->isAdmin() && $exam->effectiveStatus() === 'finished' && !$exam->scores_released)
                <form method="POST" action="{{ route('exams.release-scores', $exam) }}" onsubmit="return handleConfirmSubmit(event, 'Rilis nilai ujian {{ $exam->title }} agar dapat dilihat oleh siswa?', 'Konfirmasi Rilis Nilai', 'Ya, Rilis Nilai', 'info')">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-blue hover:bg-blue-50 transition-colors font-medium">
                    <i data-lucide="send" class="w-4 h-4"></i> Rilis Nilai
                  </button>
                </form>
                @endif
                @if((auth()->user()->isAdmin() || (int) $exam->created_by === (int) auth()->id()) && $exam->effectiveStatus() !== 'ongoing')
                <div class="my-1 border-t border-notion-border"></div>
                <form method="POST" action="{{ route('exams.destroy', $exam) }}" onsubmit="return handleConfirmSubmit(event, 'Jadwal ujian {{ $exam->title }} beserta seluruh sesi pesertanya akan dihapus permanen.', 'Konfirmasi Hapus Jadwal', 'Ya, Hapus', 'danger')">
                  @csrf @method('DELETE')
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-red hover:bg-red-50 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Jadwal
                  </button>
                </form>
                @endif
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="py-10 text-center text-notion-gray text-sm">Belum ada jadwal ujian.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($exams as $idx => $exam)
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('exam', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50">
        <div>
          <a href="{{ route('exams.monitor', $exam) }}" onclick="event.stopPropagation()" class="font-semibold text-sm text-notion-text hover:text-notion-blue hover:underline">{{ $exam->title }}</a>
          <p class="text-sm text-notion-gray">{{ $exam->subject->name ?? '-' }} • {{ $exam->statusBadge()['label'] }}</p>
        </div>
        <i data-lucide="chevron-down" id="exam-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="exam-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Mode</span>
          <span class="font-medium">{{ $exam->execution_mode === 'terpusat' ? 'Terpusat' : 'Mandiri' }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Jadwal</span>
          <span class="tnum">{{ $exam->start_at->translatedFormat('d M H:i') }} WIB ({{ $exam->duration_minutes }}m)</span>
        </div>
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          <a href="{{ route('exams.monitor', $exam) }}" class="px-3 py-1.5 bg-notion-blue text-white rounded text-sm font-medium">Monitor &amp; Token</a>
          @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
            @php
              $mobileStatus = $exam->effectiveStatus();
              $mobileCreator = (int) $exam->created_by === (int) auth()->id();
              $mobileGuruLocked = auth()->user()->isGuru() && ! $exam->scores_released;
              $canEditMobile = auth()->user()->isSuperAdmin() || ($mobileCreator && (auth()->user()->isAdmin()
                ? in_array($mobileStatus, ['draft', 'waiting', 'finished'], true)
                : ($mobileStatus === 'finished' && ! $mobileGuruLocked)));
              $canGradeExportMobile = $mobileStatus === 'finished' && ! $mobileGuruLocked;
            @endphp
            @if($canEditMobile)
            <a href="{{ route('exams.edit', $exam) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Edit</a>
            @else
            <button type="button" onclick="showNoticeModal('{{ ! $mobileCreator ? 'Akses Dibatasi' : ($mobileStatus === 'finished' && $mobileGuruLocked ? 'Menunggu Rilis Nilai' : 'Tidak Dapat Diedit') }}', '{{ ! $mobileCreator ? 'Hanya pembuat jadwal ujian ini yang dapat mengedit jadwal.' : ($mobileStatus === 'ongoing' ? 'Jadwal ujian yang sedang berlangsung tidak dapat diedit.' : ($mobileStatus === 'finished' && $mobileGuruLocked ? 'Edit jadwal hanya dapat dilakukan setelah panitia merilis nilai.' : 'Edit jadwal hanya dapat dilakukan setelah ujian selesai.')) }}')" class="px-3 py-1.5 bg-notion-hover border border-notion-border text-notion-gray rounded text-sm font-medium">Edit</button>
            @endif
            @if($mobileStatus === 'finished' && $exam->scores_finalized)
            <a href="{{ route('score-archives.index') }}" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-sm font-medium">Daftar Nilai</a>
            @elseif($mobileStatus === 'finished' && ($exam->execution_mode === 'mandiri' || $exam->scores_released))
            <a href="{{ route('exams.grade', $exam) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Input &amp; Finalisasi</a>
            @else
            <button type="button" onclick="showNoticeModal('{{ $mobileStatus === 'finished' && $exam->execution_mode !== 'mandiri' && !$exam->scores_released ? 'Menunggu Rilis Nilai' : 'Ujian Belum Selesai' }}', '{{ $mobileStatus === 'finished' && $exam->execution_mode !== 'mandiri' && !$exam->scores_released ? 'Input nilai hanya dapat dilakukan setelah panitia merilis nilai.' : 'Input nilai hanya dapat dilakukan setelah ujian selesai.' }}')" class="px-3 py-1.5 bg-notion-hover border border-notion-border text-notion-gray rounded text-sm font-medium">Input Nilai</button>
            @endif
            @if((auth()->user()->isAdmin() || $mobileCreator) && $mobileStatus !== 'ongoing')
            <form method="POST" action="{{ route('exams.destroy', $exam) }}" onsubmit="return handleConfirmSubmit(event, 'Jadwal ujian {{ $exam->title }} beserta seluruh sesi pesertanya akan dihapus permanen.', 'Konfirmasi Hapus Jadwal', 'Ya, Hapus', 'danger')">
              @csrf @method('DELETE')
              <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm font-medium inline-flex items-center gap-1">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
              </button>
            </form>
            @endif
          @endif
        </div>
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada jadwal ujian.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$exams" />
</div>

@push('scripts')
<script>
  function toggleActionMenu(event, menuId) {
    event.stopPropagation();
    const target = document.getElementById(menuId);
    if (!target) return;
    const isHidden = target.classList.contains('hidden');
    document.querySelectorAll('.exam-action-menu').forEach(m => m.classList.add('hidden'));
    if (isHidden) target.classList.remove('hidden');
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.exam-action-menu').forEach(m => m.classList.add('hidden'));
  });
</script>
@endpush
@endsection

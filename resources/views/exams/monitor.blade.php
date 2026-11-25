@extends('layouts.app')

@section('title', 'Monitor: ' . $exam->title)
@section('breadcrumb', 'Monitoring Ujian')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-notion-border pb-5">
  <div class="space-y-2">
    <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors shadow-sm">
      <i data-lucide="arrow-left" class="w-4 h-4 text-notion-gray"></i> Kembali
    </a>
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ $exam->title }}</h1>
    <div class="flex flex-wrap items-center gap-2 text-sm text-notion-gray">
      <span>Token Aktif:</span>
      @if($exam->effectiveStatus() === 'finished')
        <span id="active-token-value" class="font-mono font-bold text-notion-gray bg-notion-hover px-2.5 py-0.5 rounded border border-notion-border">-</span>
      @else
        <span id="active-token-value" class="font-mono font-bold text-notion-text tracking-widest bg-notion-hover px-2.5 py-0.5 rounded border border-notion-border transition-colors duration-300">{{ $exam->require_token ? $exam->token : 'TANPA TOKEN' }}</span>
        @if($exam->require_token)
          <form method="POST" action="{{ route('exams.regenerate-token', $exam) }}" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-white border border-notion-border rounded text-sm text-notion-text hover:bg-notion-hover transition-colors" title="Acak Token Baru">
              <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-notion-blue"></i> Ganti Token
            </button>
          </form>
          @if($exam->token_refresh_minutes)
            <span class="px-2 py-0.5 rounded text-sm bg-blue-50 text-notion-blue border border-blue-200">Auto / {{ $exam->token_refresh_minutes }} menit</span>
            @if($tokenRemainingSeconds !== null)
              <span id="token-countdown-badge" class="px-2 py-0.5 rounded text-sm font-mono font-semibold bg-amber-50 text-amber-800 border border-amber-200 tnum">
                Berganti dalam <span id="token-countdown">{{ sprintf('%02d:%02d', intdiv($tokenRemainingSeconds, 60), $tokenRemainingSeconds % 60) }}</span>
              </span>
            @endif
          @endif
        @endif
      @endif
      <span>• {{ $exam->subject->name ?? '-' }}</span>
    </div>
  </div>
  <div class="flex flex-wrap gap-2">
    @if(auth()->user()->isAdmin() && $exam->effectiveStatus() === 'finished' && !$exam->scores_released)
    <form method="POST" action="{{ route('exams.release-scores', $exam) }}" onsubmit="return handleConfirmSubmit(event, 'Rilis nilai ujian {{ $exam->title }} agar dapat dilihat oleh siswa?', 'Konfirmasi Rilis Nilai', 'Ya, Rilis Nilai', 'info')">
      @csrf
      <button type="submit" class="px-3 py-1.5 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover flex items-center gap-1.5">
        <i data-lucide="send" class="w-4 h-4"></i> Rilis Nilai
      </button>
    </form>
    @elseif($exam->scores_released)
      <span class="px-3 py-1.5 bg-blue-50 border border-blue-200 text-notion-blue rounded-md text-sm font-medium inline-flex items-center gap-1.5">
        <i data-lucide="check-circle-2" class="w-4 h-4"></i> Nilai Dirilis
      </span>
    @endif
    @php
      $canInputMonitor = (auth()->user()->isAdmin() || auth()->user()->isGuru())
        && $exam->effectiveStatus() === 'finished'
        && ($exam->execution_mode === 'mandiri' || $exam->scores_released);
    @endphp
    @if($canInputMonitor)
      @if($exam->scores_finalized)
        <a href="{{ route('score-archives.index') }}" class="px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-md text-sm font-medium hover:bg-emerald-100 flex items-center gap-1.5">
          <i data-lucide="award" class="w-4 h-4"></i> Daftar Nilai
        </a>
      @else
        <a href="{{ route('exams.grade', $exam) }}" class="px-3 py-1.5 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700 flex items-center gap-1.5">
          <i data-lucide="edit-3" class="w-4 h-4"></i> Input Nilai
        </a>
      @endif
    @endif
    @if($exam->effectiveStatus() !== 'finished')
    <a href="{{ route('exams.monitor', $exam) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover flex items-center gap-1.5">
      <i data-lucide="refresh-cw" class="w-4 h-4"></i> Muat Ulang
    </a>
    @endif
    @php
      $canExportMonitor = $exam->effectiveStatus() === 'finished' && ($exam->execution_mode === 'mandiri' || !auth()->user()->isGuru() || $exam->scores_released);
    @endphp
    @if($canExportMonitor)
    <a href="{{ route('exams.export', $exam) }}" class="px-3 py-1.5 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black flex items-center gap-1.5">
      <i data-lucide="download" class="w-4 h-4"></i> Unduh
    </a>
    @else
    <button type="button" onclick="showNoticeModal('{{ $exam->effectiveStatus() === 'finished' ? 'Menunggu Rilis Nilai' : 'Ujian Belum Selesai' }}', '{{ $exam->effectiveStatus() === 'finished' ? 'Nilai belum dapat diunduh sebelum panitia merilis nilai.' : 'Nilai belum dapat diunduh sebelum ujian selesai.' }}')" class="px-3 py-1.5 bg-notion-hover border border-notion-border text-notion-gray rounded-md text-sm font-medium flex items-center gap-1.5">
      <i data-lucide="download" class="w-4 h-4"></i> Unduh
    </button>
    @endif
  </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
  <div class="bg-white border border-notion-border rounded-lg p-4">
    <p class="text-sm text-notion-gray">Total Peserta</p>
    <p class="text-2xl font-bold tnum mt-1">{{ $summary['total'] }}</p>
  </div>
  <div class="bg-white border border-notion-border rounded-lg p-4">
    <p class="text-sm text-notion-gray">Belum Mulai</p>
    <p class="text-2xl font-bold text-notion-gray tnum mt-1">{{ $summary['not_started'] }}</p>
  </div>
  <div class="bg-white border border-notion-border rounded-lg p-4">
    <p class="text-sm text-notion-gray">Mengerjakan</p>
    <p class="text-2xl font-bold text-notion-blue tnum mt-1">{{ $summary['in_progress'] }}</p>
  </div>
  <div class="bg-white border border-notion-border rounded-lg p-4">
    <p class="text-sm text-notion-gray">Pelanggaran</p>
    <p class="text-2xl font-bold text-notion-red tnum mt-1">{{ $summary['locked'] }}</p>
  </div>
  <div class="bg-white border border-notion-border rounded-lg p-4">
    <p class="text-sm text-notion-gray">Selesai</p>
    <p class="text-2xl font-bold text-emerald-700 tnum mt-1">{{ $summary['submitted'] }}</p>
  </div>
</div>

<form method="GET" action="{{ route('exams.monitor', $exam) }}" class="bg-white border border-notion-border rounded-lg p-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
  <div class="flex-1 min-w-0">
    <label class="block text-xs font-medium text-notion-gray mb-1">Cari Peserta</label>
    <div class="relative">
      <i data-lucide="search" class="w-4 h-4 text-notion-gray absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
      <input type="text" name="search" value="{{ $search }}" placeholder="Nama siswa atau NIS..."
             class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-notion-blue">
    </div>
  </div>
  <div class="w-full sm:w-40">
    <label class="block text-xs font-medium text-notion-gray mb-1">Status</label>
    <select name="status" class="modern-select w-full">
      <option value="">Semua Status</option>
      <option value="not_started" {{ ($statusFilter ?? '') === 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
      <option value="in_progress" {{ ($statusFilter ?? '') === 'in_progress' ? 'selected' : '' }}>Mengerjakan</option>
      <option value="locked" {{ ($statusFilter ?? '') === 'locked' ? 'selected' : '' }}>Terkunci</option>
      <option value="submitted" {{ ($statusFilter ?? '') === 'submitted' ? 'selected' : '' }}>Selesai</option>
    </select>
  </div>
  <div class="w-full sm:w-40">
    <label class="block text-xs font-medium text-notion-gray mb-1">Kelas</label>
    <select name="kelas" class="modern-select w-full">
      <option value="">Semua Kelas</option>
      @foreach($classes as $c)
        <option value="{{ $c }}" {{ $kelas === $c ? 'selected' : '' }}>{{ $c }}</option>
      @endforeach
    </select>
  </div>
  <div class="w-full sm:w-40">
    <label class="block text-xs font-medium text-notion-gray mb-1">Ruang</label>
    <select name="ruang" class="modern-select w-full">
      <option value="">Semua Ruang</option>
      @foreach($rooms as $r)
        <option value="{{ $r }}" {{ $ruang === $r ? 'selected' : '' }}>{{ $r }}</option>
      @endforeach
    </select>
  </div>
  <div class="flex items-center gap-2">
    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover transition-colors">
      Terapkan
    </button>
    @if($search !== '' || $kelas !== '' || $ruang !== '' || ($statusFilter ?? '') !== '')
      <a href="{{ route('exams.monitor', $exam) }}" class="px-3 py-2 bg-white border border-notion-border rounded-md text-sm text-notion-gray hover:bg-notion-hover transition-colors">
        Reset
      </a>
    @endif
  </div>
</form>

<div class="border border-notion-border rounded-lg bg-white shadow-sm">

  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium">Siswa</th>
          <th class="py-3 px-4 font-medium">Kelas / Ruang</th>
          <th class="py-3 px-4 font-medium">Progres Jawaban</th>
          <th class="py-3 px-4 font-medium">Status</th>
          <th class="py-3 px-4 font-medium text-center">Pelanggaran</th>
          <th class="py-3 px-4 font-medium">Sinkron Terakhir</th>
          <th class="py-3 px-4 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($students as $student)
        @php
          $att = $attemptsByStudent->get($student->id);
          $answered = (int) ($att?->answered_count ?? 0);
          $pct = $totalQuestions > 0 ? min(100, (int) round(($answered / $totalQuestions) * 100)) : 0;
        @endphp
        <tr class="hover:bg-notion-hover/50">
          <td class="py-3 px-4">
            <p class="font-semibold">{{ $student->name }}</p>
            <p class="text-sm text-notion-gray font-mono">NIS: {{ $student->nis }}</p>
          </td>
          <td class="py-3 px-4 text-notion-gray">
            <p class="text-notion-text font-medium">{{ $student->kelas }}</p>
            <p class="text-xs text-notion-gray">{{ $student->ruang ?: '-' }}</p>
          </td>
          <td class="py-3 px-4 min-w-[160px]">
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="font-medium text-notion-text tnum">{{ $answered }} / {{ $totalQuestions }} soal</span>
              <span class="text-notion-gray tnum">{{ $pct }}%</span>
            </div>
            <div class="w-full h-1.5 bg-notion-hover rounded-full overflow-hidden">
              <div class="h-full {{ $pct >= 100 ? 'bg-emerald-600' : 'bg-notion-blue' }} rounded-full" style="width: {{ $pct }}%"></div>
            </div>
          </td>
          <td class="py-3 px-4">
            @if(!$att)
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-notion-hover text-notion-gray border border-notion-border">Belum Mulai</span>
            @elseif($att->status === 'in_progress')
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-blue-50 text-notion-blue border border-blue-200">Mengerjakan</span>
            @elseif($att->status === 'locked')
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-notion-redBg text-notion-red border border-red-200">Terkunci</span>
            @else
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai ({{ (int) round((float) $att->final_score) }})</span>
            @endif
          </td>
          <td class="py-3 px-4 text-center tnum {{ ($att?->violation_count ?? 0) > 0 ? 'text-notion-red font-bold' : 'text-notion-gray' }}">
            @if($att)
              {{ $att->violation_count }} / {{ $exam->max_violations }}
            @else
              -
            @endif
          </td>
          <td class="py-3 px-4 text-sm text-notion-gray tnum">
            @if(!$att)
              Belum login
            @else
              {{ $att->last_sync_at ? $att->last_sync_at->diffForHumans() : 'Belum sync' }}
            @endif
          </td>
          <td class="py-3 px-4 text-right">
            @if($att)
            <div class="relative inline-block text-left">
              <button type="button" onclick="toggleMonitorMenu(event, 'mon-menu-{{ $att->id }}')" class="p-1.5 rounded-md border border-notion-border bg-white hover:bg-notion-hover text-notion-gray transition-colors" title="Menu Aksi">
                <i data-lucide="more-vertical" class="w-4 h-4"></i>
              </button>
              <div id="mon-menu-{{ $att->id }}" class="mon-action-menu hidden fixed w-52 bg-white border border-notion-border rounded-lg shadow-lg z-50 py-1 text-left">
                @if(!in_array($att->status, ['submitted', 'auto_submitted']))
                <form method="POST" action="{{ route('attempts.reset-device', $att) }}">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                    <i data-lucide="smartphone" class="w-4 h-4 text-notion-gray"></i> Reset Login
                  </button>
                </form>
                @if($att->status === 'locked')
                <form method="POST" action="{{ route('attempts.unlock', $att) }}">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-amber-700 hover:bg-amber-50 transition-colors font-medium">
                    <i data-lucide="unlock" class="w-4 h-4"></i> Buka Kunci (Unlock)
                  </button>
                </form>
                @endif
                <form method="POST" action="{{ route('attempts.force-submit', $att) }}" onsubmit="return handleConfirmSubmit(event, 'Paksa selesaikan ujian {{ $student->name }} sekarang?', 'Konfirmasi Paksa Selesai', 'Ya, Selesaikan')">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-red hover:bg-notion-redBg transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Paksa Selesai
                  </button>
                </form>
                @else
                <div class="px-3.5 py-2">
                  <p class="text-xs text-notion-gray">Kode Bukti Selesai</p>
                  <p class="font-mono text-sm font-semibold text-notion-text mt-0.5 select-all">{{ $att->receipt_code ?: '-' }}</p>
                </div>
                @endif
              </div>
            </div>
            @else
              <span class="text-xs text-notion-gray">-</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="py-10 text-center text-notion-gray text-sm">Tidak ada data peserta yang sesuai.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($students as $idx => $student)
    @php
      $att = $attemptsByStudent->get($student->id);
      $answered = (int) ($att?->answered_count ?? 0);
      $pct = $totalQuestions > 0 ? min(100, (int) round(($answered / $totalQuestions) * 100)) : 0;
    @endphp
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('mon', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50">
        <div>
          <p class="font-semibold text-sm">{{ $student->name }}</p>
          <p class="text-sm text-notion-gray">{{ $student->kelas }}{{ $student->ruang ? ' • ' . $student->ruang : '' }} • {{ !$att ? 'Belum Mulai' : ($answered . '/' . $totalQuestions . ' soal (' . $pct . '%)') }}</p>
        </div>
        <i data-lucide="chevron-down" id="mon-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="mon-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Status</span>
          <span class="font-medium">{{ !$att ? 'Belum Mulai' : ($att->status === 'in_progress' ? 'Mengerjakan' : ($att->status === 'locked' ? 'Terkunci' : 'Selesai')) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Progres</span>
          <span class="font-semibold tnum">{{ $answered }} / {{ $totalQuestions }} soal ({{ $pct }}%)</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Pelanggaran</span>
          <span class="font-semibold tnum">{{ $att ? ($att->violation_count . ' / ' . $exam->max_violations) : '-' }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Sinkron</span>
          <span>{{ !$att ? 'Belum login' : ($att->last_sync_at ? $att->last_sync_at->diffForHumans() : 'Belum sync') }}</span>
        </div>
        @if($att && !in_array($att->status, ['submitted', 'auto_submitted']))
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          <form method="POST" action="{{ route('attempts.reset-device', $att) }}">@csrf<button type="submit" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm">Reset Login</button></form>
          @if($att->status === 'locked')
          <form method="POST" action="{{ route('attempts.unlock', $att) }}">@csrf<button type="submit" class="px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-sm">Unlock</button></form>
          @endif
          <form method="POST" action="{{ route('attempts.force-submit', $att) }}" onsubmit="return handleConfirmSubmit(event, 'Paksa selesaikan ujian {{ $student->name }} sekarang?', 'Konfirmasi Paksa Selesai', 'Ya, Selesaikan')">@csrf<button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm">Paksa Selesai</button></form>
        </div>
        @endif
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Tidak ada data peserta yang sesuai.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$students" />
</div>

@push('scripts')
<script>
  function toggleMonitorMenu(event, menuId) {
    event.stopPropagation();
    const button = event.currentTarget;
    const target = document.getElementById(menuId);
    if (!target || !button) return;

    const isHidden = target.classList.contains('hidden');
    document.querySelectorAll('.mon-action-menu').forEach(m => m.classList.add('hidden'));

    if (isHidden) {
      target.classList.remove('hidden');
      const rect = button.getBoundingClientRect();
      const menuWidth = target.offsetWidth || 192;
      const menuHeight = target.offsetHeight || 140;
      const spaceBelow = window.innerHeight - rect.bottom;
      const top = spaceBelow >= menuHeight + 12 ? rect.bottom + 6 : Math.max(12, rect.top - menuHeight - 6);
      const left = Math.max(12, Math.min(window.innerWidth - menuWidth - 12, rect.right - menuWidth));

      target.style.top = `${top}px`;
      target.style.left = `${left}px`;
    }
  }

  window.addEventListener('scroll', function () {
    document.querySelectorAll('.mon-action-menu').forEach(m => m.classList.add('hidden'));
  }, true);

  document.addEventListener('click', function () {
    document.querySelectorAll('.mon-action-menu').forEach(m => m.classList.add('hidden'));
  });

  @if($exam->require_token && $exam->token_refresh_minutes && $exam->effectiveStatus() === 'ongoing' && $tokenRemainingSeconds !== null)
  (function () {
    let remaining = {{ (int) $tokenRemainingSeconds }};
    let fetching = false;
    const tokenEl = document.getElementById('active-token-value');
    const countdownEl = document.getElementById('token-countdown');
    const badgeEl = document.getElementById('token-countdown-badge');
    const statusUrl = @json(route('exams.token-status', $exam));

    function renderTime(sec) {
      if (!countdownEl) return;
      const safe = Math.max(0, sec);
      const m = String(Math.floor(safe / 60)).padStart(2, '0');
      const s = String(safe % 60).padStart(2, '0');
      countdownEl.textContent = `${m}:${s}`;
    }

    async function syncToken() {
      if (fetching) return;
      fetching = true;
      try {
        const res = await fetch(statusUrl, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (tokenEl && data.token && tokenEl.textContent.trim() !== data.token) {
          tokenEl.textContent = data.token;
          tokenEl.classList.add('bg-emerald-100', 'border-emerald-400');
          setTimeout(() => tokenEl.classList.remove('bg-emerald-100', 'border-emerald-400'), 1500);
        }
        if (data.status === 'finished') {
          if (badgeEl) badgeEl.classList.add('hidden');
          return;
        }
        if (typeof data.remaining_seconds === 'number') {
          remaining = data.remaining_seconds;
          renderTime(remaining);
        }
      } catch (e) {
      } finally {
        fetching = false;
      }
    }

    renderTime(remaining);
    setInterval(function () {
      if (remaining > 0) {
        remaining--;
        renderTime(remaining);
      }
      if (remaining <= 0) {
        syncToken();
      }
    }, 1000);

    // Sinkronisasi berkala setiap 15 detik jika ada perubahan token manual dari tab/admin lain
    setInterval(syncToken, 15000);
  })();
  @endif
</script>
@endpush
@endsection

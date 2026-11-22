@extends('layouts.app')

@section('title', $exam ? 'Edit Jadwal Ujian' : 'Jadwal Ujian Baru')
@section('breadcrumb', $exam ? 'Edit Jadwal Ujian' : 'Jadwal Ujian Baru')

@section('content')
<div class="space-y-3 border-b border-notion-border pb-5">
  <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors shadow-sm">
    <i data-lucide="arrow-left" class="w-4 h-4 text-notion-gray"></i> Kembali
  </a>
  <h1 class="text-3xl font-bold tracking-tight">{{ $exam ? 'Edit Jadwal Ujian' : 'Jadwal Ujian Baru' }}</h1>
  <p class="text-sm text-notion-gray">Pengaturan bank soal, waktu pelaksanaan, peserta kelas, serta ketentuan keamanan ujian.</p>
</div>

<form method="POST" action="{{ $exam ? route('exams.update', $exam) : route('exams.store') }}" class="bg-white border border-notion-border rounded-xl p-6 sm:p-8 shadow-sm space-y-6">
  @csrf
  @if($exam) @method('PUT') @endif

  @if($exam)
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="sm:col-span-2">
      <label class="block text-sm font-medium mb-1.5">Judul Ujian</label>
      <input type="text" name="title" value="{{ old('title', $exam->title) }}" required placeholder="Contoh: Sumatif Akhir Semester Matematika"
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm focus:outline-none focus:border-notion-blue">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Status Jadwal</label>
      <select name="status" class="modern-select">
        <option value="draft" {{ old('status', $exam->status) === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="scheduled" {{ old('status', $exam->status) === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
        <option value="active" {{ old('status', $exam->status) === 'active' ? 'selected' : '' }}>Aktif</option>
        <option value="finished" {{ old('status', $exam->status) === 'finished' ? 'selected' : '' }}>Selesai</option>
      </select>
    </div>
  </div>
  @else
  <div>
    <label class="block text-sm font-medium mb-1.5">Judul Ujian</label>
    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Sumatif Akhir Semester Matematika"
           class="w-full border border-notion-border rounded-md px-3 py-2 text-sm focus:outline-none focus:border-notion-blue">
  </div>
  @endif

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1.5">Bank Soal (Belum Dijadwalkan)</label>
      @php
        $selectedBankId = old('question_bank_id', $exam?->question_bank_id);
        $selectedBank = $banks->firstWhere('id', $selectedBankId);
      @endphp
      <input type="hidden" name="question_bank_id" id="exam-bank-id" value="{{ $selectedBankId }}" required>
      <div id="exam-bank-tag" class="empty:hidden flex flex-wrap items-center gap-1.5"></div>
      <div id="search-exam-bank-wrap" class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" id="search-exam-bank" oninput="filterSearchPicker('search-exam-bank', 'exam-bank-list')" placeholder="Ketik judul bank soal, mapel, atau nama guru..." class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
      </div>
      <div id="exam-bank-list" class="hidden mt-1.5 max-h-44 overflow-y-auto border border-notion-border rounded-md p-1.5 space-y-0.5 bg-white shadow-sm">
        @forelse($banks as $b)
          @php
            $bankLabel = $b->title . ' (' . ($b->subject->name ?? '-') . ')';
            $teacherName = $b->teacher->name ?? '-';
            $bankClasses = collect($b->target_classes ?? [])->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
          @endphp
          <button type="button"
                  onclick='selectExamBank(@json((string) $b->id), @json($bankLabel), @json($teacherName), @json($bankClasses))'
                  class="w-full text-left px-2.5 py-2 rounded text-sm hover:bg-notion-hover text-notion-text filter-item"
                  data-label="{{ strtolower($b->title . ' ' . ($b->subject->name ?? '') . ' ' . $teacherName) }}">
            <p class="font-medium text-notion-text">{{ $b->title }}</p>
            <p class="text-sm text-notion-gray">{{ $b->subject->name ?? '-' }} • Guru: {{ $teacherName }}</p>
          </button>
        @empty
          <p class="p-2 text-sm text-notion-gray">Semua bank soal siap pakai sudah dijadwalkan.</p>
        @endforelse
      </div>
      <p id="exam-bank-teacher-info" class="hidden mt-1.5 text-sm text-notion-gray">
        Guru Pembuat: <strong id="exam-bank-teacher-name" class="text-notion-text"></strong>
      </p>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Mode Pelaksanaan</label>
      <select name="execution_mode" class="modern-select">
        @if(auth()->user()->isAdmin())
          <option value="terpusat" {{ old('execution_mode', $exam?->execution_mode) === 'terpusat' ? 'selected' : '' }}>Terpusat (Sumatif Panitia)</option>
        @endif
        <option value="mandiri" {{ old('execution_mode', $exam?->execution_mode) === 'mandiri' ? 'selected' : '' }}>Mandiri (Guru Mapel)</option>
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1.5">Waktu Mulai</label>
      <input type="text" id="start-at-input" name="start_at" required value="{{ old('start_at', $exam ? $exam->start_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="modern-datepicker">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Waktu Selesai</label>
      <input type="text" id="end-at-input" name="end_at" required value="{{ old('end_at', $exam ? $exam->end_at->format('Y-m-d\TH:i') : now()->addMinutes(90)->format('Y-m-d\TH:i')) }}" class="modern-datepicker">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Durasi (Menit) <span class="text-notion-gray font-normal">(Otomatis)</span></label>
      <input type="number" id="duration-input" name="duration_minutes" value="{{ old('duration_minutes', $exam?->duration_minutes ?? 90) }}" min="5" max="360" required
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum bg-notion-sidebar/40 focus:bg-white focus:outline-none focus:border-notion-blue">
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1.5">Sesi / Gelombang (Opsional)</label>
      <input type="text" name="session_name" value="{{ old('session_name', $exam?->session_name) }}" placeholder="Contoh: Sesi 1"
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Aturan Pelanggaran</label>
      <select name="violation_mode" class="modern-select">
        <option value="strict" {{ old('violation_mode', $exam?->violation_mode) === 'strict' ? 'selected' : '' }}>Ketat (Kunci otomatis)</option>
        <option value="standard" {{ old('violation_mode', $exam?->violation_mode) === 'standard' ? 'selected' : '' }}>Standar (Peringatan saja)</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Batas Pelanggaran</label>
      <input type="number" name="max_violations" value="{{ old('max_violations', $exam?->max_violations ?? 3) }}" min="1" max="20" required
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Rotasi Token Otomatis</label>
      @php $rot = (int) old('token_refresh_minutes', $exam?->token_refresh_minutes ?? 0); @endphp
      <select name="token_refresh_minutes" class="modern-select">
        <option value="0" {{ $rot === 0 ? 'selected' : '' }}>Manual (Tidak Otomatis)</option>
        <option value="15" {{ $rot === 15 ? 'selected' : '' }}>Setiap 15 Menit</option>
        <option value="30" {{ $rot === 30 ? 'selected' : '' }}>Setiap 30 Menit</option>
        <option value="45" {{ $rot === 45 ? 'selected' : '' }}>Setiap 45 Menit</option>
        <option value="60" {{ $rot === 60 ? 'selected' : '' }}>Setiap 60 Menit (1 Jam)</option>
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1.5">Minimal Sisa Waktu Kirim Ujian (Menit)</label>
      <input type="number" name="min_submit_remaining_minutes" value="{{ old('min_submit_remaining_minutes', $exam?->min_submit_remaining_minutes ?? 10) }}" min="0" max="180" required
             class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
      <p class="mt-1 text-xs text-notion-gray">Ideal: 10 menit sebelum waktu berakhir. Isi 0 jika siswa boleh mengirim kapan saja.</p>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium mb-1.5">Target Kelas <span class="text-notion-gray font-normal">(Terkunci mengikuti Bank Soal)</span></label>
    <div id="exam-class-tags" class="flex flex-wrap gap-1.5 p-3 bg-notion-sidebar/50 border border-notion-border rounded-md">
      <span class="text-sm text-notion-gray">Pilih bank soal terlebih dahulu untuk melihat kelas target.</span>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-3 border-t border-notion-border">
    <div>
      <label class="block text-sm font-medium mb-1.5">Wajib Token Ujian</label>
      @php $reqToken = old('require_token', $exam?->require_token ?? true) ? '1' : '0'; @endphp
      <select name="require_token" class="modern-select">
        <option value="1" {{ $reqToken === '1' ? 'selected' : '' }}>Ya, Wajib Token</option>
        <option value="0" {{ $reqToken === '0' ? 'selected' : '' }}>Tidak (Tanpa Token)</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Acak Urutan Soal</label>
      @php $shufQ = old('shuffle_questions', $exam?->shuffle_questions ?? true) ? '1' : '0'; @endphp
      <select name="shuffle_questions" class="modern-select">
        <option value="1" {{ $shufQ === '1' ? 'selected' : '' }}>Ya, Acak Soal</option>
        <option value="0" {{ $shufQ === '0' ? 'selected' : '' }}>Tidak (Urut)</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Acak Opsi Jawaban</label>
      @php $shufO = old('shuffle_options', $exam?->shuffle_options ?? true) ? '1' : '0'; @endphp
      <select name="shuffle_options" class="modern-select">
        <option value="1" {{ $shufO === '1' ? 'selected' : '' }}>Ya, Acak Opsi</option>
        <option value="0" {{ $shufO === '0' ? 'selected' : '' }}>Tidak (Urut)</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1.5">Tampilkan Nilai</label>
      @php $showS = old('show_score', $exam?->show_score ?? false) ? '1' : '0'; @endphp
      <select name="show_score" class="modern-select">
        <option value="0" {{ $showS === '0' ? 'selected' : '' }}>Sembunyikan</option>
        <option value="1" {{ $showS === '1' ? 'selected' : '' }}>Tampilkan ke Siswa</option>
      </select>
    </div>
  </div>

  <div class="flex justify-end gap-2 pt-4 border-t border-notion-border">
    <a href="{{ route('exams.index') }}" class="px-4 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover">Batal</a>
    <button type="submit" class="px-5 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors">
      {{ $exam ? 'Simpan Perubahan' : 'Terbitkan Jadwal Ujian' }}
    </button>
  </div>
</form>

@push('scripts')
<script>
  const origClearSinglePicker = window.clearSinglePicker;
  window.clearSinglePicker = function (hiddenId, tagId, searchWrapId) {
    origClearSinglePicker(hiddenId, tagId, searchWrapId);
    if (hiddenId === 'exam-bank-id') {
      document.getElementById('exam-bank-teacher-info')?.classList.add('hidden');
      renderLockedExamClasses([]);
    }
  };

  function renderLockedExamClasses(classes) {
    const tagsEl = document.getElementById('exam-class-tags');
    if (!tagsEl) return;
    if (!Array.isArray(classes) || classes.length === 0) {
      tagsEl.innerHTML = '<span class="text-sm text-notion-gray">Pilih bank soal terlebih dahulu untuk melihat kelas target.</span>';
      return;
    }
    tagsEl.innerHTML = classes.map(c =>
      `<span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-notion-border text-sm text-notion-text font-medium">${c === 'ALL' ? 'Semua Kelas' : c}</span>`
    ).join('');
  }

  function selectExamBank(bankId, label, teacherName, targetClasses) {
    selectSinglePicker('exam-bank-id', 'search-exam-bank', 'exam-bank-list', 'exam-bank-tag', bankId, label, 'search-exam-bank-wrap');

    const infoEl = document.getElementById('exam-bank-teacher-info');
    const nameEl = document.getElementById('exam-bank-teacher-name');
    if (infoEl && nameEl) {
      nameEl.textContent = teacherName || '-';
      infoEl.classList.remove('hidden');
    }

    renderLockedExamClasses(targetClasses || []);
  }

  document.addEventListener('DOMContentLoaded', () => {
    @if($selectedBank)
      selectExamBank(
        @json((string) $selectedBank->id),
        @json($selectedBank->title . ' (' . ($selectedBank->subject->name ?? '-') . ')'),
        @json($selectedBank->teacher->name ?? '-'),
        @json(collect($selectedBank->target_classes ?? [])->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all())
      );
    @endif

    const startEl = document.getElementById('start-at-input');
    const endEl = document.getElementById('end-at-input');
    const durationEl = document.getElementById('duration-input');

    function recalcDuration() {
      if (!startEl?.value || !endEl?.value || !durationEl) return;
      const start = new Date(startEl.value);
      const end = new Date(endEl.value);
      if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
      const diffMinutes = Math.round((end.getTime() - start.getTime()) / 60000);
      if (diffMinutes > 0) {
        durationEl.value = Math.min(360, Math.max(5, diffMinutes));
      }
    }

    startEl?.addEventListener('change', recalcDuration);
    endEl?.addEventListener('change', recalcDuration);
  });
</script>
@endpush
@endsection

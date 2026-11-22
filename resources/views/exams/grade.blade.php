@extends('layouts.app')

@section('title', 'Input & Finalisasi Nilai: ' . $exam->title)
@section('breadcrumb', 'Input & Finalisasi Nilai')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-notion-border pb-5">
  <div class="space-y-2">
    <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors shadow-sm">
      <i data-lucide="arrow-left" class="w-4 h-4 text-notion-gray"></i> Kembali
    </a>
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Input &amp; Finalisasi Nilai</h1>
    <p class="text-sm text-notion-gray mt-1">{{ $exam->title }} • {{ $essayQuestions->count() }} soal uraian • {{ $attempts->count() }} peserta</p>
  </div>

  @if($exam->scores_finalized)
    <div class="flex flex-wrap items-center gap-2">
      <span class="px-3 py-2 rounded-md text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1.5">
        <i data-lucide="lock" class="w-4 h-4"></i> Nilai Difinalisasi
      </span>
      <a href="{{ $exam->scoreArchive ? route('score-archives.show', $exam->scoreArchive) : route('score-archives.index') }}" class="px-3.5 py-2 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black inline-flex items-center gap-1.5">
        <i data-lucide="award" class="w-4 h-4"></i> Buka Daftar Nilai
      </a>
    </div>
  @else
    <form method="POST" action="{{ route('exams.finalize-scores', $exam) }}" onsubmit="return handleConfirmSubmit(event, 'Setelah difinalisasi, seluruh nilai peserta akan dikunci permanen dan disimpan ke menu Daftar Nilai (tetap tersimpan meskipun jadwal ujian dihapus). Lanjutkan?', 'Finalisasi &amp; Kunci Nilai', 'Ya, Finalisasi', 'info')">
      @csrf
      <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="lock" class="w-4 h-4"></i> Finalisasi &amp; Kunci Nilai
      </button>
    </form>
  @endif
</div>

@if($exam->scores_finalized)
  <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800 flex items-center gap-2.5">
    <i data-lucide="shield-check" class="w-5 h-5 shrink-0"></i>
    <span>Nilai ujian ini sudah difinalisasi dan dikunci pada {{ $exam->scores_finalized_at?->translatedFormat('d M Y H:i') }}. Data nilai telah tersimpan permanen di menu <strong>Daftar Nilai</strong>.</span>
  </div>
@elseif($essayQuestions->isEmpty())
  <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-notion-blue flex items-center gap-2.5">
    <i data-lucide="info" class="w-5 h-5 shrink-0"></i>
    <span>Ujian ini tidak memiliki soal uraian. Anda tetap dapat melakukan penyesuaian nilai manual per siswa di bawah ini sebelum menekan tombol <strong>Finalisasi &amp; Kunci Nilai</strong>.</span>
  </div>
@endif

<div class="border border-notion-border rounded-xl bg-white overflow-hidden shadow-sm divide-y divide-notion-border">
  @forelse($attempts as $idx => $attempt)
  <div>
    <div onclick="toggleGradeStudent({{ $idx }})" class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2 cursor-pointer hover:bg-notion-hover/50 transition-colors">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="font-bold text-base text-notion-text">{{ $attempt->student->name }}</h3>
          <span class="px-2 py-0.5 rounded bg-notion-hover border border-notion-border text-xs font-medium">{{ $attempt->student->kelas }}</span>
        </div>
        <p class="text-sm text-notion-gray font-mono mt-0.5">
          NIS: {{ $attempt->student->nis }} • Objektif: {{ (int) round((float) $attempt->objective_score) }} • Uraian: {{ (int) round((float) $attempt->essay_score) }} •
          @if($attempt->manual_score !== null)
            Manual: <strong>{{ (int) round((float) $attempt->manual_score) }}</strong> •
          @endif
          Nilai Akhir: <strong>{{ (int) round((float) $attempt->final_score) }}</strong>
        </p>
      </div>
      <div class="flex items-center gap-2.5 shrink-0">
        @if($exam->scores_finalized)
          <span class="px-2.5 py-1 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
            <i data-lucide="lock" class="w-3.5 h-3.5"></i> Terkunci
          </span>
        @elseif($attempt->grading_status === 'final')
          <span class="px-2.5 py-1 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Nilai Tersimpan</span>
        @else
          <span class="px-2.5 py-1 rounded text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">Menunggu Koreksi</span>
        @endif
        <i data-lucide="chevron-down" id="grade-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 {{ $idx === 0 ? 'rotate-180' : '' }}"></i>
      </div>
    </div>

    <form id="grade-detail-{{ $idx }}" method="POST" action="{{ route('attempts.grade', $attempt) }}" class="{{ $idx === 0 ? '' : 'hidden' }} p-5 sm:px-6 bg-notion-sidebar/20 border-t border-notion-border space-y-4">
      @csrf
      @if(isset($objectiveQuestions) && $objectiveQuestions->isNotEmpty())
        <div class="bg-white border border-notion-border rounded-lg overflow-hidden">
          <div class="px-4 py-3 bg-notion-sidebar/40 flex flex-wrap items-center justify-between gap-2 text-sm font-semibold text-notion-text border-b border-notion-border">
            <span>Jawaban Pilihan Ganda / Objektif ({{ $objectiveQuestions->count() }} soal · Hanya Baca)</span>
            <span class="text-xs font-mono text-notion-gray">Skor Objektif: {{ (int) round((float) $attempt->objective_score) }}</span>
          </div>
          <div class="divide-y divide-notion-border">
            @foreach($objectiveQuestions as $oqIdx => $oq)
              @php
                $oAns = $attempt->answers->firstWhere('question_id', $oq->id);
                $oData = $oAns?->answer_data ?? [];
                $opts = $oq->options ?? [];
                $earnedPts = (float) ($oAns?->score ?? 0);
                $maxPts = (float) $oq->weight;
                $isFullCorrect = $maxPts > 0 && $earnedPts >= $maxPts;
                $isPartial = ! $isFullCorrect && $earnedPts > 0;

                $statusLabel = $isFullCorrect ? 'Benar' : ($isPartial ? 'Sebagian' : 'Salah');
                $statusClass = $isFullCorrect
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : ($isPartial ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-red-50 text-red-700 border-red-200');
              @endphp
              <div class="p-4 text-sm space-y-2.5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <p class="font-semibold text-notion-text flex-1 min-w-0">
                    <span class="text-notion-gray mr-1">Soal #{{ $oqIdx + 1 }}:</span>
                    {{ $oq->question_text }}
                  </p>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $statusClass }}">{{ $statusLabel }}</span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono border border-notion-border bg-notion-sidebar/50 text-notion-text">
                      {{ rtrim(rtrim(number_format($earnedPts, 2, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format($maxPts, 2, '.', ''), '0'), '.') }}
                    </span>
                  </div>
                </div>

                @if($oq->type === 'pg')
                  @php $selKey = $oData['selected'] ?? null; @endphp
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                    @foreach($opts as $opt)
                      @php
                        $k = $opt['key'] ?? '';
                        $isCor = !empty($opt['is_correct']);
                        $isSel = $selKey === $k;
                        $optClass = $isCor
                          ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                          : ($isSel ? 'bg-red-50 border-red-200 text-red-900' : 'bg-notion-sidebar/30 border-notion-border text-notion-gray');
                      @endphp
                      <div class="px-2.5 py-1.5 rounded border flex items-center justify-between gap-2 {{ $optClass }}">
                        <span class="min-w-0"><strong class="mr-1">{{ $k }}.</strong>{{ $opt['text'] ?? '' }}</span>
                        <span class="shrink-0 font-semibold">
                          @if($isSel && $isCor) Dipilih (Benar)
                          @elseif($isSel) Dipilih (Salah)
                          @elseif($isCor) Kunci
                          @endif
                        </span>
                      </div>
                    @endforeach
                  </div>
                  @if(!$selKey)
                    <p class="text-xs text-red-600 font-medium">Siswa tidak memilih jawaban.</p>
                  @endif
                @elseif($oq->type === 'pg_kompleks')
                  @php
                    $selKeys = is_array($oData['selected'] ?? null) ? $oData['selected'] : [];
                    $corKeys = collect($opts)->filter(fn ($o) => !empty($o['is_correct']))->pluck('key')->all();
                  @endphp
                  <p class="text-xs text-notion-gray">
                    Dipilih Siswa: <strong class="{{ $isFullCorrect ? 'text-emerald-700' : 'text-red-600' }}">{{ !empty($selKeys) ? implode(', ', $selKeys) : '(Tidak dijawab)' }}</strong>
                    <span class="mx-1.5">•</span>
                    Kunci: <strong class="text-notion-text">{{ !empty($corKeys) ? implode(', ', $corKeys) : '-' }}</strong>
                  </p>
                @elseif($oq->type === 'benar_salah')
                  @php $resp = is_array($oData['statements'] ?? null) ? $oData['statements'] : []; @endphp
                  <p class="text-xs text-notion-gray">
                    Dipilih Siswa: <strong class="{{ $isFullCorrect ? 'text-emerald-700' : 'text-red-600' }}">{{ !empty($resp) ? collect($resp)->map(fn ($v, $k) => $k . ':' . ($v ? 'B' : 'S'))->implode(', ') : '(Tidak dijawab)' }}</strong>
                    <span class="mx-1.5">•</span>
                    Kunci: <strong class="text-notion-text">{{ collect($opts)->map(fn ($o) => ($o['key'] ?? '') . ':' . (!empty($o['correct']) ? 'B' : 'S'))->implode(', ') }}</strong>
                  </p>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endif
      @foreach($essayQuestions as $qIdx => $eq)
        @php
          $ans = $attempt->answers->firstWhere('question_id', $eq->id);
          $uMode = $eq->options['mode'] ?? 'manual';
          $rawText = $ans?->answer_data['text'] ?? '';
          $isOnPaper = !empty($ans?->answer_data['answered_on_paper']);
          $text = trim((string) $rawText) !== ''
            ? $rawText
            : ($isOnPaper ? '(Jawaban dikumpulkan melalui catatan / kertas manual — silakan input skor di bawah)' : '(Tidak diisi di aplikasi / dikumpulkan manual)');
        @endphp
        <div class="p-4 bg-white border border-notion-border rounded-lg space-y-3">
          <div class="flex flex-wrap justify-between gap-2 text-sm text-notion-gray">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-semibold text-notion-text">Soal Uraian #{{ $qIdx + 1 }}: {{ $eq->question_text }}</span>
              @if($uMode === 'auto_text')
                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">Otomatis</span>
              @endif
            </div>
            <span class="shrink-0 font-mono">Maks Bobot: {{ (float) $eq->weight }}</span>
          </div>
          <div class="p-3 bg-notion-sidebar/40 border border-notion-border rounded-md text-sm whitespace-pre-line">{{ $text }}</div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1">Skor Diberikan (0 – {{ (float) $eq->weight }})</label>
              <input type="number" name="scores[{{ $eq->id }}]" value="{{ $ans?->is_graded ? (float) $ans->score : 0 }}" min="0" max="{{ (float) $eq->weight }}" step="0.5" required {{ $exam->scores_finalized ? 'disabled' : '' }} class="w-full border border-notion-border rounded-md px-3 py-1.5 text-sm tnum bg-white disabled:bg-notion-sidebar">
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium mb-1">Catatan Soal (Opsional)</label>
              <input type="text" name="notes[{{ $eq->id }}]" value="{{ $ans?->teacher_note }}" placeholder="Catatan koreksi..." {{ $exam->scores_finalized ? 'disabled' : '' }} class="w-full border border-notion-border rounded-md px-3 py-1.5 text-sm bg-white disabled:bg-notion-sidebar">
            </div>
          </div>
        </div>
      @endforeach

      <div class="p-4 bg-white border border-notion-border rounded-lg grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1">Input Nilai Manual (Opsional, 0–100)</label>
          <input type="number" name="manual_score" value="{{ $attempt->manual_score !== null ? (float) $attempt->manual_score : '' }}" min="0" max="100" step="0.01" placeholder="Kosongkan untuk hitung otomatis" {{ $exam->scores_finalized ? 'disabled' : '' }} class="w-full border border-notion-border rounded-md px-3 py-1.5 text-sm tnum bg-white disabled:bg-notion-sidebar">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium mb-1">Catatan Nilai Akhir (Opsional)</label>
          <input type="text" name="attempt_note" value="{{ $attempt->teacher_note }}" placeholder="Contoh: Remedial / Tambahan keaktifan..." {{ $exam->scores_finalized ? 'disabled' : '' }} class="w-full border border-notion-border rounded-md px-3 py-1.5 text-sm bg-white disabled:bg-notion-sidebar">
        </div>
      </div>

      @if(!$exam->scores_finalized)
      <div class="flex justify-end">
        <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors">
          Simpan Nilai Siswa
        </button>
      </div>
      @endif
    </form>
  </div>
  @empty
  <div class="p-8 text-center text-sm text-notion-gray">
    Belum ada peserta yang mengumpulkan ujian.
  </div>
  @endforelse
</div>

<script>
  let openGradeIdx = 0;
  function toggleGradeStudent(idx) {
    if (openGradeIdx !== null && openGradeIdx !== idx) {
      document.getElementById(`grade-detail-${openGradeIdx}`)?.classList.add('hidden');
      document.getElementById(`grade-icon-${openGradeIdx}`)?.classList.remove('rotate-180');
    }
    const detail = document.getElementById(`grade-detail-${idx}`);
    const icon = document.getElementById(`grade-icon-${idx}`);
    if (!detail) return;
    const willOpen = detail.classList.contains('hidden');
    detail.classList.toggle('hidden', !willOpen);
    icon?.classList.toggle('rotate-180', willOpen);
    openGradeIdx = willOpen ? idx : null;
  }
</script>
@endsection

@extends('layouts.app')

@section('title', $questionBank->title)
@section('breadcrumb', 'Kelola Soal')

@section('content')
<div class="space-y-4 border-b border-notion-border pb-6">
  <div>
    <a href="{{ route('question-banks.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors shadow-sm">
      <i data-lucide="arrow-left" class="w-4 h-4 text-notion-gray"></i> Kembali
    </a>
  </div>

  <div class="space-y-2">
    <p class="text-sm font-semibold text-notion-blue">{{ $questionBank->subject->name ?? '-' }}</p>
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ $questionBank->title }}</h1>
    <div class="flex flex-wrap items-center gap-1.5 text-sm text-notion-gray">
      <span class="font-medium text-notion-text tnum mr-1">Total {{ $questionBank->questions->count() }} butir soal</span>
      @php
        $totalPoints = round((float) $questionBank->questions->sum('weight'), 2);
        $totalPointsLabel = rtrim(rtrim(number_format($totalPoints, 2, '.', ''), '0'), '.');
        $diffPoints = round(100 - $totalPoints, 2);
        $diffPointsLabel = rtrim(rtrim(number_format(abs($diffPoints), 2, '.', ''), '0'), '.');
        $pointBadgeClass = abs($diffPoints) < 0.01
          ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
          : ($totalPoints < 100 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-red-50 border-red-200 text-red-700');
      @endphp
      <span class="px-2 py-0.5 rounded border text-xs font-semibold tnum {{ $pointBadgeClass }}">
        Total Poin: {{ $totalPointsLabel }} / 100
        @if(abs($diffPoints) < 0.01)
          ✓
        @elseif($totalPoints < 100)
          (Kurang {{ $diffPointsLabel }})
        @else
          (Lebih {{ $diffPointsLabel }})
        @endif
      </span>
      <span class="mr-1">• Target Kelas:</span>
      @php
        $targets = collect($questionBank->target_classes ?? ['ALL'])->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
      @endphp
      @foreach($targets as $tc)
        <span class="px-2 py-0.5 rounded bg-notion-sidebar border border-notion-border text-notion-text text-sm">{{ $tc === 'ALL' ? 'Semua Kelas' : $tc }}</span>
      @endforeach
    </div>
  </div>

  @if($questionBank->isLocked())
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-blue-50 border border-blue-200 text-notion-blue rounded-lg text-sm">
    <div class="flex items-center gap-2.5">
      <i data-lucide="lock" class="w-4 h-4 shrink-0"></i>
      <span><strong>Terkunci:</strong> Bank soal ini sudah dijadwalkan untuk ujian sehingga tidak dapat diubah atau diedit.</span>
    </div>
    <a href="{{ route('question-banks.export-pdf', $questionBank) }}" target="_blank" class="px-3.5 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover inline-flex items-center justify-center gap-2 shadow-sm shrink-0">
      <i data-lucide="file-text" class="w-4 h-4 text-notion-red"></i> Unduh PDF
    </a>
  </div>
  @else
  <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 pt-1">
    <button type="button" onclick="openCreateQuestionModal()" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors inline-flex items-center justify-center gap-2 shadow-sm">
      <i data-lucide="plus" class="w-4 h-4"></i> Tambah Butir Soal
    </button>

    <button type="button" onclick="openAppModal('modal-import-questions')" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-text text-white text-sm font-medium rounded-md hover:bg-black transition-colors inline-flex items-center justify-center gap-2 shadow-sm">
      <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Import Excel
    </button>

    <a href="{{ route('question-banks.export-pdf', $questionBank) }}" target="_blank" class="w-full sm:w-auto px-3.5 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors inline-flex items-center justify-center gap-2 shadow-sm">
      <i data-lucide="file-text" class="w-4 h-4 text-notion-red"></i> Unduh PDF
    </a>

    @if(auth()->user()->isGuru() || $questionBank->teacher_id === auth()->id())
    <form method="POST" action="{{ route('question-banks.toggle-ready', $questionBank) }}" class="w-full sm:w-auto">
      @csrf
      @if($questionBank->is_ready)
        <button type="submit" class="w-full sm:w-auto px-3.5 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-100 inline-flex items-center justify-center gap-2 transition-colors">
          <i data-lucide="check-circle-2" class="w-4 h-4"></i> Status: Siap Pakai
        </button>
      @else
        <button type="submit" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-text text-white text-sm font-medium rounded-md hover:bg-black inline-flex items-center justify-center gap-2 transition-colors">
          <i data-lucide="send" class="w-4 h-4"></i> Tandai Siap Pakai
        </button>
      @endif
    </form>
    @endif

    @if($questionBank->is_ready && auth()->user()->isAdmin())
      <button type="button" onclick="openAppModal('modal-revision-bank')" class="w-full sm:w-auto px-3.5 py-2 bg-notion-redBg border border-red-200 text-notion-red text-sm font-medium rounded-md hover:bg-red-200 inline-flex items-center justify-center gap-2 transition-colors">
        <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Minta Perbaikan
      </button>
    @endif
  </div>
  @endif
</div>

@if(!$questionBank->is_ready && $questionBank->revision_note)
<div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm">
  <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
  <div class="space-y-1">
    <h4 class="font-semibold text-amber-900">Perlu Perbaikan (Catatan Panitia / Admin)</h4>
    <p class="text-amber-800 whitespace-pre-line leading-relaxed">{{ $questionBank->revision_note }}</p>
  </div>
</div>
@endif

@php
  $categories = [
    'pg' => 'Pilihan Ganda',
    'pg_kompleks' => 'Pilihan Ganda Kompleks',
    'benar_salah' => 'Benar / Salah',
    'uraian' => 'Uraian (Esai)',
  ];
  $groupedQuestions = $questionBank->questions->groupBy('type');
@endphp

<div class="space-y-6">
  @if($questionBank->questions->isEmpty())
    <div class="p-10 text-center border border-dashed border-notion-border rounded-xl text-sm text-notion-gray space-y-3">
      <p>Belum ada soal pada bank soal ini.</p>
      <button type="button" onclick="openCreateQuestionModal()" class="px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors inline-flex items-center gap-2 shadow-sm">
        <i data-lucide="plus" class="w-4 h-4"></i> Tambah Butir Soal Pertama
      </button>
    </div>
  @else
    @foreach($categories as $typeKey => $typeLabel)
      @php $items = $groupedQuestions->get($typeKey, collect()); @endphp
      @if($items->isNotEmpty())
      <div class="border border-notion-border rounded-xl bg-white overflow-hidden shadow-sm">

        <div class="px-5 py-3 bg-notion-sidebar border-b border-notion-border flex items-center justify-between">
          <span class="font-bold text-sm text-notion-text">{{ $typeLabel }}</span>
          <span class="px-2.5 py-0.5 rounded bg-white border border-notion-border text-sm font-medium tnum text-notion-gray">{{ $items->count() }} soal</span>
        </div>

        <div class="divide-y divide-notion-border">
          @foreach($items as $idx => $q)
          @php
            $qPayload = [
              'id' => $q->id,
              'type' => $q->type,
              'question_text' => $q->question_text,
              'weight' => (float) $q->weight,
              'scoring_mode' => $q->scoring_mode ?? 'full',
              'media_url' => $q->media_path ? asset('uploads/soal/' . $q->media_path) : null,
              'options' => in_array($q->type, ['pg', 'pg_kompleks'], true)
                ? collect($q->options ?? [])->map(function ($o) {
                    if (is_array($o)) {
                        $o['image_url'] = !empty($o['image_path']) ? asset('uploads/soal/' . $o['image_path']) : null;
                    }
                    return $o;
                  })->values()->all()
                : ($q->options ?? []),
              'update_url' => route('questions.update', [$questionBank, $q]),
            ];
          @endphp
          <div class="bg-white">

            <div onclick="toggleMobileRow('q', {{ $q->id }})" class="px-5 py-3.5 flex items-center justify-between gap-4 cursor-pointer hover:bg-notion-hover/50 transition-colors">
              <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="px-2 py-0.5 rounded text-sm font-mono font-bold bg-notion-hover border border-notion-border shrink-0">#{{ $idx + 1 }}</span>
                <span class="text-sm text-notion-gray shrink-0 tnum">Bobot: {{ (float) $q->weight }}</span>
                <p class="text-sm font-medium text-notion-text truncate">{{ \Illuminate\Support\Str::limit(strip_tags($q->question_text), 110) }}</p>
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                @if(!$questionBank->isLocked())
                <button type="button" onclick='event.stopPropagation(); openEditQuestionModal(@json($qPayload))' class="text-notion-gray hover:text-notion-blue p-1.5 rounded hover:bg-blue-50 transition-colors" title="Edit Soal">
                  <i data-lucide="edit-2" class="w-4 h-4"></i>
                </button>
                <form method="POST" action="{{ route('questions.destroy', [$questionBank, $q]) }}" onclick="event.stopPropagation()" onsubmit="return handleConfirmSubmit(event, 'Butir soal #{{ $idx + 1 }} akan dihapus permanen dari bank soal ini.')">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-notion-gray hover:text-notion-red p-1.5 rounded hover:bg-red-50 transition-colors" title="Hapus Soal"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
                @endif
                <i data-lucide="chevron-down" id="q-icon-{{ $q->id }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 ml-1"></i>
              </div>
            </div>

            <div id="q-detail-{{ $q->id }}" class="hidden px-5 pb-5 pt-3 bg-notion-sidebar/25 border-t border-notion-border space-y-3">
              <p class="text-sm text-notion-text whitespace-pre-line leading-relaxed">{{ $q->question_text }}</p>

              @if($q->media_path)
                <img src="{{ asset('uploads/soal/' . $q->media_path) }}" alt="Media Soal" class="max-h-52 rounded-lg border border-notion-border">
              @endif

              @if(in_array($q->type, ['pg', 'pg_kompleks']) && is_array($q->options))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                  @foreach($q->options as $opt)
                  <div class="p-2.5 rounded-md border text-sm space-y-2 {{ !empty($opt['is_correct']) ? 'bg-emerald-50 border-emerald-200 text-emerald-800 font-medium' : 'bg-white border-notion-border text-notion-gray' }}">
                    <div class="flex items-start gap-2">
                      <span class="font-mono font-bold shrink-0">{{ $opt['key'] }}.</span>
                      <span>{{ $opt['text'] ?? '' }}</span>
                    </div>
                    @if(!empty($opt['image_path']))
                      <img src="{{ asset('uploads/soal/' . $opt['image_path']) }}" alt="Opsi {{ $opt['key'] }}" class="max-h-32 rounded border border-notion-border">
                    @endif
                  </div>
                  @endforeach
                </div>
              @elseif($q->type === 'benar_salah' && is_array($q->options))
                <div class="space-y-1.5 pt-1">
                  @foreach($q->options as $stmt)
                  <div class="px-3 py-2 rounded-md border border-notion-border bg-white text-sm flex items-center justify-between gap-2">
                    <span>{{ $stmt['key'] }}. {{ $stmt['statement'] }}</span>
                    <span class="px-2.5 py-0.5 rounded font-semibold shrink-0 {{ !empty($stmt['correct']) ? 'bg-emerald-50 text-emerald-700' : 'bg-notion-redBg text-notion-red' }}">
                      {{ !empty($stmt['correct']) ? 'Benar' : 'Salah' }}
                    </span>
                  </div>
                  @endforeach
                </div>
              @elseif($q->type === 'uraian')
                @php $uMode = $q->options['mode'] ?? 'manual'; @endphp
                <div class="p-3 rounded-md border border-notion-border bg-white text-sm space-y-1.5">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-notion-gray">Mode Uraian:</span>
                    @if($uMode === 'auto_text')
                      <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">Kunci Jawaban Otomatis (Case-Insensitive)</span>
                    @else
                      <span class="px-2 py-0.5 rounded bg-notion-hover text-notion-text border border-notion-border font-medium">Koreksi Manual (Ketik / Dikumpulkan)</span>
                    @endif
                  </div>
                  @if($uMode === 'auto_text' && !empty($q->options['accepted_answers']))
                    <p class="text-notion-gray">Kunci diterima: <span class="font-mono text-notion-text">{{ implode(', ', $q->options['accepted_answers']) }}</span></p>
                  @endif
                </div>
              @endif
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif
    @endforeach
  @endif
</div>

@push('modals')

<div id="question-modal" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeQuestionModal()">
  <div id="question-sheet" onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-none sm:rounded-xl w-full h-full sm:h-auto sm:max-w-2xl sm:max-h-[90vh] flex flex-col shadow-xl transform translate-y-full sm:translate-y-0 sm:scale-95 transition-all duration-300 ease-out overflow-hidden">

    <div class="sticky top-0 z-10 bg-white px-5 sm:px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-q-title" class="font-bold text-lg text-notion-text">Tambah Butir Soal Baru</h3>
      <button type="button" onclick="closeQuestionModal()" class="p-1.5 text-notion-gray hover:text-notion-text hover:bg-notion-hover rounded-md transition-colors">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="question-form" method="POST" action="{{ route('questions.store', $questionBank) }}" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
      @csrf
      <input type="hidden" name="_method" id="q-method" value="POST">
      <input type="hidden" name="remove_media" id="q-remove-media" value="0">
      <div class="p-6 overflow-y-auto flex-1 space-y-4">

      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <label class="block text-sm font-medium mb-1">Tipe Soal</label>
          <select name="type" id="q-type" onchange="switchQuestionType()" class="modern-select">
            <option value="pg">Pilihan Ganda</option>
            <option value="pg_kompleks">Pilihan Ganda Kompleks</option>
            <option value="benar_salah">Benar / Salah</option>
            <option value="uraian">Uraian (Koreksi Manual Guru)</option>
          </select>
        </div>
        <div class="w-full sm:w-28 shrink-0">
          <label class="block text-sm font-medium mb-1">Bobot Nilai</label>
          <input type="number" name="weight" id="q-weight" value="1" step="0.5" min="0.5" max="100" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
        </div>
        <div id="scoring-mode-wrap" class="hidden w-full sm:w-48 shrink-0">
          <label class="block text-sm font-medium mb-1">Mode Skor</label>
          <select name="scoring_mode" id="q-scoring-mode" class="modern-select">
            <option value="full">Semua Benar (Full)</option>
            <option value="partial">Parsial per Item</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Pertanyaan</label>
        <textarea name="question_text" id="q-text" rows="3" required placeholder="Tulis pertanyaan di sini..." class="w-full border border-notion-border rounded-md px-3 py-2 text-sm focus:outline-none focus:border-notion-blue"></textarea>
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium">Gambar Pendukung Soal (Opsional, max 2MB)</label>
        <div id="q-existing-media" class="hidden flex items-center gap-3 p-2 bg-notion-sidebar rounded-lg border border-notion-border">
          <img id="q-existing-media-img" src="" class="h-14 rounded border border-notion-border">
          <button type="button" onclick="markRemoveMainMedia()" class="text-sm text-notion-red hover:underline">Hapus gambar ini</button>
        </div>
        <input type="file" name="media" id="q-media-input" accept="image/*" class="modern-file" data-placeholder="Pilih gambar soal (JPG/PNG)...">
      </div>

      <div id="panel-pg" class="space-y-3 pt-3 border-t border-notion-border">
        <div class="flex items-center justify-between gap-2">
          <p class="text-sm font-semibold text-notion-gray" id="pg-hint">Isi opsi jawaban (teks dan/atau gambar) lalu pilih 1 kunci benar:</p>
          <button type="button" onclick="addPgOptionRow()" class="px-2.5 py-1 bg-white border border-notion-border rounded-md text-sm font-medium text-notion-text hover:bg-notion-hover transition-colors inline-flex items-center gap-1 shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Opsi
          </button>
        </div>
        <div id="pg-options-list" class="space-y-2.5"></div>
      </div>

      <div id="panel-bs" class="hidden pt-3 border-t border-notion-border">
        <label class="block text-sm font-medium mb-2">Jawaban</label>
        <input type="hidden" name="bs_correct[0]" id="q-bs-correct" value="1">
        <div class="grid grid-cols-2 gap-3">
          <button type="button" id="btn-bs-true" onclick="selectBsAnswer('1')"
                  class="py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-emerald-50 border-emerald-500 text-emerald-700">
            <i data-lucide="check-circle-2" class="w-4 h-4"></i> Benar
          </button>
          <button type="button" id="btn-bs-false" onclick="selectBsAnswer('0')"
                  class="py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-white border-notion-border text-notion-gray hover:bg-notion-hover">
            <i data-lucide="x-circle" class="w-4 h-4"></i> Salah
          </button>
        </div>
      </div>

      <div id="panel-uraian" class="hidden space-y-3 pt-3 border-t border-notion-border">
        <div>
          <label class="block text-sm font-medium mb-1">Mode Jawaban Uraian</label>
          <select name="uraian_mode" id="q-uraian-mode" onchange="switchUraianMode()" class="modern-select">
            <option value="manual">Koreksi Manual (Ketik di Aplikasi / Dikumpulkan)</option>
            <option value="auto_text">Isi Kunci Jawaban (Otomatis — Kapital/Kecil Dikenali)</option>
          </select>
        </div>

        <div id="uraian-answers-wrap" class="hidden space-y-1.5">
          <label class="block text-sm font-medium">Kunci Jawaban yang Diterima</label>
          <textarea name="uraian_answers" id="q-uraian-answers" rows="2" placeholder="Pisahkan beberapa variasi jawaban dengan koma atau baris baru. Contoh: Jakarta, DKI Jakarta" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white"></textarea>
          <p class="text-sm text-notion-gray">Huruf KAPITAL, huruf kecil, atau spasi berlebih pada jawaban siswa akan otomatis dikenali dan dinilai langsung.</p>
        </div>

        <div id="uraian-manual-info" class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
          Siswa dapat mengetik jawaban di aplikasi atau menandai <strong>Dikumpulkan di Kertas</strong>, lalu guru memberi skor pada menu <strong>Koreksi</strong>.
        </div>
      </div>
      </div>

      <div class="sticky bottom-0 bg-white px-6 py-3.5 border-t border-notion-border flex flex-wrap justify-end gap-2 shrink-0">
        <button type="button" onclick="closeQuestionModal()" class="px-4 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover">Batal</button>
        <button type="button" id="q-save-new-btn" onclick="saveAndNewQuestion()" class="px-4 py-2 bg-notion-text text-white text-sm font-medium rounded-md hover:bg-black transition-colors flex items-center gap-2">
          <i data-lucide="plus-circle" class="w-4 h-4"></i> Simpan &amp; Buat Baru
        </button>
        <button type="submit" id="q-submit-btn" class="px-5 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center gap-2">
          <i data-lucide="save" class="w-4 h-4"></i> Simpan Soal
        </button>
      </div>
    </form>
  </div>
</div>

<div id="modal-import-questions" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-import-questions')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 class="font-bold text-lg">Import Soal (.xlsx)</h3>
      <button type="button" onclick="closeAppModal('modal-import-questions')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <div class="p-6 overflow-y-auto flex-1 space-y-4">
      <p class="text-sm text-notion-gray leading-relaxed">
        Unggah file Excel sesuai format template (mendukung <code>pg</code>, <code>pg_kompleks</code>, <code>benar_salah</code>, dan <code>uraian</code>).
      </p>
      <a href="{{ route('question-banks.template') }}" class="w-full px-3.5 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors flex items-center justify-center gap-2 shadow-sm">
        <i data-lucide="download" class="w-4 h-4 text-notion-gray"></i> Unduh Template Soal (.xlsx)
      </a>
      <form id="import-question-form" method="POST" action="{{ route('questions.import', $questionBank) }}" enctype="multipart/form-data" onsubmit="return handleQuestionImport(event)" class="space-y-4">
        @csrf
        <input type="file" name="file" id="import-question-file" accept=".xlsx,.xls,.csv" required class="modern-file" data-placeholder="Pilih file Excel (.xlsx)...">
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="closeAppModal('modal-import-questions')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
          <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Unggah &amp; Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="import-q-progress-overlay" class="hidden fixed inset-0 w-screen h-screen z-[130] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-white border border-notion-border rounded-xl max-w-sm w-full p-6 shadow-xl space-y-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-blue-50 text-notion-blue flex items-center justify-center shrink-0">
        <i data-lucide="file-spreadsheet" class="w-5 h-5 animate-pulse"></i>
      </div>
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-base text-notion-text">Mengimpor Butir Soal</h3>
        <p id="import-q-progress-label" class="text-sm text-notion-gray truncate">Menyiapkan file Excel...</p>
      </div>
      <span id="import-q-progress-pct" class="text-sm font-bold text-notion-blue tnum">0%</span>
    </div>

    <div class="w-full h-2.5 bg-notion-hover rounded-full overflow-hidden">
      <div id="import-q-progress-bar" class="h-full bg-notion-blue rounded-full transition-all duration-300" style="width: 5%"></div>
    </div>

    <p class="text-sm text-notion-gray">Mohon tunggu, jangan tutup halaman ini hingga proses selesai.</p>
  </div>
</div>

@if($questionBank->is_ready && auth()->user()->isAdmin())

<div id="modal-revision-bank" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-revision-bank')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 class="font-bold text-lg">Batalkan Status Siap Pakai</h3>
      <button type="button" onclick="closeAppModal('modal-revision-bank')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form method="POST" action="{{ route('question-banks.request-revision', $questionBank) }}" class="p-6 overflow-y-auto flex-1 space-y-4">
      @csrf
      <p class="text-sm text-notion-gray leading-relaxed">
        Bank soal akan dikembalikan menjadi <strong>Draf</strong> agar dapat diperbaiki oleh guru pembuat. Masukkan alasan atau catatan perbaikan:
      </p>
      <div>
        <label class="block text-sm font-medium mb-1">Alasan / Catatan Perbaikan</label>
        <textarea name="revision_note" rows="3" required placeholder="Contoh: Soal nomor 5 gambarnya kurang jelas, mohon diganti." class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white"></textarea>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-revision-bank')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" class="px-4 py-2 bg-notion-red text-white text-sm font-medium rounded-md hover:bg-red-700">Batalkan &amp; Kirim Catatan</button>
      </div>
    </form>
  </div>
</div>
@endif
@endpush

@push('scripts')
<script>
  const STORE_QUESTION_URL = @json(route('questions.store', $questionBank));
  let hasSavedViaAjax = false;

  function handleQuestionImport(event) {
    event.preventDefault();
    const form = event.target;
    const fileInput = document.getElementById('import-question-file');
    if (!fileInput || !fileInput.files || !fileInput.files.length) {
      showToast('error', 'Pilih file Excel (.xlsx) terlebih dahulu.');
      return false;
    }

    closeAppModal('modal-import-questions');
    const overlay = document.getElementById('import-q-progress-overlay');
    const bar = document.getElementById('import-q-progress-bar');
    const pct = document.getElementById('import-q-progress-pct');
    const label = document.getElementById('import-q-progress-label');

    overlay.classList.remove('hidden');
    bar.style.width = '5%';
    pct.textContent = '5%';
    label.textContent = 'Mengunggah file Excel...';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action, true);

    xhr.upload.onprogress = function (e) {
      if (e.lengthComputable) {
        const percent = Math.min(85, Math.max(10, Math.round((e.loaded / e.total) * 85)));
        bar.style.width = percent + '%';
        pct.textContent = percent + '%';
      }
    };

    xhr.upload.onload = function () {
      bar.style.width = '92%';
      pct.textContent = '92%';
      label.textContent = 'Memproses & menyimpan soal ke database...';
    };

    xhr.onload = function () {
      if (xhr.status >= 400) {
        overlay.classList.add('hidden');
        showToast('error', 'Gagal mengimpor soal. Pastikan format file (.xlsx/.xls/.csv) dan kolom sesuai template.');
        return;
      }
      bar.style.width = '100%';
      pct.textContent = '100%';
      label.textContent = 'Selesai! Memuat ulang bank soal...';
      setTimeout(() => {
        window.location.href = xhr.responseURL || window.location.href;
      }, 250);
    };

    xhr.onerror = function () {
      overlay.classList.add('hidden');
      showToast('error', 'Gagal mengimpor file soal. Periksa koneksi Anda.');
    };

    xhr.send(new FormData(form));
    return false;
  }

  function openQuestionModal() {
    const modal = document.getElementById('question-modal');
    const sheet = document.getElementById('question-sheet');
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
      modal.classList.remove('opacity-0');
      sheet.classList.remove('translate-y-full', 'sm:scale-95');
    });
  }

  function closeQuestionModal() {
    if (hasSavedViaAjax) {
      window.location.reload();
      return;
    }
    const modal = document.getElementById('question-modal');
    const sheet = document.getElementById('question-sheet');
    modal.classList.add('opacity-0');
    sheet.classList.add('translate-y-full', 'sm:scale-95');
    setTimeout(() => modal.classList.add('hidden'), 280);
  }

  async function saveAndNewQuestion() {
    const form = document.getElementById('question-form');
    if (!form.reportValidity()) return;

    const btn = document.getElementById('q-save-new-btn');
    const currentType = document.getElementById('q-type').value;
    const currentWeight = document.getElementById('q-weight').value;
    const currentScoring = document.getElementById('q-scoring-mode').value;

    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const errMsg = data.message || 'Gagal menyimpan soal. Periksa kembali isian Anda.';
        showToast('error', errMsg);
        return;
      }

      hasSavedViaAjax = true;
      showToast('success', data.message || 'Soal berhasil disimpan. Siap membuat soal baru.');

      resetQuestionForm();
      setModernSelectValue('q-type', currentType);
      setModernSelectValue('q-scoring-mode', currentScoring);
      document.getElementById('q-weight').value = currentWeight;
      ['A', 'B', 'C', 'D', 'E'].forEach((k, i) => addPgOptionRow({ key: k, is_correct: i === 0 }));
      setBsSingleValue({});
      switchQuestionType();
      lucide.createIcons();
      document.getElementById('q-text')?.focus();
    } catch (e) {
      showToast('error', 'Terjadi kesalahan jaringan saat menyimpan soal.');
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-60', 'cursor-not-allowed');
    }
  }

  function setModernSelectValue(selectId, value) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.value = value;
    sel.dispatchEvent(new Event('change', { bubbles: true }));
    const wrapper = sel.nextElementSibling;
    const opt = Array.from(sel.options).find(o => o.value === value);
    if (wrapper && opt) {
      const span = wrapper.querySelector('button span');
      if (span) span.textContent = opt.text;
    }
  }

  const OPTION_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

  function addPgOptionRow(data = {}) {
    const list = document.getElementById('pg-options-list');
    const idx = list.children.length;
    const key = data.key || OPTION_LETTERS[idx] || String(idx + 1);
    const type = document.getElementById('q-type').value;
    const isPgk = type === 'pg_kompleks';

    const row = document.createElement('div');
    row.className = 'space-y-1.5 pg-row-item';
    row.innerHTML = `
      <div class="flex items-center gap-2">
        <input type="radio" name="pg_correct" value="${key}" ${data.is_correct || (idx === 0 && data.is_correct === undefined) ? 'checked' : ''} class="pg-radio ${isPgk ? 'hidden' : ''}">
        <input type="checkbox" name="pgk_correct[]" value="${key}" ${data.is_correct ? 'checked' : ''} class="pgk-check rounded border-notion-border ${isPgk ? '' : 'hidden'}">
        <span class="w-6 text-sm font-mono font-bold text-center pg-key-label">${key}</span>
        <input type="text" name="pg_options[${key}]" value="${(data.text || '').replace(/"/g, '&quot;')}" placeholder="Teks opsi ${key}..." class="flex-1 border border-notion-border rounded-md px-3 py-1.5 text-sm bg-white">
        <input type="file" name="pg_option_images[${key}]" accept="image/*" class="modern-file-icon" data-placeholder="Upload gambar opsi ${key}">
        ${idx >= 2 ? `<button type="button" onclick="removePgOptionRow(this)" class="p-1.5 text-notion-gray hover:text-notion-red rounded" title="Hapus Opsi"><i data-lucide="x" class="w-4 h-4"></i></button>` : ''}
      </div>
      <div id="pg-prev-wrap-${key}" class="${data.image_url ? '' : 'hidden'} flex items-center gap-2 pl-10">
        <img id="pg-prev-img-${key}" src="${data.image_url || ''}" class="h-9 rounded border border-notion-border">
        <input type="hidden" name="remove_option_images[${key}]" id="pg-rem-img-${key}" value="0">
        <button type="button" onclick="removeOptImage('${key}')" class="text-sm text-notion-red hover:underline">Hapus gambar</button>
      </div>
    `;
    list.appendChild(row);
    initModernFileUploads();
    lucide.createIcons();
  }

  function removePgOptionRow(btn) {
    btn.closest('.pg-row-item')?.remove();
    reindexPgOptions();
  }

  function reindexPgOptions() {
    const rows = document.querySelectorAll('#pg-options-list .pg-row-item');
    rows.forEach((row, idx) => {
      const key = OPTION_LETTERS[idx] || String(idx + 1);
      row.querySelector('.pg-key-label').textContent = key;
      row.querySelector('.pg-radio').value = key;
      row.querySelector('.pgk-check').value = key;
      row.querySelector('input[type="text"]').name = `pg_options[${key}]`;
      row.querySelector('input[type="text"]').placeholder = `Teks opsi ${key}...`;
      row.querySelector('input[type="file"]').name = `pg_option_images[${key}]`;
    });
  }

  function selectBsAnswer(val) {
    const input = document.getElementById('q-bs-correct');
    const btnTrue = document.getElementById('btn-bs-true');
    const btnFalse = document.getElementById('btn-bs-false');
    if (input) input.value = val;

    if (btnTrue && btnFalse) {
      if (val === '1') {
        btnTrue.className = 'py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-emerald-50 border-emerald-500 text-emerald-700';
        btnFalse.className = 'py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-white border-notion-border text-notion-gray hover:bg-notion-hover';
      } else {
        btnTrue.className = 'py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-white border-notion-border text-notion-gray hover:bg-notion-hover';
        btnFalse.className = 'py-2.5 px-4 rounded-lg border text-sm font-semibold transition-colors flex items-center justify-center gap-2 bg-notion-redBg border-red-400 text-notion-red';
      }
    }
  }

  function setBsSingleValue(data = {}) {
    const isCorrect = data.correct === false ? '0' : '1';
    selectBsAnswer(isCorrect);
  }

  function resetQuestionForm() {
    const form = document.getElementById('question-form');
    form.action = STORE_QUESTION_URL;
    document.getElementById('q-method').value = 'POST';
    document.getElementById('q-remove-media').value = '0';
    document.getElementById('q-existing-media').classList.add('hidden');
    document.getElementById('q-text').value = '';
    document.getElementById('q-weight').value = '1';

    const mediaInput = document.getElementById('q-media-input');
    if (mediaInput) { mediaInput.value = ''; mediaInput._resetModernLabel?.(); }

    document.getElementById('pg-options-list').innerHTML = '';
    setBsSingleValue({});
    setModernSelectValue('q-uraian-mode', 'manual');
    document.getElementById('q-uraian-answers').value = '';
    switchUraianMode();
  }

  function openCreateQuestionModal() {
    resetQuestionForm();
    document.getElementById('modal-q-title').textContent = 'Tambah Butir Soal Baru';
    document.getElementById('q-save-new-btn').classList.remove('hidden');
    document.getElementById('q-submit-btn').innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Simpan Soal';
    setModernSelectValue('q-type', 'pg');
    setModernSelectValue('q-scoring-mode', 'full');
    ['A', 'B', 'C', 'D', 'E'].forEach((k, i) => addPgOptionRow({ key: k, is_correct: i === 0 }));
    setBsSingleValue({});
    switchQuestionType();
    lucide.createIcons();
    openQuestionModal();
  }

  function openEditQuestionModal(q) {
    resetQuestionForm();
    document.getElementById('question-form').action = q.update_url;
    document.getElementById('q-method').value = 'PUT';
    document.getElementById('modal-q-title').textContent = 'Edit Butir Soal';
    document.getElementById('q-save-new-btn').classList.add('hidden');
    document.getElementById('q-submit-btn').innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan';

    setModernSelectValue('q-type', q.type);
    setModernSelectValue('q-scoring-mode', q.scoring_mode || 'full');
    document.getElementById('q-weight').value = q.weight;
    document.getElementById('q-text').value = q.question_text;

    if (q.media_url) {
      document.getElementById('q-existing-media').classList.remove('hidden');
      document.getElementById('q-existing-media-img').src = q.media_url;
    }

    if (q.type === 'pg' || q.type === 'pg_kompleks') {
      const opts = q.options || [];
      if (opts.length > 0) {
        opts.forEach(opt => addPgOptionRow(opt));
      } else {
        ['A', 'B', 'C', 'D', 'E'].forEach((k, i) => addPgOptionRow({ key: k, is_correct: i === 0 }));
      }
      setBsSingleValue({});
    } else if (q.type === 'benar_salah') {
      const stmts = q.options || [];
      setBsSingleValue(stmts[0] || {});
      ['A', 'B', 'C', 'D', 'E'].forEach((k, i) => addPgOptionRow({ key: k, is_correct: i === 0 }));
    } else {
      ['A', 'B', 'C', 'D', 'E'].forEach((k, i) => addPgOptionRow({ key: k, is_correct: i === 0 }));
      setBsSingleValue({});
      const uMode = (q.options && q.options.mode) ? q.options.mode : 'manual';
      setModernSelectValue('q-uraian-mode', uMode);
      document.getElementById('q-uraian-answers').value = (q.options && Array.isArray(q.options.accepted_answers))
        ? q.options.accepted_answers.join(', ')
        : '';
      switchUraianMode();
    }

    switchQuestionType();
    lucide.createIcons();
    openQuestionModal();
  }

  function markRemoveMainMedia() {
    document.getElementById('q-remove-media').value = '1';
    document.getElementById('q-existing-media').classList.add('hidden');
  }

  function removeOptImage(key) {
    document.getElementById(`pg-rem-img-${key}`).value = '1';
    document.getElementById(`pg-prev-wrap-${key}`).classList.add('hidden');
  }

  function switchUraianMode() {
    const mode = document.getElementById('q-uraian-mode')?.value || 'manual';
    document.getElementById('uraian-answers-wrap')?.classList.toggle('hidden', mode !== 'auto_text');
    document.getElementById('uraian-manual-info')?.classList.toggle('hidden', mode !== 'manual');
  }

  function switchQuestionType() {
    const type = document.getElementById('q-type').value;
    const panelPg = document.getElementById('panel-pg');
    const panelBs = document.getElementById('panel-bs');
    const panelUraian = document.getElementById('panel-uraian');
    const scoringWrap = document.getElementById('scoring-mode-wrap');
    const radios = document.querySelectorAll('.pg-radio');
    const checks = document.querySelectorAll('.pgk-check');
    const pgHint = document.getElementById('pg-hint');

    panelPg.classList.add('hidden');
    panelBs.classList.add('hidden');
    panelUraian.classList.add('hidden');
    scoringWrap.classList.add('hidden');

    if (type === 'pg') {
      panelPg.classList.remove('hidden');
      radios.forEach(r => r.classList.remove('hidden'));
      checks.forEach(c => c.classList.add('hidden'));
      pgHint.textContent = 'Isi opsi jawaban (teks dan/atau gambar) lalu pilih 1 kunci benar:';
    } else if (type === 'pg_kompleks') {
      panelPg.classList.remove('hidden');
      scoringWrap.classList.remove('hidden');
      radios.forEach(r => r.classList.add('hidden'));
      checks.forEach(c => c.classList.remove('hidden'));
      pgHint.textContent = 'Isi opsi jawaban (teks dan/atau gambar) lalu centang kunci benar:';
    } else if (type === 'benar_salah') {
      panelBs.classList.remove('hidden');
    } else {
      panelUraian.classList.remove('hidden');
    }
  }
</script>
@endpush
@endsection

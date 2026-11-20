@extends('layouts.app')

@section('title', 'Bank Soal')
@section('breadcrumb', 'Bank Soal')

@section('content')
<div class="space-y-3">
  <i data-lucide="book-open" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Bank Soal</h1>
  <p class="text-notion-gray text-sm">Pengelolaan bank soal ujian.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 bg-notion-sidebar/40">
    <form method="GET" action="{{ route('question-banks.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 w-full">
      <div class="relative flex-1 min-w-[200px]">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari judul paket, mapel, atau pembuat..."
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
      </div>
      <div class="sm:w-44 shrink-0">
        <select name="status" onchange="this.form.submit()" class="modern-select w-full">
          <option value="">Semua Status</option>
          <option value="ready" {{ $status === 'ready' ? 'selected' : '' }}>Siap Pakai</option>
          <option value="locked" {{ $status === 'locked' ? 'selected' : '' }}>Terkunci</option>
          <option value="revision" {{ $status === 'revision' ? 'selected' : '' }}>Perlu Perbaikan</option>
          <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
        </select>
      </div>
      <button type="submit" class="px-3.5 py-2 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors whitespace-nowrap shrink-0">
        Filter
      </button>
      @if($search !== '' || $status !== '')
        <a href="{{ route('question-banks.index') }}" class="px-3 py-2 text-sm text-notion-gray hover:text-notion-text text-center shrink-0">Reset</a>
      @endif
    </form>

    <button onclick="openCreateBankModal()" class="w-full lg:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm shrink-0 whitespace-nowrap">
      <i data-lucide="plus" class="w-4 h-4"></i> Buat Bank Soal
    </button>
  </div>

  <div class="hidden md:block">
    <table class="w-full text-left text-sm">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium">Judul Paket Soal</th>
          <th class="py-3 px-4 font-medium">Mata Pelajaran</th>
          <th class="py-3 px-4 font-medium">Pembuat</th>
          <th class="py-3 px-4 font-medium">Status</th>
          <th class="py-3 px-4 font-medium text-right whitespace-nowrap">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($banks as $bank)
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 font-semibold text-notion-text break-words">
            <a href="{{ route('question-banks.show', $bank) }}" class="hover:text-notion-blue">{{ $bank->title }}</a>
          </td>
          <td class="py-3 px-4 text-notion-text font-medium break-words">
            {{ $bank->subject->name ?? '-' }}
          </td>
          <td class="py-3 px-4 text-notion-gray break-words">{{ $bank->teacher->name ?? '-' }}</td>
          <td class="py-3 px-4 whitespace-nowrap">
            @if($bank->isLocked())
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-blue-50 text-notion-blue border border-blue-200 inline-flex items-center gap-1">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i> Terkunci
              </span>
            @elseif($bank->is_ready)
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Siap Pakai</span>
            @elseif($bank->revision_note)
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-amber-50 text-amber-800 border border-amber-200">Perlu Perbaikan</span>
            @else
              <span class="px-2.5 py-0.5 rounded text-sm font-medium bg-notion-hover text-notion-gray border border-notion-border">Draft</span>
            @endif
          </td>
          <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
            @if(!$bank->isLocked())
              @php
                $bankPayload = [
                  'id' => $bank->id,
                  'subject_id' => $bank->subject_id,
                  'subject_name' => $bank->subject->name ?? '-',
                  'title' => $bank->title,
                  'description' => $bank->description ?? '',
                  'target_classes' => $bank->target_classes ?? [],
                  'update_url' => route('question-banks.update', $bank),
                ];
              @endphp
              <button type="button" onclick='openEditBankModal(@json($bankPayload))' class="text-notion-gray hover:text-notion-blue p-1.5 rounded hover:bg-blue-50 transition-colors" title="Edit Bank Soal">
                <i data-lucide="edit-2" class="w-4 h-4"></i>
              </button>
            @endif
            <a href="{{ route('question-banks.show', $bank) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-notion-border rounded text-sm font-medium hover:bg-notion-hover">
              <i data-lucide="list-checks" class="w-4 h-4"></i> Soal
            </a>
            @if(!$bank->isLocked())
              <form method="POST" action="{{ route('question-banks.destroy', $bank) }}" class="inline" onsubmit="return handleConfirmSubmit(event, 'Bank soal {{ $bank->title }} beserta seluruh butir soalnya akan dihapus permanen.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-notion-gray hover:text-notion-red p-1.5 rounded"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
              </form>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="py-10 text-center text-notion-gray text-sm">Belum ada bank soal. Klik <strong>Buat Bank Soal</strong> untuk memulai.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($banks as $idx => $bank)
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('bank', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50">
        <div>
          <p class="font-semibold text-sm text-notion-text">{{ $bank->title }}</p>
          <p class="text-sm text-notion-gray">{{ $bank->subject->name ?? '-' }} • {{ $bank->questions_count }} soal</p>
        </div>
        <i data-lucide="chevron-down" id="bank-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="bank-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Target Kelas</span>
          <span class="font-medium">{{ implode(', ', $bank->target_classes ?? ['Semua Kelas']) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Status</span>
          <span class="font-medium">{{ $bank->is_ready ? 'Siap Pakai' : 'Draft' }}</span>
        </div>
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          @if(!$bank->isLocked())
            <button type="button" onclick='openEditBankModal(@json($bankPayload))' class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Edit</button>
          @endif
          <a href="{{ route('question-banks.show', $bank) }}" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Kelola Soal</a>
          @if(!$bank->isLocked())
            <form method="POST" action="{{ route('question-banks.destroy', $bank) }}" onsubmit="return handleConfirmSubmit(event, 'Bank soal {{ $bank->title }} akan dihapus permanen.')">
              @csrf @method('DELETE')
              <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm">Hapus</button>
            </form>
          @endif
        </div>
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada bank soal.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$banks" />
</div>

@push('modals')

<div id="modal-add-bank" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-add-bank')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-bank-title" class="font-bold text-lg">Buat Bank Soal Baru</h3>
      <button type="button" onclick="closeAppModal('modal-add-bank')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="bank-form" method="POST" action="{{ route('question-banks.store') }}" class="p-6 overflow-y-auto flex-1 space-y-3">
      @csrf
      <input type="hidden" name="_method" id="bank-method" value="POST">
      <div>
        <label class="block text-sm font-medium mb-1">Mata Pelajaran</label>
        <input type="hidden" name="subject_id" id="bank-subject-id" required>
        <input type="hidden" name="binding_key" id="bank-binding-key">
        <div id="bank-subject-tag" class="flex flex-wrap gap-1.5 mb-1.5"></div>
        <div id="search-bank-subject-wrap" class="relative">
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
          <input type="text" id="search-bank-subject" oninput="filterSearchPicker('search-bank-subject', 'bank-subject-list')" placeholder="Ketik untuk mencari mata pelajaran..." class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
        </div>
        <div id="bank-subject-list" class="hidden mt-1.5 max-h-36 overflow-y-auto border border-notion-border rounded-md p-1.5 space-y-0.5 bg-white shadow-sm">
          @if(auth()->user()->isGuru())
            @forelse($guruBindings as $gb)
              @php
                $gbLabel = $gb['subject_name'] . (!empty($gb['classes']) ? ' (' . implode(', ', $gb['classes']) . ')' : '');
              @endphp
              <button type="button" onclick='selectGuruBinding(@json($gb), @json($gbLabel))' class="w-full text-left px-2.5 py-1.5 rounded text-sm hover:bg-notion-hover text-notion-text filter-item" data-label="{{ strtolower($gbLabel) }}">
                {{ $gbLabel }}
              </button>
            @empty
              <p class="p-2 text-sm text-notion-gray">Belum ada mapping mata pelajaran.</p>
            @endforelse
          @else
            @forelse($subjects as $sub)
              <button type="button" onclick="selectBankSubject('{{ $sub->id }}', '{{ addslashes($sub->name) }}')" class="w-full text-left px-2.5 py-1.5 rounded text-sm hover:bg-notion-hover text-notion-text filter-item" data-label="{{ strtolower($sub->name) }}">
                {{ $sub->name }}
              </button>
            @empty
              <p class="p-2 text-sm text-notion-gray">Belum ada mata pelajaran.</p>
            @endforelse
          @endif
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Target Kelas {{ auth()->user()->isGuru() ? '(Otomatis sesuai mapping)' : '' }}</label>
        <div id="bank-class-tags" class="flex flex-wrap gap-1.5 mb-1.5">
          @if(auth()->user()->isGuru())
            <span class="text-sm text-notion-gray">Pilih mata pelajaran untuk menampilkan kelas target.</span>
          @endif
        </div>
        @if(!auth()->user()->isGuru())
        <div class="relative">
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
          <input type="text" id="search-bank-class" oninput="filterSearchPicker('search-bank-class', 'bank-class-list')" placeholder="Ketik untuk mencari kelas (misal: X-E1)..." class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
        </div>
        <div id="bank-class-list" class="hidden mt-1.5 max-h-36 overflow-y-auto border border-notion-border rounded-md p-1.5 grid grid-cols-2 gap-1 bg-white shadow-sm">
          @forelse($classes as $c)
          <label class="flex items-center gap-2 px-2 py-1.5 rounded text-sm cursor-pointer hover:bg-notion-hover filter-item" data-label="{{ strtolower($c) }}">
            <input type="checkbox" name="target_classes[]" value="{{ $c }}" onchange="syncPickerTags('bank-class-list', 'bank-class-tags', 'search-bank-class', this.checked)" class="rounded border-notion-border">
            <span>{{ $c }}</span>
          </label>
          @empty
          <p class="col-span-2 p-2 text-sm text-notion-gray">Belum ada binding kelas. Hubungi Panitia.</p>
          @endforelse
        </div>
        @endif
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Judul Paket Soal</label>
        <input type="text" name="title" id="bank-title-input" required placeholder="Contoh: Sumatif Matematika Kelas X" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Keterangan (Opsional)</label>
        <textarea name="description" id="bank-desc-input" rows="2" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm"></textarea>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-add-bank')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" id="bank-submit-btn" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Buat &amp; Isi Soal</button>
      </div>
    </form>
  </div>
</div>
@endpush

@push('scripts')
<script>
  const STORE_BANK_URL = @json(route('question-banks.store'));
  const IS_GURU = @json(auth()->user()->isGuru());
  const GURU_BINDINGS = @json($guruBindings ?? []);

  function renderReadOnlyBankClasses(classes) {
    const tagsEl = document.getElementById('bank-class-tags');
    if (!tagsEl) return;
    if (!classes || classes.length === 0) {
      tagsEl.innerHTML = '<span class="text-sm text-notion-gray">Belum ada kelas ter-binding untuk mapel ini.</span>';
      return;
    }
    tagsEl.innerHTML = classes.map(c =>
      `<span class="inline-flex items-center px-2.5 py-1 rounded-md bg-notion-sidebar border border-notion-border text-sm text-notion-text font-medium">${c}</span>`
    ).join('');
  }

  function selectGuruBinding(gb, label) {
    selectSinglePicker('bank-subject-id', 'search-bank-subject', 'bank-subject-list', 'bank-subject-tag', String(gb.subject_id), label, 'search-bank-subject-wrap');
    document.getElementById('bank-binding-key').value = gb.key || '';
    renderReadOnlyBankClasses(gb.classes || []);
  }

  function selectBankSubject(subjectId, subjectName) {
    selectSinglePicker('bank-subject-id', 'search-bank-subject', 'bank-subject-list', 'bank-subject-tag', subjectId, subjectName, 'search-bank-subject-wrap');
    document.getElementById('bank-binding-key').value = '';
  }

  function resetBankForm() {
    document.getElementById('bank-form').action = STORE_BANK_URL;
    document.getElementById('bank-method').value = 'POST';
    clearSinglePicker('bank-subject-id', 'bank-subject-tag', 'search-bank-subject-wrap');
    document.getElementById('bank-binding-key').value = '';
    document.getElementById('search-bank-subject').value = '';
    document.getElementById('bank-subject-list').classList.add('hidden');

    if (IS_GURU) {
      document.getElementById('bank-class-tags').innerHTML = '<span class="text-sm text-notion-gray">Pilih mata pelajaran untuk menampilkan kelas target.</span>';
    } else {
      document.getElementById('search-bank-class').value = '';
      document.getElementById('bank-class-list').classList.add('hidden');
      document.querySelectorAll('#bank-class-list input[type="checkbox"]').forEach(cb => cb.checked = false);
      syncPickerTags('bank-class-list', 'bank-class-tags');
    }

    document.getElementById('bank-title-input').value = '';
    document.getElementById('bank-desc-input').value = '';
  }

  function openCreateBankModal() {
    resetBankForm();
    document.getElementById('modal-bank-title').textContent = 'Buat Bank Soal Baru';
    document.getElementById('bank-submit-btn').textContent = 'Buat & Isi Soal';
    if (IS_GURU && GURU_BINDINGS.length === 1) {
      const gb = GURU_BINDINGS[0];
      const lbl = gb.subject_name + (gb.classes?.length ? ` (${gb.classes.join(', ')})` : '');
      selectGuruBinding(gb, lbl);
    }
    openAppModal('modal-add-bank');
  }

  function openEditBankModal(bank) {
    resetBankForm();
    document.getElementById('bank-form').action = bank.update_url;
    document.getElementById('bank-method').value = 'PUT';
    document.getElementById('modal-bank-title').textContent = 'Edit Bank Soal';
    document.getElementById('bank-submit-btn').textContent = 'Simpan Perubahan';

    const targets = bank.target_classes || [];
    if (IS_GURU) {
      const matched = GURU_BINDINGS.find(gb =>
        String(gb.subject_id) === String(bank.subject_id) &&
        JSON.stringify([...(gb.classes || [])].sort()) === JSON.stringify([...targets].sort())
      ) || GURU_BINDINGS.find(gb => String(gb.subject_id) === String(bank.subject_id));
      if (matched) {
        const lbl = matched.subject_name + (matched.classes?.length ? ` (${matched.classes.join(', ')})` : '');
        selectGuruBinding(matched, lbl);
      } else if (bank.subject_id) {
        selectBankSubject(bank.subject_id, bank.subject_name);
        renderReadOnlyBankClasses(targets);
      }
    } else {
      if (bank.subject_id) {
        selectSinglePicker('bank-subject-id', 'search-bank-subject', 'bank-subject-list', 'bank-subject-tag', bank.subject_id, bank.subject_name, 'search-bank-subject-wrap');
      }
      document.querySelectorAll('#bank-class-list input[type="checkbox"]').forEach(cb => {
        cb.checked = targets.includes(cb.value);
      });
      syncPickerTags('bank-class-list', 'bank-class-tags');
    }

    document.getElementById('bank-title-input').value = bank.title || '';
    document.getElementById('bank-desc-input').value = bank.description || '';
    openAppModal('modal-add-bank');
  }
</script>
@endpush
@endsection

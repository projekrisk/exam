@extends('layouts.app')

@section('title', 'Data Guru')
@section('breadcrumb', 'Data Guru')

@section('content')
<div class="space-y-3">
  <i data-lucide="graduation-cap" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Guru Mapel</h1>
  <p class="text-notion-gray text-sm">Pengelolaan data guru mata pelajaran.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-notion-sidebar/40 rounded-t-lg">
    <form method="GET" action="{{ route('teachers.index') }}" class="relative w-full sm:flex-1">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray"></i>
      <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIP atau Nama Guru..."
             class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue">
    </form>

    @if(auth()->user()->isSuperAdmin())
    <button onclick="openCreateTeacherModal()" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i> Tambah Guru
    </button>
    @endif
  </div>

  <div class="hidden md:block">
    <table class="w-full text-left text-sm">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium whitespace-nowrap">NIP</th>
          <th class="py-3 px-4 font-medium whitespace-nowrap">Nama Lengkap</th>
          <th class="py-3 px-4 font-medium whitespace-nowrap">Role</th>
          <th class="py-3 px-4 font-medium">Mata Pelajaran</th>
          <th class="py-3 px-4 font-medium">Kelas</th>
          @if(auth()->user()->isSuperAdmin())
          <th class="py-3 px-4 font-medium text-right whitespace-nowrap">Aksi</th>
          @endif
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($teachers as $t)
        @php
          $groupedBySub = $t->subjects->groupBy('id');
          $bindingsPayload = $groupedBySub->map(function ($rows, $subId) {
            $first = $rows->first();
            $cls = $rows->pluck('pivot.kelas')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
            return [
              'subject_id' => (string) $subId,
              'subject_name' => $first->name,
              'classes' => $cls,
            ];
          })->values()->all();
          $teacherPayload = [
            'nip' => $t->nip ?? $t->username,
            'name' => $t->name,
            'role' => $t->role,
            'bindings' => $bindingsPayload,
            'update_url' => route('teachers.update', $t),
          ];
        @endphp
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 font-mono text-sm font-semibold whitespace-nowrap">{{ $t->username }}</td>
          <td class="py-3 px-4 font-medium whitespace-nowrap">{{ $t->name }}</td>
          <td class="py-3 px-4 whitespace-nowrap">
            <span class="px-2.5 py-0.5 rounded text-sm font-medium {{ $t->role === 'guru' ? 'bg-blue-50 text-notion-blue border border-blue-200' : 'bg-notion-hover text-notion-gray border border-notion-border' }}">{{ ucfirst($t->role) }}</span>
          </td>
          <td class="py-3 px-4">
            <div class="flex flex-wrap gap-1.5">
              @forelse($bindingsPayload as $bp)
                <span class="px-2.5 py-0.5 rounded text-sm bg-notion-sidebar border border-notion-border">{{ $bp['subject_name'] }}</span>
              @empty
                <span class="text-sm text-notion-gray">-</span>
              @endforelse
            </div>
          </td>
          <td class="py-3 px-4 whitespace-nowrap">
            @php
              $hasClasses = collect($bindingsPayload)->contains(fn ($bp) => count($bp['classes']) > 0);
            @endphp
            @if($hasClasses)
              <button type="button"
                      onclick='openTeacherClassesModal(@json(["name" => $t->name, "bindings" => $bindingsPayload]))'
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-notion-border bg-white text-notion-blue text-sm font-medium hover:bg-blue-50 transition-colors">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Lihat
              </button>
            @else
              <span class="text-sm text-notion-gray">-</span>
            @endif
          </td>
          @if(auth()->user()->isSuperAdmin())
          <td class="py-3 px-4 text-right whitespace-nowrap">
            <div class="relative inline-block text-left">
              <button type="button" onclick="toggleTeacherMenu(event, 'teacher-menu-{{ $t->id }}')" class="p-1.5 rounded-md border border-notion-border bg-white text-notion-gray hover:text-notion-text hover:bg-notion-hover transition-colors" title="Menu Aksi">
                <i data-lucide="more-vertical" class="w-4 h-4"></i>
              </button>
              <div id="teacher-menu-{{ $t->id }}" class="teacher-action-menu hidden absolute right-0 mt-1.5 w-44 bg-white border border-notion-border rounded-lg shadow-lg py-1.5 z-40 text-left">
                <button type="button" onclick='openEditTeacherModal(@json($teacherPayload))' class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-text hover:bg-notion-hover transition-colors">
                  <i data-lucide="edit-2" class="w-4 h-4 text-notion-gray"></i> Edit
                </button>
                <form method="POST" action="{{ route('teachers.reset-password', $t) }}" onsubmit="return handleConfirmSubmit(event, 'Password {{ $t->name }} akan direset ke {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}.', 'Konfirmasi Reset Password', 'Ya, Reset', 'danger')">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-red hover:bg-notion-redBg/60 transition-colors">
                    <i data-lucide="key-round" class="w-4 h-4"></i> Reset
                  </button>
                </form>
                <form method="POST" action="{{ route('teachers.destroy', $t) }}" onsubmit="return handleConfirmSubmit(event, 'Akun {{ $t->name }} akan dihapus permanen.')">
                  @csrf @method('DELETE')
                  <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-notion-red hover:bg-notion-redBg/60 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                  </button>
                </form>
              </div>
            </div>
          </td>
          @endif
        </tr>
        @empty
        <tr>
          <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="py-8 text-center text-notion-gray text-sm">Belum ada data guru.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($teachers as $idx => $t)
    @php
      $groupedBySub = $t->subjects->groupBy('id');
      $bindingsMob = $groupedBySub->map(function ($rows, $subId) {
        $first = $rows->first();
        $cls = $rows->pluck('pivot.kelas')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        return ['subject_id' => (string) $subId, 'subject_name' => $first->name, 'classes' => $cls];
      })->values()->all();
      $teacherPayloadMob = [
        'nip' => $t->nip ?? $t->username,
        'name' => $t->name,
        'role' => $t->role,
        'bindings' => $bindingsMob,
        'update_url' => route('teachers.update', $t),
      ];
    @endphp
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('teacher', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50 transition-colors">
        <div>
          <p class="font-semibold text-sm text-notion-text">{{ $t->name }}</p>
          <p class="text-sm text-notion-gray font-mono">NIP: {{ $t->username }}</p>
        </div>
        <i data-lucide="chevron-down" id="teacher-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="teacher-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Role</span>
          <span class="font-medium">{{ ucfirst($t->role) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Kelas</span>
          @php
            $hasClassesMob = collect($bindingsMob)->contains(fn ($bp) => count($bp['classes']) > 0);
          @endphp
          @if($hasClassesMob)
            <button type="button"
                    onclick='openTeacherClassesModal(@json(["name" => $t->name, "bindings" => $bindingsMob]))'
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-notion-border bg-white text-notion-blue text-sm font-medium hover:bg-blue-50 transition-colors">
              <i data-lucide="eye" class="w-3.5 h-3.5"></i> Lihat
            </button>
          @else
            <span class="text-notion-gray">-</span>
          @endif
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          <button type="button" onclick='openEditTeacherModal(@json($teacherPayloadMob))' class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Edit</button>
          <form method="POST" action="{{ route('teachers.reset-password', $t) }}" onsubmit="return handleConfirmSubmit(event, 'Password {{ $t->name }} akan direset ke {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}.', 'Konfirmasi Reset Password', 'Ya, Reset', 'danger')">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red border border-red-200 rounded text-sm font-medium">Reset</button>
          </form>
          <form method="POST" action="{{ route('teachers.destroy', $t) }}" onsubmit="return handleConfirmSubmit(event, 'Akun {{ $t->name }} akan dihapus permanen.')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm">Hapus</button>
          </form>
        </div>
        @endif
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada data guru.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$teachers" />
</div>

@push('modals')

<div id="modal-add-teacher" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-add-teacher')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-none sm:rounded-xl w-full h-full sm:h-auto sm:max-w-lg sm:max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-teacher-title" class="font-bold text-lg">Tambah Guru</h3>
      <button type="button" onclick="closeAppModal('modal-add-teacher')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="teacher-form" method="POST" action="{{ route('teachers.store') }}" class="flex flex-col flex-1 overflow-hidden">
      @csrf
      <input type="hidden" name="_method" id="teacher-method" value="POST">
      <div class="p-6 overflow-y-auto flex-1 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1">NIP (Username Login)</label>
            <input type="text" name="nip" id="teacher-nip" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Peran</label>
            <select name="role" id="teacher-role" class="modern-select">
              <option value="guru">Guru Mapel</option>
              <option value="proktor">Proktor Ruang</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Nama Lengkap &amp; Gelar</label>
          <input type="text" name="name" id="teacher-name" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
        </div>

        <div class="space-y-2.5 pt-2 border-t border-notion-border">
          <div class="flex items-center justify-between gap-2">
            <label class="text-sm font-semibold text-notion-text">Binding Mata Pelajaran &amp; Kelas</label>
            <button type="button" onclick="addTeacherBindingRow()" class="px-2.5 py-1 bg-white border border-notion-border rounded-md text-sm font-medium text-notion-text hover:bg-notion-hover inline-flex items-center gap-1">
              <i data-lucide="plus" class="w-4 h-4"></i> Tambah Mapel
            </button>
          </div>
          <div id="teacher-bindings-list" class="space-y-3"></div>
        </div>

        <p id="teacher-pass-hint" class="text-sm text-notion-gray">Password awal otomatis disetel ke: <strong>{{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}</strong></p>
      </div>
      <div class="sticky bottom-0 bg-white px-6 py-3.5 border-t border-notion-border flex justify-end gap-2 shrink-0">
        <button type="button" onclick="closeAppModal('modal-add-teacher')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" id="teacher-submit-btn" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endpush

@push('scripts')
<script>
  const STORE_TEACHER_URL = @json(route('teachers.store'));
  const ALL_SUBJECTS = @json($subjects->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])->values());
  const ALL_CLASSES = @json($classes->values());
  let bindingRowCounter = 0;

  function toggleTeacherMenu(event, menuId) {
    event.stopPropagation();
    const target = document.getElementById(menuId);
    if (!target) return;
    const isHidden = target.classList.contains('hidden');
    document.querySelectorAll('.teacher-action-menu').forEach(m => m.classList.add('hidden'));
    if (isHidden) target.classList.remove('hidden');
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.teacher-action-menu').forEach(m => m.classList.add('hidden'));
  });

  function addTeacherBindingRow(data = {}) {
    const idx = bindingRowCounter++;
    const container = document.getElementById('teacher-bindings-list');
    const row = document.createElement('div');
    row.className = 'p-3.5 bg-notion-sidebar/40 border border-notion-border rounded-lg space-y-2.5 teacher-binding-row';

    const subHiddenId = `bind-sub-id-${idx}`;
    const subTagId = `bind-sub-tag-${idx}`;
    const subSearchId = `bind-sub-search-${idx}`;
    const subListId = `bind-sub-list-${idx}`;

    const clsTagsId = `bind-cls-tags-${idx}`;
    const clsSearchId = `bind-cls-search-${idx}`;
    const clsListId = `bind-cls-list-${idx}`;

    const subItemsHtml = ALL_SUBJECTS.map(s => `
      <button type="button" onclick="selectSinglePicker('${subHiddenId}', '${subSearchId}', '${subListId}', '${subTagId}', '${s.id}', '${s.name.replace(/'/g, "\\'")}')" class="w-full text-left px-2.5 py-1.5 rounded text-sm hover:bg-notion-hover text-notion-text filter-item" data-label="${s.name.toLowerCase()}">
        ${s.name}
      </button>
    `).join('');

    const selectedClasses = data.classes || [];
    const clsItemsHtml = ALL_CLASSES.map(c => `
      <label class="flex items-center gap-2 px-2 py-1.5 rounded text-sm cursor-pointer hover:bg-notion-hover filter-item" data-label="${c.toLowerCase()}">
        <input type="checkbox" name="bindings[${idx}][classes][]" value="${c}" ${selectedClasses.includes(c) ? 'checked' : ''} onchange="syncPickerTags('${clsListId}', '${clsTagsId}', '${clsSearchId}', this.checked)" class="rounded border-notion-border">
        <span>${c}</span>
      </label>
    `).join('');

    row.innerHTML = `
      <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-semibold text-notion-gray">Mata Pelajaran</span>
        <button type="button" onclick="this.closest('.teacher-binding-row').remove()" class="text-notion-gray hover:text-notion-red p-1 rounded" title="Hapus Baris">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
      <div>
        <input type="hidden" name="bindings[${idx}][subject_id]" id="${subHiddenId}" value="${data.subject_id || ''}">
        <div id="${subTagId}" class="flex flex-wrap gap-1.5 mb-1.5"></div>
        <div class="relative">
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
          <input type="text" id="${subSearchId}" oninput="filterSearchPicker('${subSearchId}', '${subListId}')" placeholder="Cari mata pelajaran..." class="w-full border border-notion-border rounded-md pl-9 pr-3 py-1.5 text-sm bg-white">
        </div>
        <div id="${subListId}" class="hidden mt-1.5 max-h-32 overflow-y-auto border border-notion-border rounded-md p-1.5 space-y-0.5 bg-white shadow-sm">${subItemsHtml}</div>
      </div>
      <div>
        <span class="text-sm font-medium text-notion-gray block mb-1">Kelas untuk Mapel Ini</span>
        <div id="${clsTagsId}" class="flex flex-wrap gap-1.5 mb-1.5"></div>
        <div class="relative">
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
          <input type="text" id="${clsSearchId}" oninput="filterSearchPicker('${clsSearchId}', '${clsListId}')" placeholder="Cari kelas (misal: X-E1)..." class="w-full border border-notion-border rounded-md pl-9 pr-3 py-1.5 text-sm bg-white">
        </div>
        <div id="${clsListId}" class="hidden mt-1.5 max-h-32 overflow-y-auto border border-notion-border rounded-md p-1.5 grid grid-cols-2 sm:grid-cols-3 gap-1 bg-white shadow-sm">${clsItemsHtml}</div>
      </div>
    `;

    container.appendChild(row);

    if (data.subject_id && data.subject_name) {
      selectSinglePicker(subHiddenId, subSearchId, subListId, subTagId, data.subject_id, data.subject_name);
    }
    syncPickerTags(clsListId, clsTagsId);
    lucide.createIcons();
  }

  function setTeacherRoleValue(val) {
    const sel = document.getElementById('teacher-role');
    if (!sel) return;
    sel.value = val;
    const wrapper = sel.nextElementSibling;
    const opt = Array.from(sel.options).find(o => o.value === val);
    if (wrapper && opt) {
      const span = wrapper.querySelector('button span');
      if (span) span.textContent = opt.text;
    }
  }

  function resetTeacherForm() {
    document.getElementById('teacher-form').action = STORE_TEACHER_URL;
    document.getElementById('teacher-method').value = 'POST';
    document.getElementById('teacher-nip').value = '';
    document.getElementById('teacher-name').value = '';
    setTeacherRoleValue('guru');
    document.getElementById('teacher-bindings-list').innerHTML = '';
  }

  function openCreateTeacherModal() {
    resetTeacherForm();
    document.getElementById('modal-teacher-title').textContent = 'Tambah Guru';
    document.getElementById('teacher-submit-btn').textContent = 'Simpan';
    document.getElementById('teacher-pass-hint').classList.remove('hidden');
    addTeacherBindingRow({});
    openAppModal('modal-add-teacher');
  }

  function openEditTeacherModal(t) {
    resetTeacherForm();
    document.getElementById('teacher-form').action = t.update_url;
    document.getElementById('teacher-method').value = 'PUT';
    document.getElementById('modal-teacher-title').textContent = 'Edit Guru';
    document.getElementById('teacher-submit-btn').textContent = 'Simpan Perubahan';
    document.getElementById('teacher-pass-hint').classList.add('hidden');

    document.getElementById('teacher-nip').value = t.nip || '';
    document.getElementById('teacher-name').value = t.name || '';
    setTeacherRoleValue(t.role || 'guru');

    const bindings = t.bindings || [];
    if (bindings.length > 0) {
      bindings.forEach(b => addTeacherBindingRow(b));
    } else {
      addTeacherBindingRow({});
    }

    openAppModal('modal-add-teacher');
  }

  function openTeacherClassesModal(payload) {
    const titleEl = document.getElementById('teacher-classes-title');
    const bodyEl = document.getElementById('teacher-classes-body');
    if (!titleEl || !bodyEl) return;

    titleEl.textContent = 'Kelas Diampu • ' + (payload.name || '');
    const bindings = (payload.bindings || []).filter(b => (b.classes || []).length > 0);

    if (bindings.length === 0) {
      bodyEl.innerHTML = '<p class="text-sm text-notion-gray">Belum ada kelas yang diampu.</p>';
    } else {
      bodyEl.innerHTML = bindings.map(b => {
        const pills = (b.classes || []).map(k =>
          `<span class="px-2.5 py-1 rounded-md text-sm bg-notion-hover border border-notion-border font-medium text-notion-text">${k}</span>`
        ).join('');
        return `
          <div class="p-3 rounded-lg bg-notion-sidebar/50 border border-notion-border space-y-2">
            <p class="text-sm font-semibold text-notion-text">${b.subject_name || '-'}</p>
            <div class="flex flex-wrap gap-1.5">${pills}</div>
          </div>
        `;
      }).join('');
    }

    document.getElementById('modal-teacher-classes').classList.remove('hidden');
  }

  function closeTeacherClassesModal() {
    document.getElementById('modal-teacher-classes').classList.add('hidden');
  }
</script>

<div id="modal-teacher-classes" onclick="if(event.target === this) closeTeacherClassesModal()" class="hidden fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4">
  <div class="bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-xl border border-notion-border shadow-xl overflow-hidden flex flex-col max-h-[80vh]">
    <div class="sm:hidden flex justify-center pt-2.5 pb-1">
      <div class="w-10 h-1 rounded-full bg-notion-border"></div>
    </div>
    <div class="px-5 py-3.5 border-b border-notion-border flex items-center justify-between gap-3">
      <h3 id="teacher-classes-title" class="text-base font-semibold text-notion-text truncate">Detail Kelas</h3>
      <button type="button" onclick="closeTeacherClassesModal()" class="p-1 rounded-md text-notion-gray hover:bg-notion-hover">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <div id="teacher-classes-body" class="p-5 overflow-y-auto space-y-3"></div>
    <div class="px-5 py-3 border-t border-notion-border bg-notion-sidebar/30 flex justify-end">
      <button type="button" onclick="closeTeacherClassesModal()" class="w-full sm:w-auto px-4 py-2 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black transition-colors">
        Tutup
      </button>
    </div>
  </div>
</div>
@endpush
@endsection

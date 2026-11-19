@extends('layouts.app')

@section('title', 'Mata Pelajaran')
@section('breadcrumb', 'Mata Pelajaran')

@section('content')
<div class="space-y-3">
  <i data-lucide="layers" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Mata Pelajaran</h1>
  <p class="text-notion-gray text-sm">Pengelolaan daftar mata pelajaran yang digunakan pada bank soal.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-notion-sidebar/40">
    <form method="GET" action="{{ route('subjects.index') }}" class="relative w-full sm:flex-1">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray"></i>
      <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode atau nama mata pelajaran..."
             class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue">
    </form>

    @if(auth()->user()->isSuperAdmin())
    <button onclick="openCreateSubjectModal()" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i> Tambah Mapel
    </button>
    @endif
  </div>

  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium whitespace-nowrap">Kode</th>
          <th class="py-3 px-4 font-medium whitespace-nowrap">Nama Mata Pelajaran</th>
          <th class="py-3 px-4 font-medium">Guru</th>
          <th class="py-3 px-4 font-medium text-center whitespace-nowrap">Bank Soal</th>
          @if(auth()->user()->isSuperAdmin())
          <th class="py-3 px-4 font-medium text-right whitespace-nowrap">Aksi</th>
          @endif
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($subjects as $sub)
        @php
          $uniqueTeachers = $sub->teachers->unique('id')->values();
          $subPayload = [
            'code' => $sub->code,
            'name' => $sub->name,
            'update_url' => route('subjects.update', $sub),
          ];
        @endphp
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 font-mono text-sm font-semibold whitespace-nowrap">{{ $sub->code }}</td>
          <td class="py-3 px-4 font-medium whitespace-nowrap">{{ $sub->name }}</td>
          <td class="py-3 px-4">
            <div class="flex flex-wrap gap-1.5">
              @forelse($uniqueTeachers as $guru)
                <span class="px-2.5 py-0.5 rounded text-sm bg-notion-sidebar border border-notion-border">{{ $guru->name }}</span>
              @empty
                <span class="text-sm text-notion-gray">-</span>
              @endforelse
            </div>
          </td>
          <td class="py-3 px-4 text-center tnum">{{ $sub->question_banks_count }}</td>
          @if(auth()->user()->isSuperAdmin())
          <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
            <button type="button" onclick='openEditSubjectModal(@json($subPayload))' class="text-notion-gray hover:text-notion-blue hover:bg-blue-50 p-1.5 rounded transition-colors" title="Edit">
              <i data-lucide="edit-2" class="w-4 h-4"></i>
            </button>
            <form method="POST" action="{{ route('subjects.destroy', $sub) }}" class="inline" onsubmit="return handleConfirmSubmit(event, 'Mata pelajaran {{ $sub->name }} akan dihapus permanen.')">
              @csrf @method('DELETE')
              <button type="submit" class="text-notion-gray hover:text-notion-red hover:bg-red-50 p-1.5 rounded transition-colors" title="Hapus"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </form>
          </td>
          @endif
        </tr>
        @empty
        <tr>
          <td colspan="{{ auth()->user()->isSuperAdmin() ? 5 : 4 }}" class="py-8 text-center text-notion-gray text-sm">Belum ada mata pelajaran.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($subjects as $idx => $sub)
    @php
      $uniqueTeachersMob = $sub->teachers->unique('id')->values();
      $subPayloadMob = [
        'code' => $sub->code,
        'name' => $sub->name,
        'update_url' => route('subjects.update', $sub),
      ];
    @endphp
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('sub', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50">
        <div>
          <p class="font-semibold text-sm">{{ $sub->name }}</p>
          <p class="text-sm text-notion-gray font-mono">{{ $sub->code }}</p>
        </div>
        <i data-lucide="chevron-down" id="sub-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="sub-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div>
          <span class="text-notion-gray block mb-1">Guru</span>
          <div class="flex flex-wrap gap-1.5">
            @forelse($uniqueTeachersMob as $guru)
              <span class="px-2 py-0.5 rounded bg-white border border-notion-border">{{ $guru->name }}</span>
            @empty
              <span class="text-notion-gray">-</span>
            @endforelse
          </div>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Jumlah Bank Soal</span>
          <span class="font-medium tnum">{{ $sub->question_banks_count }}</span>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="pt-2 border-t border-notion-border flex justify-end gap-2">
          <button type="button" onclick='openEditSubjectModal(@json($subPayloadMob))' class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium inline-flex items-center gap-1.5">
            <i data-lucide="edit-2" class="w-4 h-4"></i> Edit
          </button>
          <form method="POST" action="{{ route('subjects.destroy', $sub) }}" onsubmit="return handleConfirmSubmit(event, 'Mata pelajaran {{ $sub->name }} akan dihapus permanen.')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm inline-flex items-center gap-1.5">
              <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
            </button>
          </form>
        </div>
        @endif
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada mata pelajaran.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$subjects" />
</div>

@push('modals')

<div id="modal-subject" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-subject')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-subject-title" class="font-bold text-lg">Tambah Mata Pelajaran</h3>
      <button type="button" onclick="closeAppModal('modal-subject')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="subject-form" method="POST" action="{{ route('subjects.store') }}" class="p-6 overflow-y-auto flex-1 space-y-3">
      @csrf
      <input type="hidden" name="_method" id="subject-method" value="POST">
      <div>
        <label class="block text-sm font-medium mb-1">Kode Mapel</label>
        <input type="text" name="code" id="subject-code" required placeholder="Contoh: MTK-W" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono uppercase">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nama Mata Pelajaran</label>
        <input type="text" name="name" id="subject-name" required placeholder="Matematika Wajib" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-subject')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" id="subject-submit-btn" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Simpan Mapel</button>
      </div>
    </form>
  </div>
</div>
@endpush

@push('scripts')
<script>
  const STORE_SUBJECT_URL = @json(route('subjects.store'));

  function openCreateSubjectModal() {
    document.getElementById('subject-form').action = STORE_SUBJECT_URL;
    document.getElementById('subject-method').value = 'POST';
    document.getElementById('modal-subject-title').textContent = 'Tambah Mata Pelajaran';
    document.getElementById('subject-submit-btn').textContent = 'Simpan Mapel';
    document.getElementById('subject-code').value = '';
    document.getElementById('subject-name').value = '';
    openAppModal('modal-subject');
  }

  function openEditSubjectModal(sub) {
    document.getElementById('subject-form').action = sub.update_url;
    document.getElementById('subject-method').value = 'PUT';
    document.getElementById('modal-subject-title').textContent = 'Edit Mata Pelajaran';
    document.getElementById('subject-submit-btn').textContent = 'Simpan Perubahan';
    document.getElementById('subject-code').value = sub.code || '';
    document.getElementById('subject-name').value = sub.name || '';
    openAppModal('modal-subject');
  }
</script>
@endpush
@endsection

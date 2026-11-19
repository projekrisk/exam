@extends('layouts.app')

@section('title', 'Data Siswa')
@section('breadcrumb', 'Data Siswa')

@section('content')
<div class="space-y-3">
  <i data-lucide="users" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Data Siswa</h1>
  <p class="text-notion-gray text-sm">Pengelolaan data peserta ujian.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-notion-sidebar/40">
    <form method="GET" action="{{ route('students.index') }}" class="flex flex-col sm:flex-row gap-2 w-full lg:flex-1">
      <div class="relative w-full sm:flex-1">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIS atau Nama..."
               onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}"
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue">
      </div>
      <div class="w-full sm:w-48 shrink-0">
        <select name="kelas" onchange="this.form.submit()" class="modern-select">
          <option value="">Semua Kelas</option>
          @foreach($classes as $c)
            <option value="{{ $c }}" {{ $kelas === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="hidden" aria-hidden="true"></button>
    </form>

    @if(auth()->user()->isSuperAdmin())
    <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 w-full lg:w-auto shrink-0">
      <button onclick="openAppModal('modal-import')" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-text text-white text-sm font-medium rounded-md hover:bg-black transition-colors flex items-center justify-center gap-1.5 shadow-sm">
        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Import
      </button>
      <button onclick="openCreateStudentModal()" class="w-full sm:w-auto px-3.5 py-2 border border-transparent bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm">
        <i data-lucide="plus" class="w-4 h-4"></i> Tambah
      </button>
    </div>
    @endif
  </div>

  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium w-14 text-center">No</th>
          <th class="py-3 px-4 font-medium">NIS</th>
          <th class="py-3 px-4 font-medium">NISN</th>
          <th class="py-3 px-4 font-medium">Nama Lengkap</th>
          <th class="py-3 px-4 font-medium">Kelas</th>
          <th class="py-3 px-4 font-medium">Ruang</th>
          @if(auth()->user()->isSuperAdmin())
          <th class="py-3 px-4 font-medium text-right">Aksi</th>
          @endif
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($students as $idx => $s)
        @php
          $studentPayload = [
            'nis' => $s->nis,
            'nisn' => $s->nisn,
            'name' => $s->name,
            'kelas' => $s->kelas,
            'ruang' => $s->ruang ?? '',
            'update_url' => route('students.update', $s),
          ];
        @endphp
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 text-center text-notion-gray tnum">{{ $students->firstItem() + $idx }}</td>
          <td class="py-3 px-4 font-mono text-sm text-notion-text font-semibold">{{ $s->nis }}</td>
          <td class="py-3 px-4 font-mono text-sm text-notion-gray">{{ $s->nisn }}</td>
          <td class="py-3 px-4 font-medium text-notion-text">{{ $s->name }}</td>
          <td class="py-3 px-4"><span class="px-2.5 py-0.5 rounded text-sm bg-notion-hover border border-notion-border">{{ $s->kelas }}</span></td>
          <td class="py-3 px-4 text-notion-gray">{{ $s->ruang ?? '-' }}</td>
          @if(auth()->user()->isSuperAdmin())
          <td class="py-3 px-4 text-right space-x-1">
            <button type="button" onclick='openEditStudentModal(@json($studentPayload))' class="text-notion-gray hover:text-notion-blue hover:bg-blue-50 p-1.5 rounded transition-colors" title="Edit">
              <i data-lucide="edit-2" class="w-4 h-4"></i>
            </button>
            <form method="POST" action="{{ route('students.destroy', $s) }}" onsubmit="return handleConfirmSubmit(event, 'Data siswa {{ $s->name }} akan dihapus permanen.')" class="inline">
              @csrf @method('DELETE')
              <button type="submit" class="text-notion-gray hover:text-notion-red hover:bg-red-50 p-1.5 rounded transition-colors" title="Hapus">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
              </button>
            </form>
          </td>
          @endif
        </tr>
        @empty
        <tr>
          <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="py-10 text-center text-notion-gray text-sm">Belum ada data siswa.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($students as $idx => $s)
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('student', {{ $idx }})" class="p-4 flex items-center justify-between cursor-pointer hover:bg-notion-hover/50 transition-colors">
        <div>
          <p class="font-semibold text-sm text-notion-text">{{ $s->name }}</p>
          <p class="text-sm text-notion-gray font-mono">NIS: {{ $s->nis }}</p>
        </div>
        <i data-lucide="chevron-down" id="student-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="student-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">NISN</span>
          <span class="font-mono font-medium">{{ $s->nisn }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Kelas</span>
          <span class="px-2.5 py-0.5 rounded bg-notion-hover border border-notion-border font-medium">{{ $s->kelas }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Ruang</span>
          <span class="font-medium">{{ $s->ruang ?? '-' }}</span>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="pt-2 border-t border-notion-border flex justify-end gap-2">
          @php
            $studentPayloadMob = [
              'nis' => $s->nis,
              'nisn' => $s->nisn,
              'name' => $s->name,
              'kelas' => $s->kelas,
              'ruang' => $s->ruang ?? '',
              'update_url' => route('students.update', $s),
            ];
          @endphp
          <button type="button" onclick='openEditStudentModal(@json($studentPayloadMob))' class="px-3 py-1.5 bg-white border border-notion-border text-notion-text text-sm font-medium rounded inline-flex items-center gap-1.5">
            <i data-lucide="edit-2" class="w-4 h-4"></i> Edit
          </button>
          <form method="POST" action="{{ route('students.destroy', $s) }}" onsubmit="return handleConfirmSubmit(event, 'Data siswa {{ $s->name }} akan dihapus permanen.')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red text-sm font-medium rounded inline-flex items-center gap-1.5">
              <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
            </button>
          </form>
        </div>
        @endif
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Belum ada data siswa.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$students" />
</div>

@push('modals')

<div id="modal-import" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-import')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 class="font-bold text-lg">Import Data Siswa (.xlsx)</h3>
      <button type="button" onclick="closeAppModal('modal-import')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <div class="p-6 overflow-y-auto flex-1 space-y-4">
      <p class="text-sm text-notion-gray leading-relaxed">
        Unggah file Excel dengan kolom header: <code>NIS</code>, <code>NISN</code>, <code>Nama</code>, <code>Kelas</code>, dan <code>Ruang</code>. Data dengan NIS yang sama akan otomatis diperbarui.
      </p>
      <a href="{{ route('students.template') }}" class="w-full px-3.5 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors flex items-center justify-center gap-2 shadow-sm">
        <i data-lucide="download" class="w-4 h-4 text-notion-gray"></i> Unduh Template Siswa (.xlsx)
      </a>
      <form id="import-student-form" method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" onsubmit="return handleStudentImport(event)" class="space-y-4">
        @csrf
        <input type="file" name="file" id="import-student-file" accept=".xlsx,.xls,.csv" required class="modern-file" data-placeholder="Pilih file Excel (.xlsx)...">
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="closeAppModal('modal-import')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
          <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Unggah &amp; Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="modal-add-student" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-add-student')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-student-title" class="font-bold text-lg">Tambah Siswa Baru</h3>
      <button type="button" onclick="closeAppModal('modal-add-student')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="student-form" method="POST" action="{{ route('students.store') }}" class="p-6 overflow-y-auto flex-1 space-y-3">
      @csrf
      <input type="hidden" name="_method" id="student-method" value="POST">
      <div>
        <label class="block text-sm font-medium mb-1">NIS (Username)</label>
        <input type="text" name="nis" id="student-nis" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">NISN (Password)</label>
        <input type="text" name="nisn" id="student-nisn" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
        <input type="text" name="name" id="student-name" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1">Kelas</label>
          <input type="text" name="kelas" id="student-kelas" required placeholder="Contoh: X-1" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Ruang Ujian</label>
          <input type="text" name="ruang" id="student-ruang" placeholder="Contoh: Ruang 1" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
        </div>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-add-student')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" id="student-submit-btn" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div id="import-progress-overlay" class="hidden fixed inset-0 w-screen h-screen z-[130] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-white border border-notion-border rounded-xl max-w-sm w-full p-6 shadow-xl space-y-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-blue-50 text-notion-blue flex items-center justify-center shrink-0">
        <i data-lucide="file-spreadsheet" class="w-5 h-5 animate-pulse"></i>
      </div>
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-base text-notion-text">Mengimpor Data Siswa</h3>
        <p id="import-progress-label" class="text-sm text-notion-gray truncate">Menyiapkan file Excel...</p>
      </div>
      <span id="import-progress-pct" class="text-sm font-bold text-notion-blue tnum">0%</span>
    </div>

    <div class="w-full h-2.5 bg-notion-hover rounded-full overflow-hidden">
      <div id="import-progress-bar" class="h-full bg-notion-blue rounded-full transition-all duration-300" style="width: 5%"></div>
    </div>

    <p class="text-sm text-notion-gray">Mohon tunggu, jangan tutup halaman ini hingga proses selesai.</p>
  </div>
</div>
@endpush

@push('scripts')
<script>
  const STORE_STUDENT_URL = @json(route('students.store'));

  function openCreateStudentModal() {
    document.getElementById('student-form').action = STORE_STUDENT_URL;
    document.getElementById('student-method').value = 'POST';
    document.getElementById('modal-student-title').textContent = 'Tambah Siswa Baru';
    document.getElementById('student-submit-btn').textContent = 'Simpan';
    document.getElementById('student-nis').value = '';
    document.getElementById('student-nisn').value = '';
    document.getElementById('student-name').value = '';
    document.getElementById('student-kelas').value = '';
    document.getElementById('student-ruang').value = '';
    openAppModal('modal-add-student');
  }

  function openEditStudentModal(s) {
    document.getElementById('student-form').action = s.update_url;
    document.getElementById('student-method').value = 'PUT';
    document.getElementById('modal-student-title').textContent = 'Edit Data Siswa';
    document.getElementById('student-submit-btn').textContent = 'Simpan Perubahan';
    document.getElementById('student-nis').value = s.nis || '';
    document.getElementById('student-nisn').value = s.nisn || '';
    document.getElementById('student-name').value = s.name || '';
    document.getElementById('student-kelas').value = s.kelas || '';
    document.getElementById('student-ruang').value = s.ruang || '';
    openAppModal('modal-add-student');
  }

  function handleStudentImport(event) {
    event.preventDefault();
    const form = event.target;
    const fileInput = document.getElementById('import-student-file');
    if (!fileInput || !fileInput.files || !fileInput.files.length) {
      showToast('error', 'Pilih file Excel (.xlsx) terlebih dahulu.');
      return false;
    }

    closeAppModal('modal-import');
    const overlay = document.getElementById('import-progress-overlay');
    const bar = document.getElementById('import-progress-bar');
    const pct = document.getElementById('import-progress-pct');
    const label = document.getElementById('import-progress-label');

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
      label.textContent = 'Memproses & menyimpan ke database...';
    };

    xhr.onload = function () {
      if (xhr.status >= 400) {
        overlay.classList.add('hidden');
        showToast('error', 'Gagal mengimpor data siswa. Pastikan format file (.xlsx/.xls/.csv) dan kolom sesuai template.');
        return;
      }
      bar.style.width = '100%';
      pct.textContent = '100%';
      label.textContent = 'Selesai! Memuat ulang data...';
      setTimeout(() => {
        window.location.href = xhr.responseURL || window.location.href;
      }, 250);
    };

    xhr.onerror = function () {
      overlay.classList.add('hidden');
      showToast('error', 'Gagal mengimpor file. Periksa koneksi Anda.');
    };

    xhr.send(new FormData(form));
    return false;
  }
</script>
@endpush
@endsection

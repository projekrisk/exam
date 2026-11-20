@extends('layouts.app')

@section('title', 'Kartu Ujian')
@section('breadcrumb', 'Kartu Ujian')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
  <div class="space-y-3">
    <i data-lucide="id-card" class="w-12 h-12 text-notion-text/80"></i>
    <h1 class="text-3xl font-bold tracking-tight">Kartu Ujian</h1>
    <p class="text-notion-gray text-sm">Pengelolaan dan pencetakan kartu peserta ujian, pembagian ruang otomatis, serta denah tempat duduk.</p>
  </div>
  @if(auth()->user()->isSuperAdmin())
    <div class="flex flex-wrap items-center gap-2 shrink-0">
      <a href="{{ route('exam-cards.seating-plan') }}" target="_blank" class="px-3.5 py-2 bg-white border border-notion-border text-notion-text rounded-md text-sm font-medium hover:bg-notion-hover transition-colors inline-flex items-center gap-1.5 shadow-sm">
        <i data-lucide="layout-grid" class="w-4 h-4 text-notion-gray"></i> Denah Duduk
      </a>
      <button type="button" onclick="openAppModal('modal-generate-rooms')" class="px-3.5 py-2 bg-notion-text text-white rounded-md text-sm font-medium hover:bg-black transition-colors inline-flex items-center gap-1.5 shadow-sm">
        <i data-lucide="sparkles" class="w-4 h-4"></i> Generate Ruang
      </button>
    </div>
  @endif
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border bg-notion-sidebar/40 flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
    <form method="GET" action="{{ route('exam-cards.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 w-full">
      <div class="relative flex-1 min-w-[200px]">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN..."
               class="w-full pl-9 pr-3 py-2 text-sm border border-notion-border rounded-md bg-white">
      </div>
      <div class="sm:w-[190px] shrink-0">
        <select name="kelas" onchange="this.form.submit()" class="modern-select w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white">
          <option value="">Semua Kelas</option>
          @foreach($kelasList as $kelas)
            <option value="{{ $kelas }}" {{ $selectedKelas === $kelas ? 'selected' : '' }}>Kelas {{ $kelas }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="px-3.5 py-2 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors whitespace-nowrap shrink-0">
        Filter
      </button>
      <a href="{{ route('exam-cards.print', ['kelas' => $selectedKelas !== '' ? $selectedKelas : 'all']) }}" target="_blank"
         class="px-4 py-2 bg-notion-blue text-white rounded-md text-sm font-medium hover:bg-notion-blueHover transition-colors inline-flex items-center justify-center gap-2 whitespace-nowrap shrink-0">
        <i data-lucide="printer" class="w-4 h-4"></i>
        {{ $selectedKelas !== '' ? 'Cetak Kelas ' . $selectedKelas : 'Cetak Semua Kelas' }}
      </a>
      @if($search !== '' || $selectedKelas !== '')
        <a href="{{ route('exam-cards.index') }}" class="px-3 py-2 text-sm text-notion-gray hover:text-notion-text text-center shrink-0">Reset</a>
      @endif
    </form>

    <button type="button" id="btn-print-selected" onclick="submitSelectedPrint()" class="hidden px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700 transition-colors inline-flex items-center justify-center gap-2 whitespace-nowrap shrink-0">
      <i data-lucide="printer" class="w-4 h-4"></i> Cetak Terpilih (<span id="selected-count">0</span>)
    </button>
  </div>

  <form id="bulk-print-form" method="GET" action="{{ route('exam-cards.print') }}" target="_blank">

    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
          <tr>
            <th class="py-3 px-4 w-10">
              <input type="checkbox" id="check-all-cards" onchange="toggleAllCardChecks(this)" class="rounded border-notion-border">
            </th>
            <th class="py-3 px-4 font-medium">Nama Siswa</th>
            <th class="py-3 px-4 font-medium">NIS / NISN</th>
            <th class="py-3 px-4 font-medium">Kelas</th>
            <th class="py-3 px-4 font-medium">Ruang</th>
            <th class="py-3 px-4 font-medium text-right">Cetak Satuan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-notion-border">
          @forelse($students as $student)
          <tr class="hover:bg-notion-hover/50">
            <td class="py-3 px-4">
              <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" onchange="updateSelectedPrintCount()" class="card-student-check rounded border-notion-border">
            </td>
            <td class="py-3 px-4 font-semibold text-notion-text">
              {{ $student->name }}
              @if(!$student->is_active)
                <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded bg-notion-hover text-notion-gray">Nonaktif</span>
              @endif
            </td>
            <td class="py-3 px-4 font-mono text-sm text-notion-gray">{{ $student->nis }} / {{ $student->nisn }}</td>
            <td class="py-3 px-4">
              <span class="px-2 py-0.5 rounded bg-notion-sidebar border border-notion-border text-sm font-medium">{{ $student->kelas }}</span>
            </td>
            <td class="py-3 px-4 text-notion-text">
              {{ $student->ruang ?: '-' }}
              @if($student->seat_number)
                <span class="ml-1 px-1.5 py-0.5 text-xs font-mono rounded bg-notion-sidebar border border-notion-border text-notion-gray">Meja {{ str_pad((string) $student->seat_number, 2, '0', STR_PAD_LEFT) }}</span>
              @endif
            </td>
            <td class="py-3 px-4 text-right">
              <a href="{{ route('exam-cards.print', ['student_id' => $student->id]) }}" target="_blank"
                 class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-notion-blue"></i> Cetak Kartu
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-10 text-center text-notion-gray text-sm">Tidak ada data siswa yang sesuai.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="md:hidden divide-y divide-notion-border">
      @forelse($students as $idx => $student)
      <div class="bg-white flex flex-col">
        <div onclick="toggleMobileRow('card', {{ $idx }})" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-notion-hover/50">
          <div class="min-w-0">
            <p class="font-semibold text-sm text-notion-text truncate">{{ $student->name }}</p>
            <p class="text-xs text-notion-gray font-mono">{{ $student->nis }} · Kelas {{ $student->kelas }}</p>
          </div>
          <i data-lucide="chevron-down" id="card-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
        </div>
        <div id="card-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
          <div class="flex justify-between">
            <span class="text-notion-gray">NIS / NISN</span>
            <span class="font-mono">{{ $student->nis }} / {{ $student->nisn }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-notion-gray">Kelas / Ruang</span>
            <span class="font-medium">{{ $student->kelas }} · {{ $student->ruang ?: '-' }}{{ $student->seat_number ? ' (Meja ' . str_pad((string) $student->seat_number, 2, '0', STR_PAD_LEFT) . ')' : '' }}</span>
          </div>
          <div class="pt-2 border-t border-notion-border flex justify-end">
            <a href="{{ route('exam-cards.print', ['student_id' => $student->id]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-notion-blue text-white rounded-md text-sm font-medium">
              <i data-lucide="printer" class="w-3.5 h-3.5"></i> Cetak Kartu
            </a>
          </div>
        </div>
      </div>
      @empty
      <div class="p-6 text-center text-sm text-notion-gray">Tidak ada data siswa yang sesuai.</div>
      @endforelse
    </div>
  </form>

  <x-simple-pagination :paginator="$students" />
</div>

@if(auth()->user()->isSuperAdmin())
@push('modals')
<div id="modal-generate-rooms" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-generate-rooms')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-lg w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 class="font-bold text-lg">Generate Ruang &amp; Meja Peserta</h3>
      <button type="button" onclick="closeAppModal('modal-generate-rooms')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form method="POST" action="{{ route('exam-cards.generate-rooms') }}" class="p-6 overflow-y-auto flex-1 space-y-4">
      @csrf
      <div class="p-3 rounded-lg bg-notion-sidebar/70 border border-notion-border flex items-center justify-between text-sm">
        <span class="text-notion-gray">Total Siswa Aktif Terdata</span>
        <span class="font-bold tnum text-notion-text">{{ number_format($totalActiveStudents) }} siswa</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1">Banyak Ruang</label>
          <input type="number" name="room_count" value="{{ $seatingConfig['room_count'] }}" min="1" max="200" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Maks / Ruang</label>
          <input type="number" name="max_per_room" id="gen-max-per-room" value="{{ $seatingConfig['max_per_room'] }}" min="1" max="200" required onchange="document.getElementById('gen-seat-count').value = this.value" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Banyak Kelas</label>
          <input type="number" name="class_count" value="{{ $totalClassCount ?: 1 }}" min="1" max="200" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Model Pembagian Ruang</label>
        <select name="distribution_mode" class="modern-select w-full">
          <option value="half_split">Setengah Kapasitas per Putaran (Contoh: 40/ruang → 20 + 20)</option>
          <option value="sequential">Sekuensial Penuh per Ruang</option>
        </select>
        <p class="mt-1 text-xs text-notion-gray">Mode setengah kapasitas mengisi tiap ruang separuh dulu (misal 20 siswa), lalu putaran kedua mengisi sisa kursinya dengan kelas berikutnya.</p>
      </div>

      <div class="pt-3 border-t border-notion-border space-y-3">
        <p class="text-sm font-semibold text-notion-text">Pengaturan Denah Duduk</p>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-medium text-notion-gray mb-1">Jumlah Kursi</label>
            <input type="number" name="seat_count" id="gen-seat-count" value="{{ $seatingConfig['seat_count'] }}" min="1" max="200" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
          </div>
          <div>
            <label class="block text-xs font-medium text-notion-gray mb-1">Jumlah Baris</label>
            <input type="number" name="rows" value="{{ $seatingConfig['rows'] }}" min="1" max="30" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
          </div>
          <div>
            <label class="block text-xs font-medium text-notion-gray mb-1">Jumlah Kolom</label>
            <input type="number" name="cols" value="{{ $seatingConfig['cols'] }}" min="1" max="20" required class="w-full border border-notion-border rounded-md px-3 py-2 text-sm tnum">
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-notion-gray mb-1">Model Urutan Meja</label>
          <select name="seating_pattern" class="modern-select w-full">
            <option value="left_to_right" {{ $seatingConfig['seating_pattern'] === 'left_to_right' ? 'selected' : '' }}>Kiri ke Kanan</option>
            <option value="right_to_left" {{ $seatingConfig['seating_pattern'] === 'right_to_left' ? 'selected' : '' }}>Kanan ke Kiri</option>
            <option value="front_to_back" {{ $seatingConfig['seating_pattern'] === 'front_to_back' ? 'selected' : '' }}>Depan ke Belakang</option>
            <option value="zigzag" {{ $seatingConfig['seating_pattern'] === 'zigzag' ? 'selected' : '' }}>Zigzag (Ular)</option>
          </select>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-generate-rooms')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Generate Sekarang</button>
      </div>
    </form>
  </div>
</div>
@endpush
@endif

<script>
  function updateSelectedPrintCount() {
    const checked = document.querySelectorAll('.card-student-check:checked').length;
    const btn = document.getElementById('btn-print-selected');
    const countSpan = document.getElementById('selected-count');
    countSpan.textContent = checked;
    if (checked > 0) {
      btn.classList.remove('hidden');
    } else {
      btn.classList.add('hidden');
    }
  }

  function toggleAllCardChecks(master) {
    document.querySelectorAll('.card-student-check').forEach((cb) => {
      cb.checked = master.checked;
    });
    updateSelectedPrintCount();
  }

  function submitSelectedPrint() {
    const checked = document.querySelectorAll('.card-student-check:checked').length;
    if (checked > 0) {
      document.getElementById('bulk-print-form').submit();
    }
  }
</script>
@endsection

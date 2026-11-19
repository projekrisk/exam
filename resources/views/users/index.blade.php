@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')

@section('content')
<div class="space-y-3">
  <i data-lucide="user-cog" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Pengguna</h1>
  <p class="text-notion-gray text-sm">Pengelolaan akun pengguna sistem ujian.</p>
</div>

<div class="border border-notion-border rounded-lg bg-white overflow-hidden shadow-sm">
  <div class="p-4 border-b border-notion-border flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 bg-notion-sidebar/40">
    <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 w-full">
      <div class="relative flex-1 min-w-[200px]">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau username..."
               class="w-full border border-notion-border rounded-md pl-9 pr-3 py-2 text-sm bg-white">
      </div>
      <div class="sm:w-44 shrink-0">
        <select name="role" onchange="this.form.submit()" class="modern-select w-full">
          <option value="">Semua Role</option>
          <option value="super_admin" {{ $role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
          <option value="panitia" {{ $role === 'panitia' ? 'selected' : '' }}>Panitia</option>
          <option value="guru" {{ $role === 'guru' ? 'selected' : '' }}>Guru</option>
          <option value="proktor" {{ $role === 'proktor' ? 'selected' : '' }}>Proktor</option>
        </select>
      </div>
      <button type="submit" class="px-3.5 py-2 bg-white border border-notion-border rounded-md text-sm font-medium hover:bg-notion-hover transition-colors whitespace-nowrap shrink-0">
        Filter
      </button>
      @if($search !== '' || $role !== '')
        <a href="{{ route('users.index') }}" class="px-3 py-2 text-sm text-notion-gray hover:text-notion-text text-center shrink-0">Reset</a>
      @endif
    </form>

    <button type="button" onclick="openCreateUserModal()" class="w-full lg:w-auto px-3.5 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors flex items-center justify-center gap-1.5 shadow-sm shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i> Tambah Pengguna
    </button>
  </div>

  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left text-sm whitespace-nowrap">
      <thead class="bg-notion-sidebar text-notion-gray border-b border-notion-border">
        <tr>
          <th class="py-3 px-4 font-medium">Nama Pengguna</th>
          <th class="py-3 px-4 font-medium">Username</th>
          <th class="py-3 px-4 font-medium">Role</th>
          <th class="py-3 px-4 font-medium">Status</th>
          <th class="py-3 px-4 font-medium text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-notion-border">
        @forelse($users as $u)
        @php
          $roleLabel = match($u->role) {
            'super_admin' => 'Super Admin',
            'panitia', 'admin' => 'Panitia',
            'guru' => 'Guru',
            'proktor' => 'Proktor',
            default => ucfirst($u->role),
          };
          $roleBadge = match($u->role) {
            'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
            'panitia', 'admin' => 'bg-amber-50 text-amber-700 border-amber-200',
            'guru' => 'bg-blue-50 text-notion-blue border-blue-200',
            default => 'bg-notion-hover text-notion-gray border-notion-border',
          };
          $userPayload = [
            'name' => $u->name,
            'username' => $u->username,
            'role' => $u->role === 'admin' ? 'panitia' : $u->role,
            'is_active' => (bool) $u->is_active,
            'update_url' => route('users.update', $u),
          ];
        @endphp
        <tr class="hover:bg-notion-hover/50 transition-colors">
          <td class="py-3 px-4 font-semibold text-notion-text">
            {{ $u->name }}
            @if($u->id === auth()->id())
              <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded bg-notion-hover text-notion-gray font-normal">Anda</span>
            @endif
          </td>
          <td class="py-3 px-4 font-mono text-sm text-notion-gray">{{ $u->username }}</td>
          <td class="py-3 px-4">
            <span class="px-2.5 py-0.5 rounded text-xs font-medium border {{ $roleBadge }}">{{ $roleLabel }}</span>
          </td>
          <td class="py-3 px-4">
            @if($u->is_active)
              <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
            @else
              <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-notion-hover text-notion-gray border border-notion-border">Nonaktif</span>
            @endif
          </td>
          <td class="py-3 px-4 text-right space-x-1">
            <button type="button" onclick='openEditUserModal(@json($userPayload))' class="px-2.5 py-1 bg-white border border-notion-border rounded text-sm hover:bg-notion-hover transition-colors inline-flex items-center gap-1" title="Edit">
              <i data-lucide="edit-2" class="w-3.5 h-3.5 text-notion-gray"></i> Edit
            </button>
            <form method="POST" action="{{ route('users.password', $u) }}" onsubmit="return handleConfirmSubmit(event, 'Password {{ $u->name }} akan direset ke {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}.', 'Konfirmasi Reset Password', 'Ya, Reset', 'danger')" class="inline">
              @csrf
              <button type="submit" class="px-2.5 py-1 bg-white border border-notion-border rounded text-sm hover:bg-notion-hover transition-colors inline-flex items-center gap-1" title="Reset Password ke {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-notion-blue"></i> Reset
              </button>
            </form>
            @if($u->id !== auth()->id())
            <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return handleConfirmSubmit(event, 'Hapus akun pengguna {{ $u->name }} ({{ $u->username }}) secara permanen?')" class="inline">
              @csrf @method('DELETE')
              <button type="submit" class="p-1.5 text-notion-gray hover:text-notion-red hover:bg-red-50 rounded transition-colors" title="Hapus">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
              </button>
            </form>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="py-10 text-center text-notion-gray text-sm">Tidak ada data pengguna yang sesuai.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="md:hidden divide-y divide-notion-border">
    @forelse($users as $idx => $u)
    @php
      $roleLabelMob = match($u->role) {
        'super_admin' => 'Super Admin',
        'panitia', 'admin' => 'Panitia',
        'guru' => 'Guru',
        'proktor' => 'Proktor',
        default => ucfirst($u->role),
      };
      $userPayloadMob = [
        'name' => $u->name,
        'username' => $u->username,
        'role' => $u->role === 'admin' ? 'panitia' : $u->role,
        'is_active' => (bool) $u->is_active,
        'update_url' => route('users.update', $u),
      ];
    @endphp
    <div class="bg-white flex flex-col">
      <div onclick="toggleMobileRow('user', {{ $idx }})" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-notion-hover/50">
        <div class="min-w-0">
          <p class="font-semibold text-sm text-notion-text truncate">{{ $u->name }}</p>
          <p class="text-xs text-notion-gray font-mono truncate">{{ $u->username }} · {{ $roleLabelMob }}</p>
        </div>
        <i data-lucide="chevron-down" id="user-icon-{{ $idx }}" class="w-4 h-4 text-notion-gray transition-transform duration-200 shrink-0"></i>
      </div>
      <div id="user-detail-{{ $idx }}" class="hidden px-4 pb-4 space-y-2.5 bg-notion-sidebar/30 border-t border-notion-border pt-3 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-notion-gray">Status</span>
          <span class="font-medium">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </div>
        <div class="pt-2 border-t border-notion-border flex flex-wrap justify-end gap-2">
          <button type="button" onclick='openEditUserModal(@json($userPayloadMob))' class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium">Edit</button>
          <form method="POST" action="{{ route('users.password', $u) }}" onsubmit="return handleConfirmSubmit(event, 'Password {{ $u->name }} akan direset ke {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}.', 'Konfirmasi Reset Password', 'Ya, Reset', 'danger')">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-white border border-notion-border rounded text-sm font-medium text-notion-blue">Reset</button>
          </form>
          @if($u->id !== auth()->id())
          <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return handleConfirmSubmit(event, 'Hapus akun {{ $u->name }} secara permanen?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-notion-redBg text-notion-red rounded text-sm">Hapus</button>
          </form>
          @endif
        </div>
      </div>
    </div>
    @empty
    <div class="p-6 text-center text-sm text-notion-gray">Tidak ada data pengguna.</div>
    @endforelse
  </div>

  <x-simple-pagination :paginator="$users" />
</div>

@push('modals')

<div id="modal-user" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-user')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 id="modal-user-title" class="font-bold text-lg">Tambah Pengguna</h3>
      <button type="button" onclick="closeAppModal('modal-user')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="user-form" method="POST" action="{{ route('users.store') }}" class="p-6 overflow-y-auto flex-1 space-y-3.5">
      @csrf
      <input type="hidden" name="_method" id="user-method" value="POST">
      <div>
        <label class="block text-sm font-medium mb-1">Nama Pengguna</label>
        <input type="text" name="name" id="user-name" required placeholder="Contoh: Panitia Ujian SMAN 1" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Username / NIP</label>
        <input type="text" name="username" id="user-username" required placeholder="Contoh: panitia" class="w-full border border-notion-border rounded-md px-3 py-2 text-sm font-mono">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select name="role" id="user-role" class="modern-select w-full">
          <option value="panitia">Panitia</option>
          <option value="super_admin">Super Admin</option>
          <option value="guru">Guru</option>
          <option value="proktor">Proktor</option>
        </select>
      </div>
      <div id="user-password-create-wrap">
        <label class="block text-sm font-medium mb-1">Password Awal</label>
        <div class="relative">
          <input type="password" name="password" id="user-create-password" minlength="6" placeholder="Kosongkan untuk default: {{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}" class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm">
          <button type="button" onclick="toggleUserPwField('user-create-password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text">
            <i data-lucide="eye" class="w-4 h-4"></i>
          </button>
        </div>
        <p class="mt-1 text-xs text-notion-gray">Password default: <code class="font-mono">{{ \App\Models\User::DEFAULT_TEACHER_PASSWORD }}</code> jika dikosongkan.</p>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-notion-text cursor-pointer pt-1">
        <input type="checkbox" name="is_active" id="user-active" value="1" checked class="rounded border-notion-border">
        Akun aktif (dapat login)
      </label>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-user')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" id="user-submit-btn" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-user-password" class="hidden fixed inset-0 w-screen h-screen z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('modal-user-password')">
  <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-t-2xl sm:rounded-xl max-w-md w-full max-h-[90vh] flex flex-col shadow-xl transform translate-y-6 sm:translate-y-0 sm:scale-95 transition-all duration-200 overflow-hidden">
    <div class="sm:hidden pt-2.5 pb-1 flex justify-center shrink-0 bg-white">
      <div class="w-10 h-1.5 bg-notion-border rounded-full"></div>
    </div>
    <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-notion-border flex items-center justify-between shrink-0">
      <h3 class="font-bold text-lg">Ganti Password: <span id="pw-user-name" class="text-notion-blue"></span></h3>
      <button type="button" onclick="closeAppModal('modal-user-password')" class="p-1 text-notion-gray hover:text-notion-text rounded-md hover:bg-notion-hover"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <form id="user-password-form" method="POST" action="" class="p-6 overflow-y-auto flex-1 space-y-3.5">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Password Baru</label>
        <div class="relative">
          <input type="password" name="password" id="pw-new" required minlength="6" placeholder="Minimal 6 karakter" class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm">
          <button type="button" onclick="toggleUserPwField('pw-new', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text">
            <i data-lucide="eye" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
        <div class="relative">
          <input type="password" name="password_confirmation" id="pw-confirm" required minlength="6" placeholder="Ulangi password baru" class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm">
          <button type="button" onclick="toggleUserPwField('pw-confirm', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text">
            <i data-lucide="eye" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAppModal('modal-user-password')" class="px-4 py-2 text-sm text-notion-gray hover:bg-notion-hover rounded-md">Batal</button>
        <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Perbarui Password</button>
      </div>
    </form>
  </div>
</div>
@endpush

<script>
  function toggleUserPwField(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.innerHTML = `<i data-lucide="${isHidden ? 'eye-off' : 'eye'}" class="w-4 h-4"></i>`;
    lucide.createIcons();
  }

  function openCreateUserModal() {
    document.getElementById('modal-user-title').textContent = 'Tambah Pengguna';
    document.getElementById('user-form').action = "{{ route('users.store') }}";
    document.getElementById('user-method').value = 'POST';
    document.getElementById('user-name').value = '';
    document.getElementById('user-username').value = '';
    const roleSelect = document.getElementById('user-role');
    roleSelect.value = 'panitia';
    roleSelect._syncModernSelect?.();
    document.getElementById('user-active').checked = true;
    const pwWrap = document.getElementById('user-password-create-wrap');
    const pwInput = document.getElementById('user-create-password');
    pwWrap.classList.remove('hidden');
    pwInput.required = false;
    pwInput.value = '';
    openAppModal('modal-user');
  }

  function openEditUserModal(data) {
    document.getElementById('modal-user-title').textContent = 'Edit Pengguna';
    document.getElementById('user-form').action = data.update_url;
    document.getElementById('user-method').value = 'PUT';
    document.getElementById('user-name').value = data.name || '';
    document.getElementById('user-username').value = data.username || '';
    const roleSelect = document.getElementById('user-role');
    roleSelect.value = data.role || 'panitia';
    roleSelect._syncModernSelect?.();
    document.getElementById('user-active').checked = !!data.is_active;
    const pwWrap = document.getElementById('user-password-create-wrap');
    const pwInput = document.getElementById('user-create-password');
    pwWrap.classList.add('hidden');
    pwInput.required = false;
    pwInput.value = '';
    openAppModal('modal-user');
  }

  function openPasswordUserModal(data) {
    document.getElementById('pw-user-name').textContent = data.name || '';
    document.getElementById('user-password-form').action = data.url;
    document.getElementById('pw-new').value = '';
    document.getElementById('pw-confirm').value = '';
    openAppModal('modal-user-password');
  }
</script>
@endsection

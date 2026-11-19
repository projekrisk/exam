@php
  $appSchoolName = \App\Models\AppSetting::getValue('school_name', 'SMAN 1 Malingping');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dasbor') | {{ $appSchoolName }}</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Bricolage Grotesque"', 'sans-serif'],
            mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
          },
          colors: {
            notion: {
              bg: '#ffffff',
              sidebar: '#f7f7f5',
              hover: '#efefed',
              text: '#222222',
              gray: '#666666',
              border: '#e5e5e5',
              blue: '#2563eb',
              blueHover: '#1d4ed8',
              red: '#e03e3e',
              redBg: '#fbdbdc',
              green: '#0f7b6c',
              greenBg: '#ebeced'
            }
          }
        }
      }
    }
  </script>

  <style>
    html { font-size: 16px; }
    body { background-color: #ffffff; color: #222222; font-size: 14px; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d3d1cb; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
    ::-webkit-scrollbar-thumb:hover { background: #aeaca6; border: 2px solid transparent; background-clip: padding-box; }
    .tnum { font-variant-numeric: tabular-nums; }
    .sidebar-transition { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.28s cubic-bezier(0.4, 0, 0.2, 1); }
    .sidebar-label { transition: opacity 0.2s ease, width 0.2s ease; white-space: nowrap; }
    @media (min-width: 768px) {
      #sidebar.sidebar-collapsed { width: 68px !important; }
      #sidebar.sidebar-collapsed .sidebar-label,
      #sidebar.sidebar-collapsed .sidebar-heading {
        display: none;
      }
      #sidebar.sidebar-collapsed .sidebar-brand {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
        margin-left: 0.5rem;
        margin-right: 0.5rem;
      }
      #sidebar.sidebar-collapsed .sidebar-brand-icon {
        margin-right: 0;
      }
      #sidebar.sidebar-collapsed .sidebar-link {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
        gap: 0;
      }
    }
    @keyframes smoothReveal {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-reveal { animation: smoothReveal 0.22s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes toastSlideIn {
      from { opacity: 0; transform: translateY(14px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-toast { animation: toastSlideIn 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
    input:not([type="checkbox"]):not([type="radio"]):focus,
    textarea:focus,
    select:focus {
      outline: none !important;
      border-color: #2563eb !important;
      box-shadow: 0 0 0 1px #2563eb !important;
    }
  </style>
</head>
<body class="antialiased flex h-screen overflow-hidden selection:bg-notion-blue/20">

  <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/20 z-40 hidden md:hidden opacity-0 transition-opacity duration-300"></div>

  <aside id="sidebar" class="sidebar-transition fixed md:static inset-y-0 left-0 w-[285px] bg-notion-sidebar border-r border-notion-border flex flex-col z-50 transform -translate-x-full md:translate-x-0 h-full shrink-0 overflow-hidden">

    <div class="sidebar-brand h-12 flex items-center px-4 hover:bg-notion-hover transition-colors mt-2 mx-2 rounded-md">
      <img src="{{ asset('favicon.png') }}" alt="Logo" class="sidebar-brand-icon w-7 h-7 rounded object-contain mr-2.5 shrink-0">
      <div class="sidebar-label flex-1 min-w-0">
        <h1 class="font-semibold text-sm text-notion-text truncate">{{ $appSchoolName }}</h1>
      </div>
    </div>

    <div class="mt-5 px-2 flex-1 overflow-y-auto space-y-5">
      <div>
        <p class="sidebar-heading px-3 text-sm font-semibold text-notion-gray mb-1.5">Menu Utama</p>
        <nav class="space-y-0.5">
          <a href="{{ route('dashboard') }}" title="Dasbor" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('dashboard') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="bar-chart-2" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Dasbor</span>
          </a>

          @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
          <a href="{{ route('question-banks.index') }}" title="Bank Soal" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('question-banks.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Bank Soal</span>
          </a>
          @endif

          <a href="{{ route('exams.index') }}" title="Jadwal Ujian" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('exams.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="calendar-clock" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Jadwal Ujian</span>
          </a>

          @if(auth()->user()->isAdmin() || auth()->user()->isGuru())
          <a href="{{ route('score-archives.index') }}" title="Daftar Nilai" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('score-archives.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="award" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Daftar Nilai</span>
          </a>
          @endif
        </nav>
      </div>

      @if(auth()->user()->isAdmin())
      <div>
        <p class="sidebar-heading px-3 text-sm font-semibold text-notion-gray mb-1.5">Master Data (Panitia)</p>
        <nav class="space-y-0.5">
          <a href="{{ route('students.index') }}" title="Data Siswa" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('students.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Data Siswa</span>
          </a>
          <a href="{{ route('teachers.index') }}" title="Data Guru" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('teachers.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Data Guru</span>
          </a>
          <a href="{{ route('subjects.index') }}" title="Mata Pelajaran" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('subjects.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Mata Pelajaran</span>
          </a>
          <a href="{{ route('exam-cards.index') }}" title="Kartu Ujian" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('exam-cards.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="id-card" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Kartu Ujian</span>
          </a>
          @if(auth()->user()->isSuperAdmin())
          <a href="{{ route('users.index') }}" title="Pengguna" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('users.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="user-cog" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Pengguna</span>
          </a>
          <a href="{{ route('settings.index') }}" title="Pengaturan" class="sidebar-link flex items-center gap-2.5 px-3 py-1.5 text-sm rounded-md transition-colors {{ request()->routeIs('settings.*') ? 'bg-notion-hover text-notion-text font-semibold' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text font-medium' }}">
            <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
            <span class="sidebar-label truncate">Pengaturan</span>
          </a>
          @endif
        </nav>
      </div>
      @endif
    </div>

    <div class="p-3 border-t border-notion-border space-y-1">
      <a href="{{ route('password.change') }}" title="Ganti Password" class="sidebar-link w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-notion-gray hover:bg-notion-hover hover:text-notion-text rounded-md transition-colors">
        <i data-lucide="key-round" class="w-4 h-4 shrink-0"></i>
        <span class="sidebar-label truncate">Ganti Password</span>
      </a>
    </div>
  </aside>

  <main class="flex-1 flex flex-col h-full min-w-0 relative bg-notion-bg">

    <header class="h-12 flex items-center px-4 justify-between shrink-0 border-b border-notion-border/60 sticky top-0 bg-white/85 backdrop-blur-sm z-30">
      <div class="flex items-center gap-2 overflow-hidden">
        <button onclick="toggleSidebar()" class="md:hidden p-1 text-notion-gray hover:bg-notion-hover rounded transition-colors mr-1" title="Menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <button onclick="toggleDesktopSidebar()" id="desktop-sidebar-toggle" class="hidden md:inline-flex p-1.5 text-notion-gray hover:text-notion-text hover:bg-notion-hover rounded transition-colors mr-1" title="Tutup / Buka Sidebar">
          <i data-lucide="panel-left" class="w-4 h-4"></i>
        </button>
        <div class="flex items-center text-sm text-notion-gray whitespace-nowrap">
          <span>Exam CBT</span>
          <span class="mx-1.5">/</span>
          <span class="text-notion-text font-medium truncate">@yield('breadcrumb', 'Dasbor')</span>
        </div>
      </div>

      <div class="relative group cursor-pointer z-50">
        <div class="flex items-center gap-2 hover:bg-notion-hover p-1 sm:px-2 sm:py-1.5 rounded-md transition-colors">
          <div class="w-7 h-7 rounded-full border border-notion-border bg-notion-sidebar flex items-center justify-center text-sm font-bold text-notion-text">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
          </div>
          <span class="text-sm font-medium text-notion-text hidden sm:block">{{ auth()->user()->name }}</span>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-notion-gray hidden sm:block"></i>
        </div>

        <div class="absolute right-0 top-full mt-1 w-56 bg-white border border-notion-border rounded-lg shadow-sm opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-1.5">
          <div class="px-3 py-2 border-b border-notion-border mb-1">
            <p class="text-sm font-semibold text-notion-text truncate">{{ auth()->user()->name }}</p>
            <p class="text-sm text-notion-gray font-mono truncate">{{ auth()->user()->username }}</p>
          </div>
          <a href="{{ route('password.change') }}" class="w-full text-left px-3 py-1.5 text-sm text-notion-text hover:bg-notion-hover transition-colors flex items-center gap-2 font-medium">
            <i data-lucide="key-round" class="w-4 h-4 text-notion-gray"></i> Ganti Password
          </a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-1.5 text-sm text-notion-red hover:bg-notion-redBg transition-colors flex items-center gap-2 font-medium">
              <i data-lucide="log-out" class="w-4 h-4 text-notion-red"></i> Keluar
            </button>
          </form>
        </div>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto w-full pb-20">
      <div class="max-w-5xl mx-auto px-5 sm:px-10 md:px-14 py-8 md:py-12 space-y-8">

        @if(auth()->user()->must_change_password)
        <div class="flex items-center justify-between gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm">
          <div class="flex items-center gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0"></i>
            <div>
              <h4 class="font-semibold text-amber-900">Keamanan Akun Guru</h4>
              <p class="text-amber-800">Anda masih menggunakan password default. Disarankan segera mengganti password Anda.</p>
            </div>
          </div>
          <a href="{{ route('password.change') }}" class="px-3 py-1.5 bg-white border border-amber-300 text-amber-900 text-sm font-semibold rounded-md hover:bg-amber-100 shrink-0">Ganti Sekarang</a>
        </div>
        @endif

        @yield('content')
      </div>
    </div>
  </main>

  @stack('modals')
  <x-confirm-modal />

  <div id="app-notice-modal" class="hidden fixed inset-0 w-screen h-screen z-[115] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeAppModal('app-notice-modal')">
    <div onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-xl w-full max-w-md p-6 shadow-xl space-y-4 transform scale-95 transition-all duration-200">
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
            <i data-lucide="shield-alert" class="w-5 h-5"></i>
          </div>
          <h3 id="notice-modal-title" class="font-bold text-base text-notion-text">Aksi Tidak Diizinkan</h3>
        </div>
        <p id="notice-modal-message" class="text-sm text-notion-gray leading-relaxed"></p>
      </div>
      <div class="flex justify-end pt-2 border-t border-notion-border">
        <button type="button" onclick="closeAppModal('app-notice-modal')" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors">
          Mengerti
        </button>
      </div>
    </div>
  </div>

  <div id="toast-container" class="fixed bottom-5 right-5 z-[120] flex flex-col gap-2.5 max-w-sm w-[calc(100%-2.5rem)] pointer-events-none"></div>

  <script>
    lucide.createIcons();

    function showToast(type, message) {
      const container = document.getElementById('toast-container');
      if (!container) return;

      const styles = {
        success: 'bg-white border-emerald-200 text-emerald-800',
        error: 'bg-white border-red-200 text-notion-red',
        info: 'bg-white border-blue-200 text-notion-blue'
      };
      const icons = {
        success: 'check-circle-2',
        error: 'alert-circle',
        info: 'info'
      };

      const toast = document.createElement('div');
      toast.className = `pointer-events-auto animate-toast flex items-start gap-2.5 p-3.5 rounded-xl border shadow-lg text-sm transition-all duration-300 ${styles[type] || styles.info}`;
      toast.innerHTML = `
        <i data-lucide="${icons[type] || 'info'}" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <span class="flex-1 leading-snug font-medium text-notion-text">${message}</span>
        <button type="button" class="text-notion-gray hover:text-notion-text p-0.5 rounded" aria-label="Tutup">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      `;

      const close = () => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 250);
      };

      toast.querySelector('button').addEventListener('click', close);
      container.appendChild(toast);
      lucide.createIcons();
      setTimeout(close, 4500);
    }

    function openAppModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      const box = modal.firstElementChild;
      modal.classList.remove('hidden');
      requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        if (box) box.classList.remove('translate-y-6', 'translate-y-full', 'sm:scale-95');
      });
    }

    function closeAppModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      const box = modal.firstElementChild;
      modal.classList.add('opacity-0');
      if (box) box.classList.add('translate-y-6', 'sm:scale-95');
      setTimeout(() => modal.classList.add('hidden'), 220);
    }

    let pendingConfirmForm = null;
    function handleConfirmSubmit(event, message, title = 'Konfirmasi Hapus', btnLabel = 'Ya, Hapus', variant = 'danger') {
      event.preventDefault();
      pendingConfirmForm = event.target.closest('form');
      document.getElementById('confirm-title').textContent = title;
      document.getElementById('confirm-message').textContent = message;
      const btn = document.getElementById('confirm-submit-btn');
      const iconWrap = document.getElementById('confirm-icon-wrap');
      btn.textContent = btnLabel;

      if (variant === 'danger') {
        btn.className = 'px-4 py-2 bg-notion-red text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors';
        iconWrap.className = 'w-10 h-10 rounded-lg bg-notion-redBg text-notion-red flex items-center justify-center shrink-0';
      } else {
        btn.className = 'px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors';
        iconWrap.className = 'w-10 h-10 rounded-lg bg-blue-50 text-notion-blue flex items-center justify-center shrink-0';
      }

      openAppModal('app-confirm-modal');
      return false;
    }

    function closeConfirmModal() {
      pendingConfirmForm = null;
      closeAppModal('app-confirm-modal');
    }

    function showNoticeModal(title, message) {
      document.getElementById('notice-modal-title').textContent = title || 'Aksi Ditolak';
      document.getElementById('notice-modal-message').textContent = message || '';
      lucide.createIcons();
      openAppModal('app-notice-modal');
    }

    document.getElementById('confirm-submit-btn')?.addEventListener('click', () => {
      if (pendingConfirmForm) {
        const form = pendingConfirmForm;
        pendingConfirmForm = null;
        closeAppModal('app-confirm-modal');
        form.submit();
      }
    });

    let currentMobRowOpen = null;
    function toggleMobileRow(prefix, index) {
      const rowId = `${prefix}-row-${index}`;
      const detailRow = document.getElementById(`${prefix}-detail-${index}`);
      const icon = document.getElementById(`${prefix}-icon-${index}`);

      if (currentMobRowOpen === rowId) {
        if (detailRow) {
          detailRow.classList.add('hidden');
          detailRow.classList.remove('animate-reveal');
        }
        if (icon) icon.classList.remove('rotate-180');
        currentMobRowOpen = null;
        return;
      }

      if (currentMobRowOpen) {
        const parts = currentMobRowOpen.split('-row-');
        if (parts.length === 2) {
          const prevDetail = document.getElementById(`${parts[0]}-detail-${parts[1]}`);
          const prevIcon = document.getElementById(`${parts[0]}-icon-${parts[1]}`);
          if (prevDetail) {
            prevDetail.classList.add('hidden');
            prevDetail.classList.remove('animate-reveal');
          }
          if (prevIcon) prevIcon.classList.remove('rotate-180');
        }
      }

      if (detailRow) {
        detailRow.classList.remove('hidden');
        detailRow.classList.add('animate-reveal');
      }
      if (icon) icon.classList.add('rotate-180');
      currentMobRowOpen = rowId;
    }

    function toggleDesktopSidebar() {
      const sidebar = document.getElementById('sidebar');
      const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
      localStorage.setItem('exam_sidebar_collapsed', isCollapsed ? '1' : '0');
    }

    document.addEventListener('DOMContentLoaded', () => {
      if (localStorage.getItem('exam_sidebar_collapsed') === '1') {
        const sidebar = document.getElementById('sidebar');
        sidebar?.classList.add('sidebar-collapsed');
      }
    });

    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('mobile-overlay');
      const isClosed = sidebar.classList.contains('-translate-x-full');
      if (isClosed) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
      } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
      }
    }

    function initModernSelects() {
      document.querySelectorAll('select.modern-select').forEach((selectEl) => {
        if (selectEl.dataset.upgraded) return;
        selectEl.dataset.upgraded = '1';
        selectEl.classList.add('hidden');

        const wrapper = document.createElement('div');
        wrapper.className = 'relative w-full';
        selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'w-full flex items-center justify-between border border-notion-border rounded-md px-3 py-2 text-sm bg-white hover:bg-notion-sidebar transition-colors text-left';

        const labelSpan = document.createElement('span');
        labelSpan.className = 'truncate text-notion-text';
        const icon = document.createElement('i');
        icon.setAttribute('data-lucide', 'chevron-down');
        icon.className = 'w-4 h-4 text-notion-gray shrink-0 ml-2 transition-transform duration-150';
        trigger.appendChild(labelSpan);
        trigger.appendChild(icon);

        const menu = document.createElement('div');
        menu.className = 'hidden absolute z-50 top-full left-0 w-full min-w-[200px] mt-1 bg-white border border-notion-border rounded-lg shadow-lg p-1.5';

        const options = Array.from(selectEl.options);
        const hasSearch = options.length > 3;

        let searchInput = null;
        if (hasSearch) {
          const searchWrap = document.createElement('div');
          searchWrap.className = 'relative mb-1.5';
          searchWrap.innerHTML = '<i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-notion-gray pointer-events-none"></i>';
          searchInput = document.createElement('input');
          searchInput.type = 'text';
          searchInput.placeholder = 'Cari...';
          searchInput.className = 'w-full bg-notion-sidebar border border-notion-border rounded-md pl-8 pr-2.5 py-1.5 text-sm focus:outline-none focus:bg-white focus:border-notion-blue';
          searchWrap.appendChild(searchInput);
          menu.appendChild(searchWrap);
        }

        const listBox = document.createElement('div');
        listBox.className = 'max-h-52 overflow-y-auto space-y-0.5';
        menu.appendChild(listBox);

        function renderOptions(filter = '') {
          listBox.innerHTML = '';
          const q = filter.trim().toLowerCase();
          const currentVal = selectEl.value;
          let visibleCount = 0;

          options.forEach((opt) => {
            if (q && !opt.text.toLowerCase().includes(q)) return;
            visibleCount++;
            const btn = document.createElement('button');
            btn.type = 'button';
            const isSelected = opt.value === currentVal;
            btn.className = `w-full text-left px-2.5 py-1.5 text-sm rounded-md flex items-center justify-between transition-colors ${isSelected ? 'bg-notion-hover text-notion-text font-medium' : 'text-notion-gray hover:bg-notion-hover hover:text-notion-text'}`;
            btn.innerHTML = `<span class="truncate">${opt.text}</span>` + (isSelected ? '<i data-lucide="check" class="w-4 h-4 text-notion-text shrink-0 ml-2"></i>' : '');
            btn.addEventListener('click', (e) => {
              e.stopPropagation();
              selectEl.value = opt.value;
              labelSpan.textContent = opt.text;
              menu.classList.add('hidden');
              icon.classList.remove('rotate-180');
              selectEl.dispatchEvent(new Event('change', { bubbles: true }));
              renderOptions(searchInput ? searchInput.value : '');
            });
            listBox.appendChild(btn);
          });

          if (visibleCount === 0) {
            listBox.innerHTML = '<p class="px-2.5 py-2 text-sm text-notion-gray text-center">Tidak ditemukan</p>';
          }
          lucide.createIcons();
        }

        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        labelSpan.textContent = selectedOpt ? selectedOpt.text : 'Pilih...';
        renderOptions();
        selectEl._syncModernSelect = () => {
          const opt = selectEl.options[selectEl.selectedIndex];
          labelSpan.textContent = opt ? opt.text : 'Pilih...';
          renderOptions(searchInput ? searchInput.value : '');
        };

        if (searchInput) {
          searchInput.addEventListener('input', () => renderOptions(searchInput.value));
          searchInput.addEventListener('click', (e) => e.stopPropagation());
        }

        trigger.addEventListener('click', (e) => {
          e.stopPropagation();
          document.querySelectorAll('.modern-select-menu-open').forEach((m) => {
            if (m !== menu) {
              m.classList.add('hidden');
              m.classList.remove('modern-select-menu-open');
            }
          });
          const opening = menu.classList.contains('hidden');
          menu.classList.toggle('hidden');
          menu.classList.toggle('modern-select-menu-open', opening);
          icon.classList.toggle('rotate-180', opening);
          if (opening && searchInput) {
            searchInput.value = '';
            renderOptions('');
            setTimeout(() => searchInput.focus(), 20);
          }
        });

        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);
      });
    }

    function initModernDatePickers() {
      const MONTHS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      const SHORT_MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
      const DAYS = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

      document.querySelectorAll('input.modern-datepicker').forEach((inputEl) => {
        if (inputEl.dataset.upgraded) return;
        inputEl.dataset.upgraded = '1';
        inputEl.type = 'hidden';

        const parseVal = (v) => {
          const d = v ? new Date(v.replace(' ', 'T')) : new Date();
          return isNaN(d.getTime()) ? new Date() : d;
        };

        let selectedDate = parseVal(inputEl.value);
        let viewYear = selectedDate.getFullYear();
        let viewMonth = selectedDate.getMonth();

        const wrapper = document.createElement('div');
        wrapper.className = 'relative w-full';
        inputEl.parentNode.insertBefore(wrapper, inputEl.nextSibling);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'w-full flex items-center justify-between border border-notion-border rounded-md px-3 py-2 text-sm bg-white hover:bg-notion-sidebar transition-colors text-left tnum';

        const formatDisplay = (d) => {
          const pad = (n) => String(n).padStart(2, '0');
          return `${pad(d.getDate())} ${SHORT_MONTHS[d.getMonth()]} ${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        };
        const formatIsoLocal = (d) => {
          const pad = (n) => String(n).padStart(2, '0');
          return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        };

        inputEl.value = formatIsoLocal(selectedDate);
        trigger.innerHTML = `<span class="truncate">${formatDisplay(selectedDate)}</span><i data-lucide="calendar" class="w-4 h-4 text-notion-gray shrink-0 ml-2"></i>`;

        const popup = document.createElement('div');
        popup.className = 'hidden absolute z-50 top-full left-0 mt-1.5 w-80 bg-white border border-notion-border rounded-xl shadow-lg p-4 space-y-3';
        popup.addEventListener('click', (e) => e.stopPropagation());

        function renderCalendar() {
          const firstDay = new Date(viewYear, viewMonth, 1).getDay();
          const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
          const pad = (n) => String(n).padStart(2, '0');

          let dayCells = '';
          for (let i = 0; i < firstDay; i++) {
            dayCells += `<div></div>`;
          }
          for (let d = 1; d <= daysInMonth; d++) {
            const isSel = selectedDate.getFullYear() === viewYear && selectedDate.getMonth() === viewMonth && selectedDate.getDate() === d;
            dayCells += `<button type="button" data-day="${d}" class="h-8 w-full rounded-md text-sm tnum flex items-center justify-center transition-colors ${isSel ? 'bg-notion-blue text-white font-semibold' : 'hover:bg-notion-hover text-notion-text'}">${d}</button>`;
          }

          popup.innerHTML = `
            <div class="flex items-center justify-between gap-2 pb-1 border-b border-notion-border">
              <button type="button" data-nav="-1" class="p-1.5 rounded-md border border-notion-border hover:bg-notion-hover"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
              <div class="flex items-center gap-1.5">
                <span class="text-sm font-semibold">${MONTHS[viewMonth]}</span>
                <input type="number" data-year-input min="2000" max="2100" value="${viewYear}" class="w-20 border border-notion-border rounded-md px-2 py-1 text-sm text-center font-semibold tnum focus:outline-none focus:border-notion-blue">
              </div>
              <button type="button" data-nav="1" class="p-1.5 rounded-md border border-notion-border hover:bg-notion-hover"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-sm text-notion-gray font-medium">
              ${DAYS.map(d => `<div>${d}</div>`).join('')}
            </div>
            <div class="grid grid-cols-7 gap-1">${dayCells}</div>

            <div class="pt-2 border-t border-notion-border flex items-center justify-between gap-2">
              <div class="flex items-center gap-1.5 text-sm">
                <span class="text-notion-gray">Jam:</span>
                <input type="number" data-hour min="0" max="23" value="${pad(selectedDate.getHours())}" class="w-14 border border-notion-border rounded-md px-2 py-1 text-sm text-center tnum">
                <span>:</span>
                <input type="number" data-minute min="0" max="59" value="${pad(selectedDate.getMinutes())}" class="w-14 border border-notion-border rounded-md px-2 py-1 text-sm text-center tnum">
              </div>
              <button type="button" data-done class="px-3 py-1.5 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover">Pilih</button>
            </div>
          `;

          lucide.createIcons();

          popup.querySelectorAll('[data-nav]').forEach(btn => {
            btn.addEventListener('click', () => {
              viewMonth += parseInt(btn.dataset.nav, 10);
              if (viewMonth < 0) { viewMonth = 11; viewYear--; }
              if (viewMonth > 11) { viewMonth = 0; viewYear++; }
              renderCalendar();
            });
          });

          popup.querySelectorAll('[data-month]').forEach(btn => {
            btn.addEventListener('click', () => {
              viewMonth = parseInt(btn.dataset.month, 10);
              renderCalendar();
            });
          });

          const yearInput = popup.querySelector('[data-year-input]');
          yearInput?.addEventListener('change', () => {
            const y = parseInt(yearInput.value, 10);
            if (y >= 2000 && y <= 2100) {
              viewYear = y;
              renderCalendar();
            }
          });

          popup.querySelectorAll('[data-day]').forEach(btn => {
            btn.addEventListener('click', () => {
              selectedDate.setFullYear(viewYear);
              selectedDate.setMonth(viewMonth);
              selectedDate.setDate(parseInt(btn.dataset.day, 10));
              inputEl.value = formatIsoLocal(selectedDate);
              trigger.querySelector('span').textContent = formatDisplay(selectedDate);
              inputEl.dispatchEvent(new Event('change', { bubbles: true }));
              renderCalendar();
            });
          });

          const hInput = popup.querySelector('[data-hour]');
          const mInput = popup.querySelector('[data-minute]');
          const syncTime = () => {
            const h = Math.min(23, Math.max(0, parseInt(hInput.value || '0', 10)));
            const m = Math.min(59, Math.max(0, parseInt(mInput.value || '0', 10)));
            selectedDate.setHours(h, m);
            inputEl.value = formatIsoLocal(selectedDate);
            trigger.querySelector('span').textContent = formatDisplay(selectedDate);
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
          };
          hInput?.addEventListener('change', syncTime);
          mInput?.addEventListener('change', syncTime);

          popup.querySelector('[data-done]')?.addEventListener('click', () => {
            syncTime();
            popup.classList.add('hidden');
          });
        }

        trigger.addEventListener('click', (e) => {
          e.stopPropagation();
          document.querySelectorAll('.modern-datepicker-open').forEach((p) => {
            if (p !== popup) {
              p.classList.add('hidden');
              p.classList.remove('modern-datepicker-open');
            }
          });
          const opening = popup.classList.contains('hidden');
          popup.classList.toggle('hidden');
          popup.classList.toggle('modern-datepicker-open', opening);
          if (opening) renderCalendar();
        });

        wrapper.appendChild(trigger);
        wrapper.appendChild(popup);
      });
    }

    document.addEventListener('click', () => {
      document.querySelectorAll('.modern-select-menu-open, .modern-datepicker-open').forEach((el) => {
        el.classList.add('hidden');
        el.classList.remove('modern-select-menu-open', 'modern-datepicker-open');
      });
    });

    function filterSearchPicker(inputId, listId) {
      const q = (document.getElementById(inputId)?.value || '').trim().toLowerCase();
      const listEl = document.getElementById(listId);
      if (!listEl) return;

      if (q === '') {
        listEl.classList.add('hidden');
        return;
      }

      let anyVisible = false;
      listEl.querySelectorAll('.filter-item').forEach((item) => {
        const label = item.dataset.label || '';
        const cb = item.querySelector('input[type="checkbox"]');
        const isChecked = cb ? cb.checked : false;
        const show = !isChecked && label.includes(q);
        item.classList.toggle('hidden', !show);
        if (show) anyVisible = true;
      });

      listEl.classList.toggle('hidden', !anyVisible);
    }

    function syncPickerTags(listId, tagsId, inputId = null, justChecked = false) {
      const listEl = document.getElementById(listId);
      const tagsEl = document.getElementById(tagsId);
      if (!listEl || !tagsEl) return;

      if (justChecked) {
        listEl.classList.add('hidden');
        if (inputId) {
          const inp = document.getElementById(inputId);
          if (inp) inp.value = '';
        }
      }

      tagsEl.innerHTML = '';
      listEl.querySelectorAll('input[type="checkbox"]:checked').forEach((cb) => {
        const labelText = cb.closest('label')?.querySelector('span')?.textContent || cb.value;
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-notion-sidebar border border-notion-border text-sm text-notion-text font-medium';
        chip.innerHTML = `<span>${labelText}</span><button type="button" class="text-notion-gray hover:text-notion-red"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>`;
        chip.querySelector('button').addEventListener('click', () => {
          cb.checked = false;
          syncPickerTags(listId, tagsId, inputId, false);
        });
        tagsEl.appendChild(chip);
      });
      lucide.createIcons();
    }

    function selectSinglePicker(hiddenId, inputId, listId, tagId, value, label, searchWrapId = null) {
      const hiddenEl = document.getElementById(hiddenId);
      const inputEl = document.getElementById(inputId);
      const listEl = document.getElementById(listId);
      const tagEl = document.getElementById(tagId);
      if (hiddenEl) hiddenEl.value = value;
      if (inputEl) inputEl.value = '';
      if (listEl) listEl.classList.add('hidden');
      if (searchWrapId) {
        document.getElementById(searchWrapId)?.classList.add('hidden');
      }
      if (tagEl) {
        const wrapArg = searchWrapId ? `'${searchWrapId}'` : 'null';
        tagEl.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-notion-sidebar border border-notion-border text-sm text-notion-text font-medium"><span>${label}</span><button type="button" onclick="clearSinglePicker('${hiddenId}', '${tagId}', ${wrapArg})" class="text-notion-gray hover:text-notion-red"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></span>`;
        lucide.createIcons();
      }
    }

    function clearSinglePicker(hiddenId, tagId, searchWrapId = null) {
      const hiddenEl = document.getElementById(hiddenId);
      const tagEl = document.getElementById(tagId);
      if (hiddenEl) hiddenEl.value = '';
      if (tagEl) tagEl.innerHTML = '';
      if (searchWrapId) {
        document.getElementById(searchWrapId)?.classList.remove('hidden');
      }
    }

    function initModernFileUploads() {
      document.querySelectorAll('input[type="file"].modern-file, input[type="file"].modern-file-compact, input[type="file"].modern-file-icon').forEach((fileEl) => {
        if (fileEl.dataset.upgraded) return;
        fileEl.dataset.upgraded = '1';
        fileEl.classList.add('hidden');

        const isIconOnly = fileEl.classList.contains('modern-file-icon');
        const isCompact = fileEl.classList.contains('modern-file-compact');
        const placeholder = fileEl.dataset.placeholder || (isCompact ? 'Upload Gambar' : 'Pilih atau seret file ke sini...');

        const wrapper = document.createElement('div');
        wrapper.className = (isCompact || isIconOnly) ? 'inline-flex items-center shrink-0' : 'w-full';
        fileEl.parentNode.insertBefore(wrapper, fileEl.nextSibling);

        const btn = document.createElement('button');
        btn.type = 'button';
        if (isIconOnly) {
          btn.title = placeholder;
          btn.className = 'p-2 bg-white border border-notion-border rounded-md text-notion-gray hover:text-notion-blue hover:bg-notion-hover transition-colors flex items-center justify-center relative';
        } else {
          btn.className = isCompact
            ? 'inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-notion-border rounded-md text-sm text-notion-gray hover:text-notion-text hover:bg-notion-hover transition-colors whitespace-nowrap'
            : 'w-full flex items-center justify-between gap-3 px-3.5 py-2.5 bg-notion-sidebar/60 border border-dashed border-notion-border rounded-lg text-sm hover:bg-notion-hover/60 hover:border-notion-gray transition-all text-left';
        }

        const updateLabel = () => {
          const file = fileEl.files && fileEl.files[0];
          if (isIconOnly) {
            if (file) {
              btn.title = file.name;
              btn.innerHTML = `<i data-lucide="image" class="w-4 h-4 text-notion-blue"></i><span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-emerald-500 border border-white"></span>`;
            } else {
              btn.title = placeholder;
              btn.innerHTML = `<i data-lucide="image-plus" class="w-4 h-4"></i>`;
            }
          } else if (file) {
            const name = file.name.length > (isCompact ? 14 : 36) ? file.name.slice(0, isCompact ? 12 : 34) + '…' : file.name;
            btn.innerHTML = isCompact
              ? `<i data-lucide="image" class="w-4 h-4 text-notion-blue shrink-0"></i><span class="text-notion-text font-medium">${name}</span>`
              : `<span class="flex items-center gap-2.5 min-w-0"><i data-lucide="file-check-2" class="w-4 h-4 text-emerald-600 shrink-0"></i><span class="truncate font-medium text-notion-text">${name}</span></span><span class="text-sm text-notion-blue font-medium shrink-0">Ganti</span>`;
          } else {
            btn.innerHTML = isCompact
              ? `<i data-lucide="image-plus" class="w-4 h-4 shrink-0"></i><span>${placeholder}</span>`
              : `<span class="flex items-center gap-2.5 min-w-0"><i data-lucide="upload-cloud" class="w-4 h-4 text-notion-gray shrink-0"></i><span class="truncate text-notion-gray">${placeholder}</span></span><span class="px-2.5 py-1 bg-white border border-notion-border rounded text-sm font-medium text-notion-text shrink-0">Pilih File</span>`;
          }
          lucide.createIcons();
        };

        btn.addEventListener('click', () => fileEl.click());
        fileEl.addEventListener('change', updateLabel);
        fileEl._resetModernLabel = updateLabel;

        wrapper.appendChild(btn);
        updateLabel();
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      initModernSelects();
      initModernDatePickers();
      initModernFileUploads();

      @if(session('toast_success'))
        showToast('success', @json(session('toast_success')));
      @endif
      @if(session('toast_error'))
        showToast('error', @json(session('toast_error')));
      @endif
      @if($errors->any())
        @foreach($errors->all() as $err)
          showToast('error', @json($err));
        @endforeach
      @endif
    });
  </script>
  @stack('scripts')
</body>
</html>

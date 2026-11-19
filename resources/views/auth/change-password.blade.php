@extends('layouts.app')

@section('title', 'Ganti Password')
@section('breadcrumb', 'Ganti Password')

@section('content')
<div class="space-y-4">
  <i data-lucide="key-round" class="w-12 h-12 text-notion-text/80"></i>
  <h1 class="text-3xl font-bold tracking-tight">Ganti Password</h1>
  <p class="text-notion-gray text-sm">Perbarui kata sandi akun Anda secara berkala untuk menjaga keamanan data ujian.</p>
</div>

<form method="POST" action="{{ route('password.update') }}" class="max-w-md bg-white border border-notion-border rounded-xl p-6 shadow-sm space-y-4">
  @csrf
  <div class="space-y-1.5">
    <label for="current_password" class="block text-sm font-medium">Password Saat Ini</label>
    <div class="relative">
      <input type="password" id="current_password" name="current_password" required class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-notion-blue focus:ring-1 focus:ring-notion-blue">
      <button type="button" onclick="togglePasswordField('current_password', this)" aria-label="Lihat password saat ini" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text transition-colors">
        <i data-lucide="eye" class="w-4 h-4"></i>
      </button>
    </div>
  </div>
  <div class="space-y-1.5">
    <label for="new_password" class="block text-sm font-medium">Password Baru</label>
    <div class="relative">
      <input type="password" id="new_password" name="password" required minlength="6" class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-notion-blue focus:ring-1 focus:ring-notion-blue">
      <button type="button" onclick="togglePasswordField('new_password', this)" aria-label="Lihat password baru" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text transition-colors">
        <i data-lucide="eye" class="w-4 h-4"></i>
      </button>
    </div>
  </div>
  <div class="space-y-1.5">
    <label for="password_confirmation" class="block text-sm font-medium">Konfirmasi Password Baru</label>
    <div class="relative">
      <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-notion-blue focus:ring-1 focus:ring-notion-blue">
      <button type="button" onclick="togglePasswordField('password_confirmation', this)" aria-label="Lihat konfirmasi password baru" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text transition-colors">
        <i data-lucide="eye" class="w-4 h-4"></i>
      </button>
    </div>
  </div>
  <div class="pt-2 flex gap-2">
    <button type="submit" class="px-4 py-2 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors">Simpan Password</button>
    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors">Batal</a>
  </div>
</form>

<script>
  function togglePasswordField(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.innerHTML = `<i data-lucide="${isHidden ? 'eye-off' : 'eye'}" class="w-4 h-4"></i>`;
    lucide.createIcons();
  }
</script>
@endsection

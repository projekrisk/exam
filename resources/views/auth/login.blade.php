<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | {{ \App\Models\AppSetting::getValue('school_name', 'SMAN 1 Malingping') }}</title>
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
          fontFamily: { sans: ['"Bricolage Grotesque"', 'sans-serif'] },
          colors: {
            notion: {
              bg: '#ffffff', sidebar: '#f7f7f5', hover: '#efefed',
              text: '#222222', gray: '#666666', border: '#e5e5e5',
              blue: '#2563eb', blueHover: '#1d4ed8', red: '#e03e3e', redBg: '#fbdbdc'
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-white sm:bg-notion-sidebar min-h-screen flex items-center justify-center p-0 sm:p-4 antialiased text-notion-text text-sm">
  <div class="w-full sm:max-w-md bg-white sm:border sm:border-notion-border sm:rounded-xl p-6 sm:p-8 sm:shadow-sm space-y-6">
    <div class="space-y-2">
      <img src="{{ asset('favicon.png') }}" alt="Logo" class="w-10 h-10 rounded-lg object-contain">
      <h1 class="text-2xl font-bold tracking-tight">Panel Ujian</h1>
      <p class="text-sm text-notion-gray">Silakan masuk menggunakan username dan kata sandi akun Anda.</p>
    </div>

    @if($errors->any())
      <div class="p-3 bg-notion-redBg border border-red-200 rounded-md text-sm text-notion-red flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
      @csrf
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Username</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus placeholder="Masukkan username..."
               class="w-full border border-notion-border rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue focus:ring-1 focus:ring-notion-blue">
      </div>

      <div class="space-y-1.5">
        <label for="login-password" class="block text-sm font-medium">Password</label>
        <div class="relative">
          <input type="password" id="login-password" name="password" required placeholder="Masukkan password..."
                 class="w-full border border-notion-border rounded-md pl-3 pr-10 py-2 text-sm bg-white focus:outline-none focus:border-notion-blue focus:ring-1 focus:ring-notion-blue">
          <button type="button" onclick="toggleLoginPassword(this)" aria-label="Lihat password" class="absolute inset-y-0 right-0 px-3 flex items-center text-notion-gray hover:text-notion-text transition-colors">
            <i data-lucide="eye" class="w-4 h-4"></i>
          </button>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm text-notion-gray cursor-pointer">
        <input type="checkbox" name="remember" class="rounded border-notion-border">
        Ingat sesi saya
      </label>

      <button type="submit" class="w-full px-4 py-2.5 bg-notion-blue text-white text-sm font-medium rounded-md hover:bg-notion-blueHover transition-colors shadow-sm flex items-center justify-center gap-2">
        <i data-lucide="log-in" class="w-4 h-4"></i> Masuk
      </button>
    </form>
  </div>
  <script>
    function toggleLoginPassword(btn) {
      const input = document.getElementById('login-password');
      if (!input) return;
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.innerHTML = `<i data-lucide="${isHidden ? 'eye-off' : 'eye'}" class="w-4 h-4"></i>`;
      lucide.createIcons();
    }
    lucide.createIcons();
  </script>
</body>
</html>

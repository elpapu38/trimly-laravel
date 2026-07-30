<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $pageTitle ?? 'Trimly' }}</title>
  <meta name="description" content="Encontrá y reservá turnos en las mejores barberías y salones cerca tuyo.">
  {{-- Tailwind compilado (no CDN) para que cargue rápido incluso en conexiones/dispositivos lentos --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-ink-900 text-cream-light font-sans antialiased min-h-screen flex flex-col">
  @include('partials.header')

  <div id="toast-container"></div>

  @php
    $flashData = [];
    foreach (['success', 'error', 'info', 'warning'] as $ftype) {
        if (session()->has($ftype)) $flashData[$ftype] = [session($ftype)];
    }
  @endphp
  @if(!empty($flashData))
  <script>
    const _flashMessages = @json($flashData);
  </script>
  @endif

  <main class="flex-1">
    @yield('content')
  </main>

  @include('partials.footer')

  <script>
  const toastColors = {
    success: 'bg-emerald-900/90 border-emerald-500/40 text-emerald-200',
    error:   'bg-red-900/90 border-red-500/40 text-red-200',
    info:    'bg-blue-900/90 border-blue-500/40 text-blue-200',
    warning: 'bg-amber-900/90 border-amber-500/40 text-amber-200',
  };
  const toastIcons = { success:'✓', error:'✕', info:'ℹ', warning:'⚠' };

  function showToast(type, message, duration = 4000) {
    const container = document.getElementById('toast-container');
    const toast     = document.createElement('div');
    const cls       = toastColors[type] || toastColors.info;
    toast.className = `toast mb-2 flex items-start gap-3 px-4 py-3 rounded-xl border backdrop-blur-sm text-sm ${cls}`;
    toast.innerHTML = `<span class="shrink-0 font-bold">${toastIcons[type]||'•'}</span><span class="flex-1">${message}</span><button onclick="this.closest('.toast').remove()" class="shrink-0 opacity-50 hover:opacity-100 text-xs ml-1">✕</button>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('hiding');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  if (typeof _flashMessages !== 'undefined') {
    Object.entries(_flashMessages).forEach(([type, msgs]) => {
      msgs.forEach(msg => showToast(type, msg));
    });
  }

  window.trimlyAction = async function(url, body, onSuccess) {
    try {
      const r = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: new URLSearchParams(body)
      });
      const d = await r.json();
      if (d.success) {
        showToast('success', d.message || 'Acción completada correctamente.');
        if (onSuccess) onSuccess(d);
      } else {
        showToast('error', d.error || 'Ocurrió un error. Intentá de nuevo.');
      }
      return d;
    } catch(e) {
      showToast('error', 'Error de conexión. Verificá tu internet.');
      return null;
    }
  };

  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.style.opacity = '1';
          e.target.style.transform = 'translateY(0)';
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('[data-anim]').forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity .5s ease, transform .5s ease';
      obs.observe(el);
    });
  }
  </script>
  @stack('scripts')
</body>
</html>

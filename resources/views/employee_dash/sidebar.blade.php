@php
  $currentPath = '/'.request()->path();
  $nav = [
    '/mi-panel' => ['🏠', 'Dashboard'],
    '/mi-panel/turnos' => ['📅', 'Mis turnos'],
    '/mi-panel/nuevo-turno' => ['➕', 'Cargar turno'],
    '/mi-panel/servicios' => ['✂', 'Mis servicios'],
    '/mi-panel/fotos' => ['📷', 'Mis fotos'],
    '/mi-panel/perfil' => ['👤', 'Mi perfil'],
  ];
@endphp
<aside class="w-56 shrink-0 bg-ink-800 border-r border-ink-700 min-h-screen fixed top-0 left-0 pt-16 z-20 hidden lg:flex flex-col">
  <div class="px-4 py-2 border-b border-ink-700/60">
    <p class="text-xs text-ink-500 uppercase tracking-widest mb-1">Empleado</p>
    <p class="font-semibold text-cream-light text-sm truncate">{{ $employee->name ?? '' }}</p>
    <p class="text-xs text-gold truncate">{{ $employee->shop->name ?? '' }}</p>
  </div>
  <nav class="flex-1 px-2 py-4 space-y-0.5">
    @foreach($nav as $path => [$icon, $label])
    @php $active = str_starts_with($currentPath, $path) && ($path === '/mi-panel' ? $currentPath === '/mi-panel' : true); @endphp
    <a href="{{ url($path) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $active ? 'bg-gold/10 text-gold border border-gold/20' : 'text-ink-400 hover:text-cream-light hover:bg-ink-700/50' }}">
      <span class="text-base w-5 text-center">{{ $icon }}</span>{{ $label }}
    </a>
    @endforeach
  </nav>
  <div class="px-4 py-2 border-t border-ink-700/60 space-y-1">
    <a href="{{ url('/cuenta/contrasena') }}" class="block text-xs text-ink-500 hover:text-gold transition-colors">⚙ Mi cuenta</a>
    <a href="{{ url('/logout') }}" class="block text-xs text-ink-500 hover:text-red-400 transition-colors">⎋ Cerrar sesión</a>
  </div>
</aside>

<div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-ink-800/95 backdrop-blur border-t border-ink-700/50 flex overflow-x-auto" style="border-top-width:1px">
  @foreach($nav as $path => [$icon, $label])
  @php $active = str_starts_with($currentPath, $path) && ($path === '/mi-panel' ? $currentPath === '/mi-panel' : true); @endphp
  <a href="{{ url($path) }}" class="flex flex-col items-center gap-0 py-1.5 px-2.5 text-[10px] whitespace-nowrap shrink-0 {{ $active ? 'text-gold' : 'text-ink-400' }}">
    <span class="text-sm">{{ $icon }}</span><span>{{ $label }}</span>
  </a>
  @endforeach
</div>

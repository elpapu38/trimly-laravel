@php
  $cur = request()->path() === '/' ? '/' : '/'.request()->path();
  $nav = [
    ['url' => '/panel', 'label' => 'Dashboard', 'icon' => '📊'],
    ['url' => '/panel/agenda', 'label' => 'Agenda', 'icon' => '📅'],
    ['url' => '/panel/turnos', 'label' => 'Turnos', 'icon' => '✂️'],
    ['url' => '/panel/empleados', 'label' => 'Empleados', 'icon' => '👥'],
    ['url' => '/panel/servicios', 'label' => 'Servicios', 'icon' => '💈'],
    ['url' => '/panel/resenas', 'label' => 'Reseñas', 'icon' => '⭐'],
    ['url' => '/panel/estadisticas', 'label' => 'Estadísticas', 'icon' => '📈'],
    ['url' => '/panel/local', 'label' => 'Mi local', 'icon' => '⚙️'],
  ];
@endphp
<aside class="hidden lg:flex flex-col w-52 shrink-0 bg-ink-800 border-r border-ink-700 min-h-screen pt-4 pb-6 px-3">
  @if(!empty($shop))
  <div class="mb-5 px-2">
    <p class="text-[10px] font-bold uppercase tracking-widest text-ink-500 mb-1">Panel del local</p>
    <p class="text-sm font-semibold text-cream-light truncate">{{ $shop->name }}</p>
    @if($shop->status !== 'active')
      <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full">{{ ucfirst($shop->status) }}</span>
    @elseif($shop->verified)
      <span class="text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-full">✓ Verificado</span>
    @endif
  </div>
  @endif
  <nav class="flex-1 space-y-0.5">
    @foreach($nav as $item)
    @php $active = $cur === $item['url'] || ($item['url'] !== '/panel' && str_starts_with($cur, $item['url'])); @endphp
    <a href="{{ url($item['url']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $active ? 'bg-gold/15 text-gold border border-gold/20' : 'text-ink-300 hover:bg-ink-700 hover:text-cream-light border border-transparent' }}">
      <span>{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span>
    </a>
    @endforeach
  </nav>
  <div class="border-t border-ink-700 pt-3 mt-3 space-y-1">
    <a href="{{ url('/') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-ink-500 hover:text-gold rounded-lg transition-colors">↗ Ver sitio</a>
    <a href="{{ url('/cuenta/contrasena') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-ink-400 hover:text-gold rounded-lg transition-colors">⚙ Mi cuenta</a>
    <a href="{{ url('/logout') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-red-400 hover:text-red-300 rounded-lg transition-colors">⎋ Salir</a>
  </div>
</aside>
<div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-ink-800/95 backdrop-blur border-t border-ink-700/50 flex overflow-x-auto">
  @foreach($nav as $item)
  @php $active = $cur === $item['url'] || ($item['url'] !== '/panel' && str_starts_with($cur, $item['url'])); @endphp
  <a href="{{ url($item['url']) }}" class="flex flex-col items-center gap-0.5 py-2 px-2.5 text-[10px] whitespace-nowrap shrink-0 {{ $active ? 'text-gold' : 'text-ink-400' }}">
    <span class="text-sm">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span>
  </a>
  @endforeach
</div>

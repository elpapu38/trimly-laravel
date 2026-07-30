@extends('layouts.app')
@section('content')
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('shop_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden min-w-0">

    <div class="flex items-center justify-between mb-6">
      <h1 class="font-display text-2xl text-cream-light">Servicios</h1>
      <a href="{{ url('/panel/servicios/nuevo') }}" class="px-5 py-2.5 bg-gold text-ink-900 font-semibold rounded-xl hover:bg-gold-300 transition-colors text-sm">+ Nuevo servicio</a>
    </div>

    @if(empty($grouped))
    <div class="text-center py-20 text-ink-400">
      <p class="text-lg mb-4">Todavía no cargaste servicios</p>
      <a href="{{ url('/panel/servicios/nuevo') }}" class="inline-block px-6 py-3 bg-gold text-ink-900 font-semibold rounded-full">Agregar primero</a>
    </div>
    @else
    @foreach($grouped as $cat => $items)
    <div class="mb-6">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-gold mb-3">{{ $cat }}</h2>
      <div class="bg-ink-800 border border-ink-700 rounded-xl overflow-hidden">
        @foreach($items as $i => $svc)
        <div class="flex items-center gap-4 px-4 py-3 {{ $i > 0 ? 'border-t border-ink-700' : '' }} {{ !$svc['is_active'] ? 'opacity-50' : '' }}">
          @if($svc['image'])<img src="{{ upload_url($svc['image']) }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">@else<div class="w-10 h-10 bg-ink-700 rounded-lg flex items-center justify-center text-xl flex-shrink-0">✂️</div>@endif
          <div class="flex-1 min-w-0">
            <p class="text-cream-light font-medium">{{ $svc['name'] }}</p>
            @if($svc['description'])<p class="text-ink-400 text-xs truncate">{{ $svc['description'] }}</p>@endif
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-gold font-semibold">{{ money((float)$svc['price']) }}</p>
            <p class="text-ink-400 text-xs">{{ duracionTexto((int)$svc['duration_min']) }}</p>
          </div>
          <div class="flex gap-2 flex-shrink-0 ml-2">
            <a href="{{ url('/panel/servicios/'.$svc['id']) }}" class="px-3 py-1.5 border border-ink-600 text-ink-300 rounded-lg text-xs hover:border-gold/50">Editar</a>
            <form method="POST" action="{{ url('/panel/servicios/'.$svc['id'].'/eliminar') }}" onsubmit="return confirm('¿Desactivar este servicio?')">
              @csrf
              <button class="px-3 py-1.5 border border-red-500/30 text-red-400 rounded-lg text-xs hover:bg-red-500/10">Desactivar</button>
            </form>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endforeach
    @endif
  </main>
</div>
@endsection

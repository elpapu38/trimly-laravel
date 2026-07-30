@extends('layouts.app')
@section('content')
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('shop_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden min-w-0">
    <div class="flex items-center justify-between mb-5">
      <h1 class="font-display text-xl font-bold text-cream-light">Profesionales</h1>
      <a href="{{ url('/panel/empleados/nuevo') }}" class="bg-gold hover:bg-gold-500 text-ink-900 font-bold px-4 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
        <span>+</span><span class="hidden sm:inline">Nuevo profesional</span>
      </a>
    </div>

    @if($employees->isEmpty())
    <div class="text-center py-16 bg-ink-800 border border-ink-700 rounded-2xl">
      <div class="text-4xl mb-3">👥</div>
      <h2 class="font-semibold text-cream-light mb-2">Sin profesionales todavía</h2>
      <p class="text-sm text-ink-400 mb-5">Agregá los profesionales de tu local para que los clientes puedan elegir con quién atenderse.</p>
      <a href="{{ url('/panel/empleados/nuevo') }}" class="bg-gold text-ink-900 font-bold px-6 py-3 rounded-xl text-sm hover:bg-gold-500 transition-colors">+ Agregar primer profesional</a>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($employees as $emp)
      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 flex flex-col">
        <div class="flex items-center gap-3 mb-3">
          @if($emp->avatar)
          <img src="{{ upload_url($emp->avatar) }}" class="w-12 h-12 rounded-full object-cover border border-ink-600 shrink-0">
          @else
          <div class="w-12 h-12 rounded-full bg-ink-700 border border-ink-600 flex items-center justify-center text-lg font-bold text-gold shrink-0">{{ initials($emp->name) }}</div>
          @endif
          <div class="min-w-0">
            <p class="font-semibold text-cream-light truncate">{{ $emp->name }}</p>
            @if($emp->specialty)<p class="text-xs text-ink-400 truncate">{{ $emp->specialty }}</p>@endif
          </div>
        </div>

        @if($emp->services->isNotEmpty())
        <div class="mb-3">
          <p class="text-xs text-ink-500 mb-1.5">Servicios:</p>
          <div class="flex flex-wrap gap-1">
            @foreach($emp->services->take(4) as $s)
            <span class="text-[10px] bg-ink-700 text-ink-300 border border-ink-600 px-2 py-0.5 rounded-full truncate max-w-[100px]">{{ $s->name }}</span>
            @endforeach
            @if($emp->services->count() > 4)<span class="text-[10px] text-ink-500">+{{ $emp->services->count()-4 }} más</span>@endif
          </div>
        </div>
        @endif

        <div class="flex items-center justify-between mt-auto pt-3 border-t border-ink-700/50">
          <span class="text-[11px] px-2 py-0.5 rounded-full {{ $emp->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-ink-700 text-ink-500' }}">{{ $emp->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
          <a href="{{ url('/panel/empleados/'.$emp->id) }}" class="text-xs bg-ink-700 hover:bg-ink-600 text-ink-200 px-3 py-1.5 rounded-lg transition-colors">Editar</a>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </main>
</div>
@endsection

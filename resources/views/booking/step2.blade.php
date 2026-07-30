@extends('layouts.app')
@section('content')
@php $bookedService = \App\Models\Service::find($booking['service_id'] ?? 0); @endphp
<div class="min-h-screen bg-ink-900 py-10">
<div class="max-w-3xl mx-auto px-4">

  @include('booking.steps', ['currentStep' => 2])

  <div class="flex items-center gap-4 mb-8 p-4 bg-ink-800 rounded-xl border border-ink-600">
    <div class="flex-1">
      <p class="text-xs text-ink-400 uppercase tracking-wide">Servicio seleccionado</p>
      <p class="text-cream-light font-medium">{{ $bookedService->name ?? '' }} — {{ money((float)($bookedService->price ?? 0)) }} · {{ duracionTexto((int)($bookedService->duration_min ?? 0)) }}</p>
    </div>
    <a href="{{ url('/reservar/'.$shop->slug) }}" class="text-gold text-sm hover:underline">Cambiar</a>
  </div>

  <h1 class="font-display text-2xl text-cream-light mb-6">¿Con quién querés atenderte?</h1>

  <form method="POST" action="{{ url('/reservar/'.$shop->slug.'/empleado') }}">
    @csrf
    <label class="flex items-center gap-4 p-4 mb-3 bg-ink-800 border border-ink-600 rounded-xl cursor-pointer hover:border-gold/50 transition-all has-[:checked]:border-gold has-[:checked]:bg-gold/5">
      <input type="radio" name="employee_id" value="0" {{ ($booking['employee_id'] ?? null) === 0 ? 'checked' : '' }} class="sr-only" required>
      <div class="w-12 h-12 rounded-full bg-gold/20 flex items-center justify-center text-gold text-lg flex-shrink-0">✨</div>
      <div>
        <p class="font-medium text-cream-light">Cualquier profesional disponible</p>
        <p class="text-sm text-ink-400">El primer disponible en el horario que elijas</p>
      </div>
    </label>

    @foreach($employees as $emp)
    <label class="flex items-center gap-4 p-4 mb-3 bg-ink-800 border border-ink-600 rounded-xl cursor-pointer hover:border-gold/50 transition-all has-[:checked]:border-gold has-[:checked]:bg-gold/5">
      <input type="radio" name="employee_id" value="{{ $emp->id }}" {{ ($booking['employee_id'] ?? null) == $emp->id ? 'checked' : '' }} class="sr-only">
      @if($emp->avatar)
        <img src="{{ upload_url($emp->avatar) }}" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
      @else
        <div class="w-12 h-12 rounded-full bg-ink-600 flex items-center justify-center text-cream-light text-lg font-bold flex-shrink-0">{{ initials($emp->name) }}</div>
      @endif
      <div class="flex-1">
        <p class="font-medium text-cream-light">{{ $emp->name }}</p>
        @if($emp->specialty)<p class="text-sm text-ink-400">{{ $emp->specialty }}</p>@endif
      </div>
      @if($emp->instagram)
        <a href="https://instagram.com/{{ $emp->instagram }}" target="_blank" class="text-ink-400 hover:text-gold text-sm">&#64;{{ $emp->instagram }}</a>
      @endif
    </label>
    @endforeach

    <div class="flex justify-between mt-8">
      <a href="{{ url('/reservar/'.$shop->slug) }}" class="px-6 py-3 border border-ink-600 text-ink-300 rounded-full hover:border-gold/50 transition-colors">← Atrás</a>
      <button type="submit" class="px-8 py-3 bg-gold text-ink-900 font-semibold rounded-full hover:bg-gold-300 transition-colors">Continuar →</button>
    </div>
  </form>
</div>
</div>
@endsection

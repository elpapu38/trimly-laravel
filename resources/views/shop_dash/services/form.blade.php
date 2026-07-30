@extends('layouts.app')
@section('content')
@php
  $isEdit = (bool) $service;
  $action = $isEdit ? url("/panel/servicios/{$service->id}") : url('/panel/servicios');
@endphp
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('shop_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-2xl">

    <div class="flex items-center gap-3 mb-6">
      <a href="{{ url('/panel/servicios') }}" class="text-ink-400 hover:text-gold">← Volver</a>
      <h1 class="font-display text-2xl text-cream-light">{{ $isEdit ? 'Editar — '.$service->name : 'Nuevo servicio' }}</h1>
    </div>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
      @csrf
      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-sm text-ink-300 mb-1">Nombre del servicio *</label>
            <input type="text" name="name" required value="{{ old('name', $service->name ?? '') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('name') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm text-ink-300 mb-1">Descripción</label>
            <textarea name="description" rows="2" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light resize-none">{{ old('description', $service->description ?? '') }}</textarea>
          </div>
          <div>
            <label class="block text-sm text-ink-300 mb-1">Precio (ARS) *</label>
            <input type="number" name="price" min="0" step="50" required value="{{ old('price', $service->price ?? '') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('price') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light">
          </div>
          <div>
            <label class="block text-sm text-ink-300 mb-1">Duración (minutos) *</label>
            <select name="duration_min" required class="input-gold w-full bg-ink-700 border {{ $errors->has('duration_min') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light">
              @foreach([15,20,30,45,60,75,90,120] as $min)
              <option value="{{ $min }}" {{ old('duration_min', $service->duration_min ?? 30) == $min ? 'selected' : '' }}>{{ duracionTexto($min) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm text-ink-300 mb-1">Categoría</label>
            <select name="category_id" id="catSelect" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light" onchange="toggleNewCat(this)">
              <option value="">Sin categoría</option>
              @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ ($service->category_id ?? 0) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
              @endforeach
              <option value="new">+ Nueva categoría</option>
            </select>
          </div>
          <div id="newCatField" class="hidden">
            <label class="block text-sm text-ink-300 mb-1">Nombre de nueva categoría</label>
            <input type="text" name="new_category" placeholder="Ej: Coloración" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light">
          </div>
          <div>
            <label class="block text-sm text-ink-300 mb-1">% Seña online (0 = sin seña)</label>
            <input type="number" name="deposit_pct" min="0" max="100" value="{{ old('deposit_pct', $service->deposit_pct ?? 0) }}" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light">
          </div>
          <div>
            <label class="block text-sm text-ink-300 mb-1">Orden</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $service->sort_order ?? 0) }}" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light">
          </div>
          @if($isEdit)
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="is_active" value="1" {{ $service->is_active ? 'checked' : '' }} class="w-4 h-4 accent-gold">
              <span class="text-sm text-cream-light">Servicio activo</span>
            </label>
          </div>
          @endif
          <div class="sm:col-span-2">
            <label class="block text-sm text-ink-300 mb-1">Imagen del servicio</label>
            @if($service?->image)<img src="{{ upload_url($service->image) }}" class="w-24 h-24 rounded-xl object-cover mb-2">@endif
            <input type="file" name="image" accept="image/*" class="w-full text-ink-300 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gold/20 file:text-gold hover:file:bg-gold/30">
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <a href="{{ url('/panel/servicios') }}" class="px-6 py-3 border border-ink-600 text-ink-300 rounded-xl hover:border-gold/50 transition-colors">Cancelar</a>
          <button type="submit" class="px-8 py-3 bg-gold text-ink-900 font-semibold rounded-xl hover:bg-gold-300 transition-colors">{{ $isEdit ? 'Guardar cambios' : 'Crear servicio' }}</button>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
function toggleNewCat(sel) { document.getElementById('newCatField').classList.toggle('hidden', sel.value !== 'new'); }
</script>
@endsection

@extends('layouts.app')
@section('content')
<div class="min-h-screen bg-ink-900 py-16">
<div class="max-w-2xl mx-auto px-4">

  <div class="text-center mb-8">
    <h1 class="font-display text-3xl text-cream-light mb-2">Registrá tu local</h1>
    <p class="text-ink-400">Completá los datos básicos. Podés editar todo después desde el panel.</p>
  </div>

  <form method="POST" action="{{ url('/registrar-local') }}" enctype="multipart/form-data">
    @csrf
    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-6 space-y-5">

      <div>
        <p class="text-sm text-ink-300 mb-3 font-medium">Tipo de local *</p>
        <div class="grid grid-cols-3 gap-3">
          @php $types = ['barbershop'=>['label'=>'Barbería','icon'=>'💈'], 'salon'=>['label'=>'Salón de belleza','icon'=>'💇'], 'mixed'=>['label'=>'Mixto','icon'=>'✨']]; @endphp
          @foreach($types as $val => $t)
          <label class="flex flex-col items-center gap-2 p-3 bg-ink-700 border border-ink-600 rounded-xl cursor-pointer has-[:checked]:border-gold has-[:checked]:bg-gold/5 text-center">
            <input type="radio" name="type" value="{{ $val }}" {{ old('type') === $val ? 'checked' : '' }} required class="sr-only">
            <span class="text-2xl">{{ $t['icon'] }}</span><span class="text-xs text-cream-light">{{ $t['label'] }}</span>
          </label>
          @endforeach
        </div>
        @error('type')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2"><label class="block text-sm text-ink-300 mb-1">Nombre del local *</label><input type="text" name="name" required value="{{ old('name') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('name') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light"></div>
        <div class="sm:col-span-2"><label class="block text-sm text-ink-300 mb-1">Descripción breve</label><textarea name="description" rows="2" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light resize-none">{{ old('description') }}</textarea></div>
        <div><label class="block text-sm text-ink-300 mb-1">Teléfono *</label><input type="text" name="phone" required value="{{ old('phone') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('phone') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light"></div>
        <div><label class="block text-sm text-ink-300 mb-1">Email de contacto</label><input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()->email ?? '') }}" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light"></div>
        <div><label class="block text-sm text-ink-300 mb-1">Dirección *</label><input type="text" name="address" required value="{{ old('address') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('address') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light"></div>
        <div><label class="block text-sm text-ink-300 mb-1">Ciudad *</label><input type="text" name="city" required value="{{ old('city') }}" class="input-gold w-full bg-ink-700 border {{ $errors->has('city') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-3 text-cream-light"></div>
        <div><label class="block text-sm text-ink-300 mb-1">Provincia</label><input type="text" name="province" value="{{ old('province') }}" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light"></div>
        <div><label class="block text-sm text-ink-300 mb-1">Logo (opcional)</label><input type="file" name="logo" accept="image/*" class="w-full text-ink-300 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gold/20 file:text-gold hover:file:bg-gold/30"></div>
      </div>

      <div class="p-4 bg-gold/10 border border-gold/30 rounded-xl text-sm">
        <p class="text-gold font-medium mb-1">ℹ️ Revisión previa</p>
        <p class="text-ink-300">Tu local quedará en estado <strong>pendiente</strong> hasta que el administrador lo apruebe. Te notificaremos por email.</p>
      </div>

      <button type="submit" class="w-full py-3 bg-gold text-ink-900 font-bold rounded-xl hover:bg-gold-300 transition-colors">Registrar local</button>
    </div>
  </form>
</div>
</div>
@endsection

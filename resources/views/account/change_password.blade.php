@extends('layouts.app')
@section('content')
<div class="min-h-screen bg-ink-900 pt-24 pb-12">
<div class="max-w-md mx-auto px-4">
  <h1 class="font-display text-2xl font-bold text-cream-light mb-2">Cambiar contraseña</h1>
  <p class="text-ink-400 text-sm mb-6">Tu nueva contraseña debe tener al menos 8 caracteres.</p>

  <form method="POST" action="{{ url('/cuenta/contrasena') }}" class="bg-ink-800 border border-ink-700 rounded-2xl p-6 space-y-4">
    @csrf
    <div><label class="block text-xs text-ink-400 mb-1">Contraseña actual *</label><input type="password" name="current_password" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
    <div><label class="block text-xs text-ink-400 mb-1">Nueva contraseña *</label><input type="password" name="new_password" required minlength="8" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
    <div><label class="block text-xs text-ink-400 mb-1">Confirmar nueva contraseña *</label><input type="password" name="confirm_password" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
    <button type="submit" class="w-full bg-gold hover:bg-gold-500 text-ink-900 font-bold py-3 rounded-xl text-sm transition-colors">Guardar nueva contraseña</button>
  </form>

  <div class="mt-4 text-center space-x-4">
    <a href="{{ url('/cuenta/email') }}" class="text-xs text-ink-400 hover:text-gold transition-colors">Cambiar email</a>
    @if($user->role !== 'superadmin')<a href="{{ url('/cuenta/eliminar') }}" class="text-xs text-red-500/70 hover:text-red-400 transition-colors">Eliminar cuenta</a>@endif
  </div>
</div>
</div>
@endsection

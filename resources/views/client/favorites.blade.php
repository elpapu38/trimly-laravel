@extends('layouts.app')
@section('content')
<div class="min-h-screen bg-ink-900 py-16">
<div class="max-w-5xl mx-auto px-4">
  <h1 class="font-display text-3xl text-cream-light mb-8">Mis favoritos</h1>
  @if($favorites->isEmpty())
  <div class="text-center py-16">
    <p class="text-ink-400 text-lg mb-4">Todavía no marcaste ningún local como favorito</p>
    <a href="{{ url('/buscar') }}" class="inline-block px-6 py-3 bg-gold text-ink-900 font-semibold rounded-full">Buscar locales</a>
  </div>
  @else
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($favorites as $shop)
    <a href="{{ url('/local/'.$shop->slug) }}" class="card-lift bg-ink-800 border border-ink-700 hover:border-gold/30 rounded-2xl overflow-hidden p-4">
      <p class="font-display font-semibold text-cream-light">{{ $shop->name }}</p>
      <p class="text-xs text-ink-400 mt-1">{{ $shop->city }}</p>
    </a>
    @endforeach
  </div>
  <div class="mt-8">{{ $favorites->links() }}</div>
  @endif
</div>
</div>
@endsection

@extends('layouts.app')
@section('content')
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('employee_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 lg:ml-56 max-w-2xl">

    <h1 class="font-display text-2xl font-bold text-cream-light mb-2">Mis servicios</h1>
    <p class="text-ink-400 text-sm mb-6">Elegí qué servicios ofrecés vos específicamente.</p>

    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 sm:p-6">
      <p id="svc-msg" class="text-sm mb-4 hidden"></p>
      @if($allServices->isEmpty())
      <p class="text-ink-500 text-sm text-center py-6">El local todavía no tiene servicios cargados.</p>
      @else
      <div class="space-y-2 mb-6" id="services-list">
        @foreach($allServices as $s)
        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all {{ in_array($s->id, $assignedIds) ? 'border-gold/40 bg-gold/5' : 'border-ink-600 hover:border-ink-500' }}">
          <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" {{ in_array($s->id, $assignedIds) ? 'checked' : '' }} onchange="toggleHighlight(this)" class="mt-0.5 accent-gold shrink-0">
          <div class="flex-1 min-w-0">
            <p class="text-cream-light text-sm font-medium">{{ $s->name }}</p>
            <p class="text-ink-400 text-xs">{{ money((float)$s->price) }} · {{ duracionTexto((int)$s->duration_min) }}</p>
          </div>
        </label>
        @endforeach
      </div>
      <button onclick="saveServices()" class="w-full bg-gold hover:bg-gold-500 text-ink-900 font-bold py-3 rounded-xl text-sm transition-colors">Guardar cambios</button>
      @endif
    </div>
  </main>
</div>

<script>
function toggleHighlight(cb) {
  const label = cb.closest('label');
  if (cb.checked) { label.classList.replace('border-ink-600','border-gold/40'); label.classList.add('bg-gold/5'); }
  else { label.classList.replace('border-gold/40','border-ink-600'); label.classList.remove('bg-gold/5'); }
}
async function saveServices() {
  const msg = document.getElementById('svc-msg');
  const checks = [...document.querySelectorAll('#services-list input:checked')];
  const fd = new FormData();
  checks.forEach(c => fd.append('service_ids[]', c.value));
  const res = await fetch(@json(url('/mi-panel/servicios')), { method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} });
  const data = await res.json();
  msg.textContent = data.success ? `✓ Guardado — ${data.assigned} servicio(s) asignados.` : (data.error || 'Error.');
  msg.className = `text-sm mb-4 ${data.success ? 'text-emerald-400' : 'text-red-400'}`;
  msg.classList.remove('hidden');
}
</script>
@endsection

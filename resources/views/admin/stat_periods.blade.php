@extends('layouts.app')
@section('content')
<div class="flex min-h-screen pt-16 pb-16 lg:pb-0">
@include('admin.sidebar')
<main class="flex-1 p-4 sm:p-5 lg:p-8 overflow-x-hidden min-w-0 max-w-2xl">
  <h1 class="font-display text-xl font-bold text-cream-light mb-2">Períodos de estadísticas</h1>
  <p class="text-sm text-ink-400 mb-6">Los períodos creados aquí aparecen como filtros en las estadísticas de todos los locales.</p>

  <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 mb-6">
    <h3 class="font-semibold text-cream-light mb-4">Crear período</h3>
    <div class="space-y-3">
      <div><label class="block text-xs text-ink-400 mb-1">Nombre del período *</label><input type="text" id="p-name" placeholder="Ej: Verano 2025, Q1 2026" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="block text-xs text-ink-400 mb-1">Desde *</label><input type="date" id="p-from" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
        <div><label class="block text-xs text-ink-400 mb-1">Hasta *</label><input type="date" id="p-to" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
      </div>
      <p id="period-msg" class="text-xs hidden"></p>
      <button onclick="createPeriod()" class="w-full bg-gold hover:bg-gold-500 text-ink-900 font-bold py-2.5 rounded-xl text-sm transition-colors">Crear período</button>
    </div>
  </div>

  @if($periods->isEmpty())
  <p class="text-center text-ink-500 py-10">No hay períodos creados aún.</p>
  @else
  <div class="space-y-2">
    @foreach($periods as $p)
    <div class="flex items-center justify-between bg-ink-800 border border-ink-700 rounded-xl px-4 py-3">
      <div>
        <p class="text-cream-light font-medium text-sm">{{ $p->name }}</p>
        <p class="text-xs text-ink-500">{{ fecha($p->date_from) }} → {{ fecha($p->date_to) }} · por {{ $p->creator->name ?? '' }}</p>
      </div>
      <button onclick="deletePeriod({{ $p->id }}, '{{ addslashes($p->name) }}')" class="text-xs text-red-400 hover:text-red-300 border border-red-500/30 hover:border-red-400/50 px-3 py-1.5 rounded-lg transition-all">Eliminar</button>
    </div>
    @endforeach
  </div>
  @endif
</main>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
async function createPeriod() {
  const msg = document.getElementById('period-msg');
  const name = document.getElementById('p-name').value.trim();
  const from = document.getElementById('p-from').value;
  const to = document.getElementById('p-to').value;
  if (!name || !from || !to) { showMsg(msg, 'Completá todos los campos.', false); return; }
  if (from > to) { showMsg(msg, 'La fecha de inicio debe ser anterior al fin.', false); return; }
  const fd = new FormData();
  fd.append('name', name); fd.append('date_from', from); fd.append('date_to', to);
  const res = await fetch(@json(url('/admin/periodos/crear')), { method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': CSRF_TOKEN} });
  const data = await res.json();
  if (data.success) { showMsg(msg, '✓ Período creado.', true); setTimeout(() => location.reload(), 800); }
  else showMsg(msg, data.error || 'Error.', false);
}
async function deletePeriod(id, name) {
  if (!confirm(`¿Eliminar el período "${name}"?`)) return;
  const res = await fetch(@json(url('/admin/periodos')) + `/${id}/eliminar`, { method: 'POST', headers: {'X-CSRF-TOKEN': CSRF_TOKEN} });
  const data = await res.json();
  if (data.success) location.reload(); else alert('Error al eliminar.');
}
function showMsg(el, text, ok) { el.textContent = text; el.className = `text-xs ${ok ? 'text-emerald-400' : 'text-red-400'}`; el.classList.remove('hidden'); }
</script>
@endsection

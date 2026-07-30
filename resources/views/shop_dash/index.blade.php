@extends('layouts.app')
@section('content')
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('shop_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden min-w-0">

    @if($adminNotes->isNotEmpty())
    <div class="mb-6 space-y-2">
      @php
        $styleByAction = [
          'note' => ['icon'=>'💬','border'=>'border-blue-500/30','bg'=>'bg-blue-500/10','text'=>'text-blue-300','label'=>'Nota del administrador'],
          'suspended' => ['icon'=>'⚠️','border'=>'border-amber-500/30','bg'=>'bg-amber-500/10','text'=>'text-amber-300','label'=>'Tu local fue suspendido'],
          'banned' => ['icon'=>'🚫','border'=>'border-red-500/30','bg'=>'bg-red-500/10','text'=>'text-red-300','label'=>'Tu local fue baneado'],
        ];
      @endphp
      @foreach($adminNotes as $n)
      @php $st = $styleByAction[$n->action] ?? $styleByAction['note']; @endphp
      <div class="{{ $st['bg'] }} border {{ $st['border'] }} rounded-2xl p-4 flex items-start gap-3" id="admin-note-{{ $n->id }}">
        <span class="text-xl shrink-0">{{ $st['icon'] }}</span>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold {{ $st['text'] }}">{{ $st['label'] }}</p>
          @if($n->reason)<p class="text-sm text-ink-200 mt-1">{{ $n->reason }}</p>@endif
          <p class="text-xs text-ink-500 mt-1.5">
            Por {{ $n->admin->name ?? '' }} · {{ fecha($n->created_at) }}
            @if($n->expires_at) · Vence el {{ fecha($n->expires_at) }}@endif
          </p>
        </div>
        <button onclick="dismissAdminNote({{ $n->id }}, this)" title="Marcar como leído" class="shrink-0 ml-1 text-ink-500 hover:text-ink-200 transition-colors p-1 rounded-lg hover:bg-white/5" aria-label="Descartar aviso">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      @endforeach
    </div>

    <div id="dismiss-note-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60" onclick="window.__closeDismissModal()"></div>
      <div class="relative bg-ink-900 border border-white/10 rounded-2xl p-5 max-w-sm w-full shadow-xl">
        <p class="text-sm font-semibold text-cream-light">¿Marcar este aviso como visto?</p>
        <p class="text-xs text-ink-400 mt-1.5">Una vez que lo elimines de tu panel, no vas a poder volver a verlo acá.</p>
        <div class="flex gap-2 justify-end mt-4">
          <button type="button" onclick="window.__closeDismissModal()" class="px-3 py-1.5 rounded-lg text-xs text-ink-300 hover:bg-white/5 transition-colors">Cancelar</button>
          <button type="button" onclick="window.__confirmDismissModal()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-500/30 hover:bg-blue-500/30 transition-colors">Sí, eliminar</button>
        </div>
      </div>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="font-display text-xl font-bold text-cream-light">{{ $shop->name }}</h1>
        <div class="flex flex-wrap items-center gap-2 mt-1">
          <span class="text-xs text-ink-400">{{ \App\Models\Shop::typeLabel($shop->type) }}</span>
          @if($shop->verified)<span class="text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-full">✓ Verificado</span>@endif
          @if($shop->featured)<span class="text-[10px] bg-gold/10 text-gold border border-gold/20 px-2 py-0.5 rounded-full">⭐ Destacado</span>@endif
          @if($shop->status !== 'active')
          <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full">{{ $shop->status === 'suspended' ? '⏸ Suspendido' : ucfirst($shop->status) }}</span>
          @endif
        </div>
      </div>
      <a href="{{ url('/local/'.$shop->slug) }}" target="_blank" class="text-xs border border-ink-600 hover:border-gold/40 text-ink-300 hover:text-gold px-3 py-2 rounded-xl transition-all self-start sm:self-center">↗ Ver perfil público</a>
    </div>

    @if($shop->status === 'suspended')
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 mb-5">
      <p class="text-amber-300 font-semibold text-sm">⚠ Tu local está suspendido</p>
      @if($shop->suspension_reason)<p class="text-amber-400 text-xs mt-1">{{ $shop->suspension_reason }}</p>@endif
      @if($shop->suspension_until)<p class="text-amber-400/70 text-xs mt-1">Hasta: {{ fecha($shop->suspension_until,'d/m/Y H:i') }}</p>@endif
    </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      @foreach([
        ['Turnos del mes', $monthStats->total ?? 0, 'text-cream-light', '📅'],
        ['Completados', $monthStats->completed ?? 0, 'text-emerald-400', '✓'],
        ['Cancelados', $monthStats->cancelled ?? 0, 'text-red-400', '✕'],
        ['Ingresos', money((float)($monthStats->revenue ?? 0)), 'text-gold', '💰'],
      ] as [$l, $v, $c, $ic])
      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-4">
        <div class="flex items-center justify-between mb-2"><span class="text-sm">{{ $ic }}</span><span class="text-[10px] text-ink-500 uppercase font-semibold">{{ $l }}</span></div>
        <div class="text-xl font-bold {{ $c }}">{{ $v }}</div>
      </div>
      @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-cream-light text-sm">Turnos de hoy</h2>
          <a href="{{ url('/panel/agenda') }}" class="text-xs text-gold hover:underline">Ver agenda →</a>
        </div>
        @if($todayAppts->isEmpty())
        <div class="text-center py-8"><p class="text-2xl mb-2">📭</p><p class="text-sm text-ink-500">Sin turnos para hoy.</p></div>
        @else
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
          @php $sc = ['pending'=>'text-amber-400','confirmed'=>'text-emerald-400','completed'=>'text-blue-400','cancelled_client'=>'text-ink-500','cancelled_shop'=>'text-ink-500','no_show'=>'text-ink-500']; @endphp
          @foreach($todayAppts as $a)
          @php $c = $sc[$a->status] ?? 'text-ink-400'; @endphp
          <div class="flex items-center gap-3 p-3 bg-ink-700/40 rounded-xl">
            <div class="text-center shrink-0 w-12">
              <p class="text-xs font-bold text-cream-light">{{ hora($a->start_time) }}</p>
              <p class="text-[10px] text-ink-500">{{ hora($a->end_time) }}</p>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-cream-light truncate">{{ $a->client_name }}</p>
              <p class="text-xs text-ink-400 truncate">{{ $a->service->name ?? '' }} · {{ $a->employee->name ?? '' }}</p>
            </div>
            <span class="{{ $c }} text-[10px] font-semibold uppercase shrink-0">{{ $a->status==='confirmed'?'✓':($a->status==='pending'?'⏳':($a->status==='completed'?'✅':'✕')) }}</span>
          </div>
          @endforeach
        </div>
        @endif
      </div>

      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-cream-light text-sm">Reseñas recientes</h2>
          <a href="{{ url('/panel/resenas') }}" class="text-xs text-gold hover:underline">Ver todas →</a>
        </div>
        @if($recentReviews->isEmpty())
        <div class="text-center py-8"><p class="text-2xl mb-2">⭐</p><p class="text-sm text-ink-500">Sin reseñas todavía.</p></div>
        @else
        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
          @foreach($recentReviews as $r)
          <div class="p-3 bg-ink-700/40 rounded-xl">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-medium text-cream-light">{{ $r->appointment->client_name ?? '' }}</span>
              <span class="text-amber-400 text-xs">{{ str_repeat('★',(int)$r->rating) }}{{ str_repeat('☆',5-(int)$r->rating) }}</span>
            </div>
            <p class="text-xs text-ink-300 line-clamp-2">{{ truncate($r->comment ?? '', 100) }}</p>
          </div>
          @endforeach
        </div>
        @endif
      </div>

      <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 lg:col-span-2">
        <h2 class="font-semibold text-cream-light mb-3 text-sm">Accesos rápidos</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          @foreach([['/panel/turnos','📋','Ver turnos'],['/panel/empleados','👥','Profesionales'],['/panel/servicios','💈','Servicios'],['/panel/estadisticas','📈','Estadísticas']] as [$u, $ic, $l])
          <a href="{{ url($u) }}" class="flex flex-col items-center gap-2 p-4 bg-ink-700/40 hover:bg-ink-700 rounded-xl transition-colors group">
            <span class="text-2xl">{{ $ic }}</span>
            <span class="text-xs text-ink-300 group-hover:text-cream-light transition-colors text-center">{{ $l }}</span>
          </a>
          @endforeach
        </div>
      </div>
    </div>
  </main>
</div>

<script>
(function () {
  var _pendingNoteId = null, _pendingBtn = null;
  window.dismissAdminNote = function (noteId, btn) {
    _pendingNoteId = noteId; _pendingBtn = btn;
    var modal = document.getElementById('dismiss-note-modal');
    if (modal) modal.classList.remove('hidden');
  };
  window.__closeDismissModal = function () {
    var modal = document.getElementById('dismiss-note-modal');
    if (modal) modal.classList.add('hidden');
    _pendingNoteId = null; _pendingBtn = null;
  };
  window.__confirmDismissModal = function () {
    var noteId = _pendingNoteId, btn = _pendingBtn;
    window.__closeDismissModal();
    if (noteId == null) return;
    var card = document.getElementById('admin-note-' + noteId);
    if (!card) return;
    if (btn) { btn.disabled = true; btn.style.opacity = '0.4'; }
    fetch(@json(url('/panel/notas')) + '/' + noteId + '/descartar', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json().catch(() => { throw new Error('Respuesta inválida del servidor (HTTP ' + r.status + ')'); }))
    .then(data => {
      if (data.ok) {
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease, max-height 0.3s ease';
        card.style.opacity = '0'; card.style.transform = 'translateY(-6px)';
        setTimeout(() => card.remove(), 320);
        if (window.showToast) showToast('success', 'Aviso marcado como visto.');
      } else {
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
        if (window.showToast) showToast('error', data.msg || 'No se pudo descartar el aviso.');
      }
    })
    .catch(err => {
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      if (window.showToast) showToast('error', 'No se pudo conectar con el servidor. Probá de nuevo en unos segundos.');
      console.error('dismissAdminNote error:', err);
    });
  };
})();
</script>
@endsection

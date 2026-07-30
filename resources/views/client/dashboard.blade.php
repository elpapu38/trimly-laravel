@extends('layouts.app')
@section('content')
@php $statuses = config('trimly.appointment_statuses', []); @endphp
<div class="min-h-screen bg-ink-900 py-10">
<div class="max-w-4xl mx-auto px-4">

  <div class="flex items-center gap-4 mb-8">
    <div class="w-14 h-14 rounded-full bg-gold/20 flex items-center justify-center text-gold text-2xl font-bold">{{ initials($userRow->name) }}</div>
    <div>
      <h1 class="font-display text-2xl text-cream-light">Hola, {{ explode(' ', $userRow->name)[0] }} 👋</h1>
      <p class="text-ink-400 text-sm">{{ $userRow->email }}</p>
    </div>
  </div>

  <div class="flex gap-1 mb-8 bg-ink-800 p-1 rounded-xl w-fit">
    <button onclick="showTab('turnos')" id="tab-turnos" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium transition-all text-gold bg-ink-700">Mis turnos</button>
    <button onclick="showTab('perfil')" id="tab-perfil" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium transition-all text-ink-400 hover:text-cream-light">Mi perfil</button>
    <a href="{{ url('/mis-favoritos') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-all text-ink-400 hover:text-cream-light">♥ Favoritos</a>
  </div>

  <div id="pane-turnos">
    @if($upcoming->isNotEmpty())
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gold mb-4">Próximos</h2>
    <div class="space-y-3 mb-8">
      @foreach($upcoming as $a)
      @php $sc = $statuses[$a->status] ?? ['label' => $a->status, 'color' => 'gray']; @endphp
      <div class="p-4 bg-ink-800 border border-ink-600 rounded-xl flex items-center gap-4">
        <div class="w-12 h-12 bg-gold/20 rounded-xl flex flex-col items-center justify-center flex-shrink-0">
          <span class="text-gold text-lg font-bold leading-none">{{ fecha($a->date,'d') }}</span>
          <span class="text-gold text-xs uppercase">{{ fecha($a->date,'M') }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-cream-light font-medium">{{ $a->shop->name ?? '' }}</p>
          <p class="text-ink-400 text-sm">{{ $a->service->name ?? '' }} · {{ hora($a->start_time) }} hs</p>
        </div>
        <span class="px-2 py-1 rounded-full text-xs bg-{{ $sc['color'] }}-500/20 text-{{ $sc['color'] }}-400 border border-{{ $sc['color'] }}-500/30 flex-shrink-0">{{ $sc['label'] }}</span>
        @if(in_array($a->status, ['pending','confirmed']))
        <form method="POST" action="{{ url('/cancelar-turno/'.$a->id) }}" onsubmit="return confirm('¿Cancelar este turno?')">
          @csrf
          <button class="text-red-400 text-xs hover:text-red-300">Cancelar</button>
        </form>
        @endif
      </div>
      @endforeach
    </div>
    @endif

    @if($past->isNotEmpty())
    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-400 mb-4">Historial</h2>
    <div class="space-y-2">
      @foreach($past->take(10) as $a)
      <div class="p-3 bg-ink-800/50 border border-ink-700 rounded-xl flex items-center gap-3">
        <div class="flex-1 min-w-0">
          <p class="text-cream-light text-sm font-medium">{{ $a->shop->name ?? '' }} — {{ $a->service->name ?? '' }}</p>
          <p class="text-ink-400 text-xs">{{ fecha($a->date,'d/m/Y') }} · {{ hora($a->start_time) }} hs</p>
        </div>
        <span class="text-gold text-sm font-medium">{{ money((float)$a->price) }}</span>
        @if($a->status === 'completed' && !$a->review)
        <button onclick="openReviewModal({{ $a->id }})" class="text-xs text-gold hover:text-gold-300 border border-gold/30 hover:border-gold/60 px-2 py-1 rounded-lg transition-all ml-2 shrink-0">⭐ Reseñar</button>
        @elseif($a->status === 'completed' && $a->review)
        <span class="text-xs text-ink-500 ml-2 shrink-0">✓ Reseñado</span>
        @endif
      </div>
      @endforeach
    </div>
    @endif

    @if($upcoming->isEmpty() && $past->isEmpty())
    <div class="text-center py-16">
      <p class="text-ink-400 text-lg mb-4">Todavía no tenés turnos reservados</p>
      <a href="{{ url('/') }}" class="inline-block px-6 py-3 bg-gold text-ink-900 font-semibold rounded-full">Buscar barbería</a>
    </div>
    @endif
  </div>

  <div id="pane-perfil" class="hidden">
    <div class="max-w-lg space-y-6">
      <form method="POST" action="{{ url('/mi-cuenta') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 space-y-4">
          <h3 class="font-semibold text-cream-light text-sm">Datos personales</h3>
          <div class="flex items-center gap-4">
            @if($userRow->avatar)
              <img src="{{ upload_url($userRow->avatar) }}" alt="Avatar" class="w-16 h-16 rounded-full object-cover border-2 border-ink-600 shrink-0">
            @else
              <div class="w-16 h-16 rounded-full bg-ink-700 border-2 border-ink-600 flex items-center justify-center text-2xl text-ink-400 shrink-0">{{ mb_strtoupper(mb_substr($userRow->name ?? 'U',0,1)) }}</div>
            @endif
            <div class="flex-1">
              <label class="block text-xs text-ink-400 mb-1">Foto de perfil <span class="text-ink-600">(opcional, máx. 3 MB)</span></label>
              <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="text-xs text-ink-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-ink-700 file:text-cream-light hover:file:bg-ink-600 cursor-pointer">
            </div>
          </div>
          <div>
            <label class="block text-xs text-ink-400 mb-1">Nombre *</label>
            <input type="text" name="name" value="{{ old('name', $userRow->name) }}" required class="input-gold w-full bg-ink-700 border {{ $errors->has('name') ? 'border-red-500' : 'border-ink-600' }} rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none">
          </div>
          <div>
            <label class="block text-xs text-ink-400 mb-1">Teléfono</label>
            @php $currentPhone = trim(old('phone', $userRow->phone ?? '')); @endphp
            <input type="tel" name="phone" value="{{ $currentPhone }}" placeholder="Ej: +5491166778899" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none">
            @if($currentPhone === '')
            <p class="text-xs text-ink-500 mt-1">📵 No tenés teléfono guardado. Podés agregarlo aquí.</p>
            @else
            <p class="text-xs text-emerald-500/70 mt-1">✓ Teléfono guardado</p>
            @endif
          </div>
          <button type="submit" class="w-full py-2.5 bg-gold text-ink-900 font-bold rounded-xl text-sm hover:bg-gold-300 transition-colors">Guardar datos</button>
        </div>
      </form>

      <form method="POST" action="{{ url('/cuenta/email') }}">
        @csrf
        <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 space-y-4">
          <h3 class="font-semibold text-cream-light text-sm">Cambiar email</h3>
          <div><label class="block text-xs text-ink-400 mb-1">Email actual</label><p class="text-sm text-ink-300 bg-ink-700/50 rounded-xl px-4 py-2.5">{{ $userRow->email }}</p></div>
          <div><label class="block text-xs text-ink-400 mb-1">Nuevo email *</label><input type="email" name="new_email" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
          <div><label class="block text-xs text-ink-400 mb-1">Contraseña actual *</label><input type="password" name="password" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
          <button type="submit" class="w-full py-2.5 bg-ink-600 hover:bg-ink-500 text-cream-light font-semibold rounded-xl text-sm transition-colors">Actualizar email</button>
        </div>
      </form>

      <form method="POST" action="{{ url('/cuenta/contrasena') }}">
        @csrf
        <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 space-y-4">
          <h3 class="font-semibold text-cream-light text-sm">Cambiar contraseña</h3>
          <div><label class="block text-xs text-ink-400 mb-1">Contraseña actual *</label><input type="password" name="current_password" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
          <div><label class="block text-xs text-ink-400 mb-1">Nueva contraseña *</label><input type="password" name="new_password" required minlength="8" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
          <div><label class="block text-xs text-ink-400 mb-1">Confirmar nueva contraseña *</label><input type="password" name="confirm_password" required class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-2.5 text-sm text-cream-light focus:outline-none"></div>
          <button type="submit" class="w-full py-2.5 bg-ink-600 hover:bg-ink-500 text-cream-light font-semibold rounded-xl text-sm transition-colors">Cambiar contraseña</button>
        </div>
      </form>

      <div class="text-center">
        <a href="{{ url('/cuenta/eliminar') }}" class="text-xs text-red-500/70 hover:text-red-400 transition-colors">Eliminar mi cuenta</a>
      </div>
    </div>
  </div>
</div>
</div>

<div id="reviewModal" class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center">
  <div class="bg-ink-800 border border-ink-600 rounded-2xl p-6 max-w-md w-full mx-4">
    <h3 class="font-display text-xl text-cream-light mb-4">Dejar reseña</h3>
    <div id="starRating" class="flex gap-2 mb-4">
      @for($i = 1; $i <= 5; $i++)
      <button onclick="setRating({{ $i }})" class="star-btn text-3xl text-ink-600 hover:text-gold transition-colors" data-val="{{ $i }}">★</button>
      @endfor
    </div>
    <textarea id="reviewComment" rows="3" placeholder="Contá tu experiencia (opcional)" class="input-gold w-full bg-ink-700 border border-ink-600 rounded-xl px-4 py-3 text-cream-light resize-none mb-4"></textarea>
    <div class="flex gap-3">
      <button onclick="closeReviewModal()" class="flex-1 py-2 border border-ink-600 text-ink-300 rounded-xl hover:border-gold/50">Cancelar</button>
      <button onclick="submitReview()" class="flex-1 py-2 bg-gold text-ink-900 font-semibold rounded-xl hover:bg-gold-300">Publicar</button>
    </div>
  </div>
</div>

<script>
function showTab(tab) {
  ['turnos','perfil'].forEach(t => {
    document.getElementById('pane-'+t).classList.toggle('hidden', t !== tab);
    document.getElementById('tab-'+t).className = t === tab
      ? 'tab-btn px-4 py-2 rounded-lg text-sm font-medium transition-all text-gold bg-ink-700'
      : 'tab-btn px-4 py-2 rounded-lg text-sm font-medium transition-all text-ink-400 hover:text-cream-light';
  });
}
let reviewAppointmentId = null, selectedRating = 0;
function openReviewModal(id) { reviewAppointmentId = id; document.getElementById('reviewModal').classList.remove('hidden'); document.getElementById('reviewModal').classList.add('flex'); }
function closeReviewModal() {
  document.getElementById('reviewModal').classList.add('hidden'); document.getElementById('reviewModal').classList.remove('flex');
  reviewAppointmentId = null; selectedRating = 0;
  document.querySelectorAll('.star-btn').forEach(b => b.classList.remove('text-gold'));
  document.getElementById('reviewComment').value = '';
}
function setRating(val) {
  selectedRating = val;
  document.querySelectorAll('.star-btn').forEach(b => {
    b.classList.toggle('text-gold', parseInt(b.dataset.val) <= val);
    b.classList.toggle('text-ink-600', parseInt(b.dataset.val) > val);
  });
}
async function submitReview() {
  if (!selectedRating) return alert('Elegí un puntaje');
  const btn = document.querySelector('#reviewModal button[onclick="submitReview()"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Publicando…'; }
  const formData = new FormData();
  formData.append('rating', selectedRating);
  formData.append('comment', document.getElementById('reviewComment').value);
  try {
    const res = await fetch(`{{ url('/resena') }}/${reviewAppointmentId}`, { method: 'POST', body: formData, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} });
    const data = await res.json();
    if (data.success) { closeReviewModal(); location.reload(); }
    else { alert(data.error || 'Error al publicar'); if (btn) { btn.disabled = false; btn.textContent = 'Publicar'; } }
  } catch(e) {
    alert('Error de conexión. Intentá de nuevo.');
    if (btn) { btn.disabled = false; btn.textContent = 'Publicar'; }
  }
}
</script>
@endsection

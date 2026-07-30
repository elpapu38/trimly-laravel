@extends('layouts.app')
@section('content')
@php
  $diasES = ['1'=>'Lun','2'=>'Mar','3'=>'Mié','4'=>'Jue','5'=>'Vie','6'=>'Sáb','7'=>'Dom'];
  $prevWeek = date('Y-m-d', strtotime($weekStart.' -7 days'));
  $nextWeek = date('Y-m-d', strtotime($weekStart.' +7 days'));
@endphp
<div class="flex min-h-screen pt-16 pb-20 lg:pb-6">
  @include('employee_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 lg:ml-56 overflow-x-hidden">

    <h1 class="font-display text-2xl font-bold text-cream-light mb-6">Mis turnos</h1>

    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-4 mb-6">
      <div class="flex items-center justify-between mb-3">
        <a href="?date={{ $prevWeek }}&status={{ urlencode($selectedStatus) }}" class="w-8 h-8 flex items-center justify-center rounded-xl bg-ink-700/60 hover:bg-ink-700 text-ink-300 hover:text-cream transition-all text-lg shrink-0">‹</a>
        <p class="text-xs font-semibold text-ink-400 uppercase tracking-widest">{{ date('d/m', strtotime($weekStart)) }} — {{ date('d/m', strtotime($weekEnd)) }}</p>
        <a href="?date={{ $nextWeek }}&status={{ urlencode($selectedStatus) }}" class="w-8 h-8 flex items-center justify-center rounded-xl bg-ink-700/60 hover:bg-ink-700 text-ink-300 hover:text-cream transition-all text-lg shrink-0">›</a>
      </div>
      <div class="flex gap-1 overflow-x-auto pb-1 scrollbar-none lg:justify-between">
        @php $cur = $weekStart; @endphp
        @for($i = 0; $i < 7; $i++)
        @php
          $dow = date('N', strtotime($cur));
          $isSelected = $cur === $selectedDate;
          $isToday = $cur === date('Y-m-d');
          $dayAppts = $weekMap->get($cur, collect());
          $cnt = $dayAppts->count();
          $completed = $dayAppts->where('status', 'completed')->count();
        @endphp
        <a href="?date={{ $cur }}&status={{ urlencode($selectedStatus) }}" class="flex flex-col items-center gap-0.5 py-2 px-1.5 rounded-xl transition-all shrink-0 w-10 lg:flex-1 relative {{ $isSelected ? 'bg-gold/15 border border-gold/40' : ($isToday ? 'bg-ink-700/40 border border-ink-600' : 'hover:bg-ink-700/50 border border-transparent') }}">
          <span class="text-[9px] uppercase {{ $isSelected ? 'text-gold' : 'text-ink-500' }}">{{ $diasES[$dow] }}</span>
          <span class="text-sm font-bold {{ $isSelected ? 'text-gold' : ($isToday ? 'text-cream-light' : 'text-ink-300') }}">{{ date('d', strtotime($cur)) }}</span>
          @if($cnt > 0)<span class="text-[10px] font-bold {{ $isSelected ? 'text-gold' : 'text-ink-400' }}">{{ $completed }}/{{ $cnt }}</span>@else<span class="text-[10px] text-ink-700">—</span>@endif
        </a>
        @php $cur = date('Y-m-d', strtotime($cur.' +1 day')); @endphp
        @endfor
      </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-6">
      <input type="date" name="date" value="{{ $selectedDate }}" class="input-gold bg-ink-800 border border-ink-600 rounded-xl px-4 py-2 text-cream-light text-sm">
      <select name="status" class="input-gold bg-ink-800 border border-ink-600 rounded-xl px-4 py-2 text-cream-light text-sm">
        <option value="">Todos los estados</option>
        @foreach($statuses as $key => $sc)<option value="{{ $key }}" {{ $selectedStatus === $key ? 'selected' : '' }}>{{ $sc['label'] }}</option>@endforeach
      </select>
      <button type="submit" class="px-5 py-2 bg-gold text-ink-900 font-semibold rounded-xl text-sm hover:bg-gold-300">Filtrar</button>
      <a href="{{ url('/mi-panel/turnos') }}" class="px-5 py-2 border border-ink-600 text-ink-300 rounded-xl text-sm hover:border-gold/50">Limpiar</a>
    </form>

    @if($appointments->isEmpty())
    <div class="text-center py-10 border border-dashed border-ink-700 rounded-2xl text-ink-400 mb-6">Sin turnos para esta fecha.</div>
    @else
    <div class="bg-ink-800 border border-ink-700 rounded-xl overflow-x-auto mb-6">
      <table class="w-full text-sm min-w-[600px]">
        <thead><tr class="border-b border-ink-700">
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Hora</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Cliente</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Servicio</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Precio</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Estado</th>
          <th class="px-4 py-3"></th>
        </tr></thead>
        <tbody>
          @foreach($appointments as $a)
          @php $sc = $statuses[$a->status] ?? ['label' => $a->status, 'color' => 'gray']; @endphp
          <tr class="border-b border-ink-700/50 hover:bg-ink-700/30 transition-colors">
            <td class="px-4 py-3 text-gold font-mono">{{ hora($a->start_time) }}</td>
            <td class="px-4 py-3"><p class="text-cream-light font-medium">{{ $a->client_name }}</p><p class="text-ink-500 text-xs">{{ $a->client_phone }}</p></td>
            <td class="px-4 py-3 text-ink-300">{{ $a->service->name ?? '' }} <span class="text-ink-500 text-xs ml-1">{{ duracionTexto((int)$a->duration_min) }}</span></td>
            <td class="px-4 py-3 text-gold">{{ money((float)$a->price, $employee->shop->currency ?? 'ARS') }}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-{{ $sc['color'] }}-500/20 text-{{ $sc['color'] }}-400">{{ $sc['label'] }}</span></td>
            <td class="px-4 py-3">
              <div class="flex gap-1 flex-wrap">
                @if($a->status === 'pending')<button onclick="changeStatus({{ $a->id }},'confirmed')" class="px-2 py-1 bg-green-600/80 text-white rounded text-xs hover:bg-green-500">Confirmar</button>@endif
                @if($a->status === 'confirmed')<button onclick="changeStatus({{ $a->id }},'completed')" class="px-2 py-1 bg-blue-600/80 text-white rounded text-xs hover:bg-blue-500">Completar</button>@endif
                @if(!in_array($a->status, ['cancelled_client','cancelled_shop','completed']))<button onclick="changeStatus({{ $a->id }},'cancelled_shop')" class="px-2 py-1 bg-red-600/60 text-white rounded text-xs hover:bg-red-500">Cancelar</button>@endif
                @if($a->status === 'confirmed')<button onclick="changeStatus({{ $a->id }},'no_show')" class="px-2 py-1 bg-gray-600/60 text-white rounded text-xs hover:bg-gray-500">No asistió</button>@endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    @if($openAppts->isNotEmpty())
    <div class="bg-ink-800 border border-amber-500/30 rounded-2xl p-4">
      <p class="text-xs font-semibold text-amber-400 uppercase tracking-widest mb-3">Turnos sin empleado asignado ({{ $openAppts->count() }})</p>
      <p class="text-xs text-ink-500 mb-3">Estos turnos están disponibles para que los tomes.</p>
      <div class="space-y-2">
        @foreach($openAppts as $o)
        @php $sc = $statuses[$o->status] ?? ['label' => $o->status, 'color' => 'gray']; @endphp
        <div class="flex items-center gap-3 p-3 bg-ink-700/40 rounded-xl">
          <div class="shrink-0 text-center"><p class="text-xs text-ink-500">{{ fecha($o->date,'d/m') }}</p><p class="font-mono text-gold text-sm">{{ hora($o->start_time) }}</p></div>
          <div class="flex-1 min-w-0"><p class="text-cream-light text-sm font-medium truncate">{{ $o->client_name }}</p><p class="text-ink-400 text-xs">{{ $o->service->name ?? '' }} · {{ duracionTexto((int)$o->duration_min) }}</p></div>
          <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $sc['color'] }}-500/20 text-{{ $sc['color'] }}-400 shrink-0">{{ $sc['label'] }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif
  </main>
</div>

<script>
async function changeStatus(id, status) {
  if (!confirm('¿Cambiar el estado del turno?')) return;
  const form = new FormData();
  form.append('status', status);
  const res = await fetch(@json(url('/mi-panel/turnos')) + `/${id}/status`, { method: 'POST', body: form, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} });
  if (res.ok) location.reload();
  else alert('Error al cambiar el estado.');
}
</script>
@endsection

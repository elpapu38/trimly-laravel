@extends('layouts.app')
@section('content')
<div class="flex">
  @include('shop_dash.sidebar')
  <div class="ml-0 lg:ml-0 flex-1 p-6 min-h-screen bg-ink-900">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <h1 class="font-display text-2xl font-bold text-cream-light">Turnos</h1>
      <div class="flex gap-2">
        <a href="{{ url('/panel/turnos') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border transition-all {{ ($view ?? 'day') === 'day' ? 'bg-gold text-ink-900 border-gold' : 'border-ink-600 text-ink-400 hover:border-ink-500' }}">Del día</a>
        <a href="{{ url('/panel/turnos?view=history') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border transition-all {{ ($view ?? 'day') === 'history' ? 'bg-gold text-ink-900 border-gold' : 'border-ink-600 text-ink-400 hover:border-ink-500' }}">Historial</a>
      </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-6">
      <input type="date" name="date" value="{{ $selectedDate }}" class="input-gold bg-ink-800 border border-ink-600 rounded-xl px-4 py-2 text-cream-light text-sm">
      <select name="status" class="input-gold bg-ink-800 border border-ink-600 rounded-xl px-4 py-2 text-cream-light text-sm">
        <option value="">Todos los estados</option>
        @foreach($statuses as $key => $sc)
        <option value="{{ $key }}" {{ $selectedStatus === $key ? 'selected' : '' }}>{{ $sc['label'] }}</option>
        @endforeach
      </select>
      <button type="submit" class="px-5 py-2 bg-gold text-ink-900 font-semibold rounded-xl text-sm hover:bg-gold-300">Filtrar</button>
      <a href="{{ url('/panel/turnos') }}" class="px-5 py-2 border border-ink-600 text-ink-300 rounded-xl text-sm hover:border-gold/50">Limpiar</a>
    </form>

    @if(empty($appointments))
      <div class="text-center py-16 text-ink-400">Sin turnos para los filtros seleccionados</div>
    @else
    <div class="bg-ink-800 border border-ink-700 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-ink-700">
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Hora</th>
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Cliente</th>
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Servicio</th>
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Empleado</th>
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Precio</th>
            <th class="text-left px-4 py-3 text-ink-400 font-medium">Estado</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($appointments as $a)
          @php $sc = $statuses[$a->status] ?? ['label' => $a->status, 'color' => 'gray']; @endphp
          <tr class="border-b border-ink-700/50 hover:bg-ink-700/30 transition-colors">
            <td class="px-4 py-3 text-gold font-mono">{{ hora($a->start_time) }}</td>
            <td class="px-4 py-3"><p class="text-cream-light font-medium">{{ $a->client_name }}</p><p class="text-ink-500 text-xs">{{ $a->client_phone }}</p></td>
            <td class="px-4 py-3 text-ink-300">{{ $a->service->name ?? '' }}</td>
            <td class="px-4 py-3 text-ink-300">{{ $a->employee->name ?? '' }}</td>
            <td class="px-4 py-3 text-gold">{{ money((float)$a->price) }}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-{{ $sc['color'] }}-500/20 text-{{ $sc['color'] }}-400">{{ $sc['label'] }}</span></td>
            <td class="px-4 py-3">
              <div class="flex gap-1">
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

    @if(($view ?? 'day') === 'history')
    @if(empty($appointments))
    <div class="text-center py-16 text-ink-400 border border-dashed border-ink-700 rounded-2xl mt-4">Sin turnos en el historial.</div>
    @else
    <div class="bg-ink-800 border border-ink-700 rounded-xl overflow-x-auto mt-4">
      <table class="w-full text-sm min-w-[580px]">
        <thead><tr class="border-b border-ink-700">
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Fecha</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Cliente</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Servicio</th>
          <th class="text-left px-4 py-3 text-ink-400 font-medium">Estado</th>
          <th class="px-4 py-3"></th>
        </tr></thead>
        <tbody>
        @foreach($appointments as $a)
        @php $sc = $statuses[$a->status] ?? ['label' => $a->status, 'color' => 'gray']; @endphp
        <tr class="border-b border-ink-700/50 hover:bg-ink-700/20">
          <td class="px-4 py-3 text-gold font-mono text-xs">{{ fecha($a->date,'d/m/Y') }}<span class="block">{{ hora($a->start_time) }}</span></td>
          <td class="px-4 py-3"><p class="text-cream-light">{{ $a->client_name }}</p></td>
          <td class="px-4 py-3 text-ink-300 text-xs">{{ $a->service->name ?? '' }}</td>
          <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-{{ $sc['color'] }}-500/20 text-{{ $sc['color'] }}-400">{{ $sc['label'] }}</span></td>
          <td class="px-4 py-3">
            @if($a->status === 'confirmed')
            <div class="flex gap-1 flex-wrap">
              <button onclick="changeStatus({{ $a->id }},'completed')" class="px-2 py-1 bg-blue-600/80 text-white rounded text-xs">Asistió</button>
              <button onclick="changeStatus({{ $a->id }},'no_show')" class="px-2 py-1 bg-gray-600/60 text-white rounded text-xs">No asistió</button>
            </div>
            @endif
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    @if(($lastPage ?? 1) > 1)
    <div class="flex flex-wrap gap-2 justify-center mt-4">
      @for($pg = 1; $pg <= ($lastPage ?? 1); $pg++)
      <a href="?view=history&page={{ $pg }}" class="px-3 py-1.5 rounded-lg text-sm {{ $pg === ($page ?? 1) ? 'bg-gold text-ink-900 font-bold' : 'bg-ink-700 text-ink-300 hover:bg-ink-600' }}">{{ $pg }}</a>
      @endfor
    </div>
    @endif
    @endif
    @endif

  </div>
</div>

<script>
async function changeStatus(id, status) {
  if (!confirm('¿Cambiar el estado del turno?')) return;
  const form = new FormData();
  form.append('status', status);
  const res = await fetch(@json(url('/panel/turnos')) + `/${id}/status`, { method: 'POST', body: form, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} });
  if (res.ok) location.reload();
  else alert('Error al cambiar el estado. Recargá la página e intentá de nuevo.');
}
</script>
@endsection

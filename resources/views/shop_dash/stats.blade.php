@extends('layouts.app')
@section('content')
@php $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']; @endphp
<div class="flex min-h-screen pt-16 pb-20 lg:pb-0">
  @include('shop_dash.sidebar')
  <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden min-w-0">

    <div class="flex flex-col gap-3 mb-6">
      <h1 class="font-display text-xl font-bold text-cream-light">Estadísticas</h1>

      @if($allPeriods->isNotEmpty())
      <div class="flex flex-wrap gap-2">
        @foreach($allPeriods as $p)
        <a href="?period={{ $p->id }}" class="px-3 py-1.5 rounded-lg text-sm border truncate max-w-[200px] {{ $periodId == $p->id ? 'bg-gold text-ink-900 border-gold font-semibold' : 'border-ink-600 text-ink-300 hover:border-gold/50' }}"
           title="{{ $p->name }} ({{ fecha($p->date_from) }} – {{ fecha($p->date_to) }})">{{ $p->name }}</a>
        @endforeach
      </div>
      @if($activePeriod)
      <p class="text-xs text-gold/80">Mostrando: <strong>{{ $activePeriod->name }}</strong> · {{ fecha($activePeriod->date_from) }} → {{ fecha($activePeriod->date_to) }}</p>
      @else
      <p class="text-xs text-ink-500">Elegí un período para ver las estadísticas.</p>
      @endif
      @else
      <p class="text-xs text-amber-400/80 bg-amber-500/10 border border-amber-500/20 rounded-xl px-3 py-2">El administrador de Trimly todavía no creó períodos de estadísticas. Cuando los cree, aparecerán aquí como filtros.</p>
      @endif
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
      @php $kpis = [
        ['label'=>'Turnos totales', 'val'=>(int)($totals->total ?? 0), 'icon'=>'📋'],
        ['label'=>'Completados', 'val'=>(int)($totals->completed ?? 0), 'icon'=>'✅'],
        ['label'=>'Cancelados', 'val'=>(int)($totals->cancelled ?? 0), 'icon'=>'❌'],
        ['label'=>'Ingresos totales', 'val'=>money((float)($totals->revenue ?? 0)), 'icon'=>'💰'],
      ]; @endphp
      @foreach($kpis as $k)
      <div class="bg-ink-800 border border-ink-700 rounded-xl p-4">
        <span class="text-2xl">{{ $k['icon'] }}</span>
        <p class="text-xl sm:text-2xl font-bold text-cream-light mt-2 break-all">{{ $k['val'] }}</p>
        <p class="text-xs sm:text-sm text-ink-400">{{ $k['label'] }}</p>
      </div>
      @endforeach
    </div>

    <div class="bg-ink-800 border border-ink-700 rounded-xl p-4 sm:p-5 mb-4">
      <h2 class="font-semibold text-cream-light mb-4 text-sm sm:text-base">Ingresos por mes</h2>
      <div class="overflow-x-auto"><div style="min-width:320px"><canvas id="revenueChart" height="120"></canvas></div></div>
    </div>

    <div class="bg-ink-800 border border-ink-700 rounded-xl p-4 sm:p-5 mb-4">
      <h2 class="font-semibold text-cream-light mb-4 text-sm sm:text-base">Turnos por mes</h2>
      <div class="overflow-x-auto"><div style="min-width:320px"><canvas id="apptsChart" height="100"></canvas></div></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
      <div class="bg-ink-800 border border-ink-700 rounded-xl p-4 sm:p-5">
        <h2 class="font-semibold text-cream-light mb-4 text-sm sm:text-base">Servicios más solicitados</h2>
        @if($topServices->isEmpty())
        <p class="text-ink-500 text-sm">Sin datos aún</p>
        @else
        <div class="space-y-3">
          @foreach($topServices as $s)
          <div class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex justify-between mb-1">
                <span class="text-cream-light text-sm truncate">{{ $s->name }}</span>
                <span class="text-ink-400 text-xs ml-2 flex-shrink-0">{{ $s->cnt }} turnos</span>
              </div>
              <div class="w-full bg-ink-700 rounded-full h-1.5">
                <div class="bg-gold h-1.5 rounded-full" style="width:{{ min(100, round($s->cnt / max(1, $topServices[0]->cnt) * 100)) }}%"></div>
              </div>
            </div>
            <span class="text-gold text-sm font-medium flex-shrink-0">{{ money((float)$s->revenue) }}</span>
          </div>
          @endforeach
        </div>
        @endif
      </div>

      <div class="bg-ink-800 border border-ink-700 rounded-xl p-4 sm:p-5">
        <h2 class="font-semibold text-cream-light mb-4 text-sm sm:text-base">Rendimiento por profesional</h2>
        @if($topEmployees->isEmpty())
        <p class="text-ink-500 text-sm">Sin datos aún</p>
        @else
        <div class="space-y-3">
          @foreach($topEmployees as $emp)
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center text-gold text-xs font-bold flex-shrink-0">{{ initials($emp->name) }}</div>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between mb-1">
                <span class="text-cream-light text-sm truncate">{{ $emp->name }}</span>
                <span class="text-ink-400 text-xs ml-2 flex-shrink-0">{{ $emp->cnt }} turnos</span>
              </div>
              <div class="w-full bg-ink-700 rounded-full h-1.5">
                <div class="bg-gold h-1.5 rounded-full" style="width:{{ min(100, round($emp->cnt / max(1, $topEmployees[0]->cnt) * 100)) }}%"></div>
              </div>
            </div>
            <span class="text-gold text-sm font-medium flex-shrink-0">{{ money((float)$emp->revenue) }}</span>
          </div>
          @endforeach
        </div>
        @endif
      </div>
    </div>
  </main>
</div>

<script>
window._trimlyChartData = { months: @json($months), byMonth: @json($byMonth) };
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" onload="window._initTrimlyCharts()" onerror="document.getElementById('chartError').classList.remove('hidden')"></script>
<script>
window._initTrimlyCharts = function () {
  var months = window._trimlyChartData.months;
  var byMonth = window._trimlyChartData.byMonth;
  var revenues = byMonth.map(m => parseFloat(m.revenue) || 0);
  var completed = byMonth.map(m => parseInt(m.completed) || 0);
  var cancelled = byMonth.map(m => parseInt(m.cancelled) || 0);
  Chart.defaults.color = '#A0A0A0';
  Chart.defaults.borderColor = '#2E2E2E';
  var cvRevenue = document.getElementById('revenueChart');
  var cvAppts = document.getElementById('apptsChart');
  if (!cvRevenue || !cvAppts) return;
  new Chart(cvRevenue, { type: 'bar', data: { labels: months, datasets: [{ label:'Ingresos ($)', data:revenues, backgroundColor:'rgba(201,168,76,0.7)', borderColor:'#C9A84C', borderWidth:1, borderRadius:4 }] }, options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true } } } });
  new Chart(cvAppts, { type: 'line', data: { labels: months, datasets: [
    { label:'Completados', data:completed, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,0.1)', tension:0.3, fill:true },
    { label:'Cancelados', data:cancelled, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.1)', tension:0.3, fill:true }
  ] }, options: { responsive:true, scales:{ y:{ beginAtZero:true } } } });
};
if (typeof Chart !== 'undefined') { window._initTrimlyCharts(); }
</script>
<div id="chartError" class="hidden text-center text-ink-400 text-sm mt-2 p-4">No se pudo cargar la librería de gráficos. Verificá tu conexión a internet.</div>
@endsection

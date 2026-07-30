@extends('layouts.app')
@section('content')
@php $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']; @endphp
<div class="flex min-h-screen pt-16 pb-16 lg:pb-0">
@include('admin.sidebar')
<main class="flex-1 p-5 lg:p-8 overflow-x-hidden min-w-0">
  <div class="flex items-center justify-between mb-6">
    <h1 class="font-display text-xl font-bold text-cream-light">Estadísticas globales</h1>
    <p class="text-xs text-ink-500">Datos del año en curso</p>
  </div>

  <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5 mb-5">
    <h2 class="font-semibold text-cream-light mb-4 text-sm">Turnos completados por mes — {{ $year }}</h2>
    <canvas id="monthlyChart" height="90"></canvas>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-5">
    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
      <h2 class="font-semibold text-cream-light mb-3 text-sm">Locales por tipo</h2>
      <div class="space-y-2">
        @php $totalT = max(1, $byType->sum('cnt')); @endphp
        @foreach($byType as $t)
        @php $pct = (int) ($t->cnt / $totalT * 100); @endphp
        <div>
          <div class="flex justify-between text-xs mb-1"><span class="text-ink-300">{{ \App\Models\Shop::typeLabel($t->type) }}</span><span class="text-ink-500">{{ $t->cnt }}</span></div>
          <div class="h-1.5 bg-ink-700 rounded-full"><div class="h-full bg-gold rounded-full" style="width:{{ $pct }}%"></div></div>
        </div>
        @endforeach
      </div>
    </div>
    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
      <h2 class="font-semibold text-cream-light mb-3 text-sm">Top ciudades</h2>
      <div class="space-y-2">
        @php $totalC2 = max(1, $byCity->sum('cnt')); @endphp
        @foreach($byCity as $c)
        @php $pct = (int) ($c->cnt / $totalC2 * 100); @endphp
        <div>
          <div class="flex justify-between text-xs mb-1"><span class="text-ink-300">{{ $c->city }}</span><span class="text-ink-500">{{ $c->cnt }} locales</span></div>
          <div class="h-1.5 bg-ink-700 rounded-full"><div class="h-full bg-blue-400 rounded-full" style="width:{{ $pct }}%"></div></div>
        </div>
        @endforeach
      </div>
    </div>
    <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
      <h2 class="font-semibold text-cream-light mb-3 text-sm">Nuevos usuarios (12m)</h2>
      @if($growth->isEmpty())
      <p class="text-xs text-ink-500 py-4 text-center">Sin datos.</p>
      @else
      <canvas id="growthChart" height="120"></canvas>
      @endif
    </div>
  </div>

  <div class="bg-ink-800 border border-ink-700 rounded-2xl p-5">
    <h2 class="font-semibold text-cream-light mb-4 text-sm">Top 10 locales por ingresos — {{ $year }}</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[400px]">
        <thead><tr class="text-left text-ink-400 text-xs uppercase border-b border-ink-700"><th class="pb-2">#</th><th class="pb-2">Local</th><th class="pb-2">Ciudad</th><th class="pb-2">Turnos</th><th class="pb-2 text-right">Ingresos</th></tr></thead>
        <tbody class="divide-y divide-ink-700/40">
          @forelse($topShops as $i => $sh)
          <tr class="hover:bg-ink-700/20">
            <td class="py-2.5 text-ink-500 text-xs">{{ $i+1 }}</td>
            <td class="py-2.5 font-medium text-cream-light">{{ $sh->name }}</td>
            <td class="py-2.5 text-xs text-ink-400">{{ $sh->city ?: '—' }}</td>
            <td class="py-2.5 text-xs text-ink-300">{{ number_format($sh->total) }}</td>
            <td class="py-2.5 text-right text-gold font-semibold text-xs">{{ money($sh->revenue) }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="py-8 text-center text-ink-500">Sin datos.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#A0A0A0';
Chart.defaults.borderColor = '#2E2E2E';

const monthlyLabels = @json($months);
const monthlyCompleted = @json($byMonth->map(fn($m) => (int) ($m->completed ?? 0)));
const monthlyTotal = @json($byMonth->map(fn($m) => (int) ($m->total_appts ?? 0)));

if (document.getElementById('monthlyChart')) {
  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: monthlyLabels, datasets: [
      { label: 'Completados', data: monthlyCompleted, backgroundColor: 'rgba(201,168,76,0.75)', borderColor: '#C9A84C', borderWidth: 1, borderRadius: 4 },
      { label: 'Total', data: monthlyTotal, backgroundColor: 'rgba(201,168,76,0.2)', borderColor: '#C9A84C', borderWidth: 1, borderRadius: 4, borderDash: [4,2] },
    ] },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
}

const growthEl = document.getElementById('growthChart');
if (growthEl) {
  const growthLabels = @json($growth->pluck('ym'));
  const growthData = @json($growth->map(fn($g) => (int) $g->new_users));
  new Chart(growthEl, {
    type: 'bar',
    data: { labels: growthLabels, datasets: [{ label: 'Nuevos usuarios', data: growthData, backgroundColor: 'rgba(34,197,94,0.65)', borderColor: '#22c55e', borderWidth: 1, borderRadius: 3 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
  });
}
</script>
@endsection

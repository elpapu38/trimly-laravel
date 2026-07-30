@php
  $steps = [1 => 'Servicio', 2 => 'Profesional', 3 => 'Horario', 4 => 'Confirmar'];
@endphp
<div class="flex items-center justify-center mb-8">
  @foreach($steps as $n => $label)
    @if($n > 1)
      <div class="flex-1 h-px {{ $currentStep >= $n ? 'bg-gold' : 'bg-ink-700' }} mx-2"></div>
    @endif
    <div class="flex flex-col items-center gap-1">
      <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all
        {{ $currentStep > $n ? 'bg-gold border-gold text-ink-900' : ($currentStep === $n ? 'border-gold text-gold bg-gold/10' : 'border-ink-600 text-ink-500') }}">
        {{ $currentStep > $n ? '✓' : $n }}
      </div>
      <span class="text-xs {{ $currentStep === $n ? 'text-gold' : 'text-ink-500' }}">{{ $label }}</span>
    </div>
  @endforeach
</div>

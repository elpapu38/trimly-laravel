@php
    $first = explode(' ', $appointment->client_name)[0];
    $cancelUrl = url('/cancelar/' . $appointment->cancel_token);
@endphp
<x-mail-layout title="Turno confirmado ✓">
<p>Hola <strong style="color:#F5F0E8">{{ $first }}</strong>,</p>
<p>Tu turno quedó confirmado:</p>
<div style="background:#111;border:1px solid #1A1A1A;border-radius:10px;padding:16px;margin:16px 0">
  <table style="width:100%;border-collapse:collapse">
    <tr><td style="padding:5px 0;color:#707070;font-size:12px">Local</td><td style="padding:5px 0;color:#F5F0E8;text-align:right"><strong>{{ $shop->name }}</strong></td></tr>
    <tr><td style="padding:5px 0;color:#707070;font-size:12px">Servicio</td><td style="padding:5px 0;color:#F5F0E8;text-align:right">{{ $appointment->service->name ?? '' }}</td></tr>
    <tr><td style="padding:5px 0;color:#707070;font-size:12px">Profesional</td><td style="padding:5px 0;color:#F5F0E8;text-align:right">{{ $appointment->employee->name ?? '' }}</td></tr>
    <tr><td style="padding:5px 0;color:#707070;font-size:12px">Fecha</td><td style="padding:5px 0;color:#C9A84C;text-align:right;font-weight:700">{{ $appointment->date->translatedFormat('d/m/Y') }} a las {{ $appointment->start_time }}</td></tr>
    <tr><td style="padding:5px 0;color:#707070;font-size:12px">Total</td><td style="padding:5px 0;color:#F5F0E8;text-align:right">${{ number_format($appointment->price, 2, ',', '.') }}</td></tr>
  </table>
</div>
<p>Para cancelar tu turno: <a href="{{ $cancelUrl }}" style="color:#C9A84C">{{ $cancelUrl }}</a></p>
</x-mail-layout>

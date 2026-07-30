@php $first = explode(' ', $owner->name)[0]; $link = url('/panel'); @endphp
<x-mail-layout title="Local aprobado ✓" :cta-text="'Ir al panel'" :cta-url="$link">
<p>Hola <strong style="color:#F5F0E8">{{ $first }}</strong>,</p>
<p>¡Tu local <strong style="color:#C9A84C">{{ $shop->name }}</strong> fue aprobado y ya está visible en Trimly!</p>
<p>Ahora podés cargar tus servicios, empleados y empezar a recibir reservas.</p>
</x-mail-layout>

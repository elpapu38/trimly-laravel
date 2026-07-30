@php $first = explode(' ', $user->name)[0]; $link = url('/reset/' . $token); @endphp
<x-mail-layout title="Restablecer contraseña 🔑" :cta-text="'Crear nueva contraseña'" :cta-url="$link">
<p>Hola <strong style="color:#F5F0E8">{{ $first }}</strong>,</p>
<p>Recibimos una solicitud para restablecer tu contraseña. El enlace expira en <strong style="color:#F5F0E8">1 hora</strong>.</p>
<p><a href="{{ $link }}" style="color:#C9A84C;word-break:break-all">{{ $link }}</a></p>
</x-mail-layout>

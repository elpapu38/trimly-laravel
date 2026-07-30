@php $first = explode(' ', $user->name)[0]; $link = url('/verificar/' . $token); @endphp
<x-mail-layout title="Verificá tu cuenta ✉" :cta-text="'Verificar cuenta'" :cta-url="$link">
<p>Hola <strong style="color:#F5F0E8">{{ $first }}</strong>,</p>
<p>Gracias por registrarte en Trimly. Hacé clic abajo para activar tu cuenta.</p>
<p><a href="{{ $link }}" style="color:#C9A84C;word-break:break-all">{{ $link }}</a></p>
</x-mail-layout>

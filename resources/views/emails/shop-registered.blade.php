@php $link = url('/admin/locales'); @endphp
<x-mail-layout title="Nuevo local registrado" :cta-text="'Revisar en Admin'" :cta-url="$link">
<p>Nuevo local registrado en Trimly:</p>
<p><strong style="color:#F5F0E8">{{ $shop->name }}</strong> — {{ $shop->city }}</p>
<p>Dueño: {{ $owner->name }} ({{ $owner->email }})</p>
</x-mail-layout>

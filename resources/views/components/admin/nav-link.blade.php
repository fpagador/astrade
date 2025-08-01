@props(['route', 'icon', 'label'])

@php
    $isActive = request()->routeIs($route);
@endphp

<a href="{{ route($route) }}"
    {{ $attributes->merge([
         'class' => 'flex items-center gap-3 py-2 px-4 rounded hover:bg-gray-700 transition ' . ($isActive ? 'bg-gray-800' : '')
     ]) }}>
    <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0"></i>
    {{ $label }}
</a>

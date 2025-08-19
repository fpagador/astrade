@props(['route', 'icon', 'label', 'parameters' => []])

@php
    $url = route($route, $parameters);

    // Verify that the route is the same and that all parameters match
    $isActive = request()->routeIs($route);

    foreach ($parameters as $key => $value) {
        if (request()->query($key) != $value) {
            $isActive = false;
            break;
        }
    }
@endphp

<a href="{{ $url }}"
    {{ $attributes->merge([
        'class' => 'font-medium  flex items-center gap-3 py-2 px-4 rounded hover:bg-gray-700 transition ' . ($isActive ? 'bg-gray-800 ' : '')
    ]) }}>
    <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0"></i>
    {{ $label }}
</a>

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

<a href="{{ $url }}" class="sidebar-link {{ $isActive ? 'active' : '' }}">
    <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0 transition-colors"></i>
    {{ $label }}
</a>

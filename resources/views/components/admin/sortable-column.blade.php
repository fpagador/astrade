@props([
'label',
'field',
'default' => false,
'route' => null
])

@php
    $routeName = $route ?? \Illuminate\Support\Facades\Route::currentRouteName();
    $currentSort = request('sort', $default ? $field : null);
    $currentDirection = request('direction', $default ? 'asc' : 'asc');

    $isActive = $currentSort === $field;
    $direction = $isActive ? $currentDirection : 'asc';
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
@endphp

<a href="{{ route($routeName, array_merge(request()->query(), ['sort' => $field, 'direction' => $nextDirection])) }}"
   class="flex items-center gap-1">
    {{ $label }}
    @if($isActive)
        @if($direction === 'asc')
            <i data-lucide="arrow-up" class="w-4 h-4"></i>
        @else
            <i data-lucide="arrow-down" class="w-4 h-4"></i>
        @endif
    @else
        <i data-lucide="arrow-up-down" class="w-4 h-4 text-gray-400"></i>
    @endif
</a>

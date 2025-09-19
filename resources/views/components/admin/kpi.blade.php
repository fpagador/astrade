@props(['value','label','color' => 'blue'])

@php
    $colorMap = [
        'blue' => '#BFDBFE',    // azul claro
        'indigo' => '#C7D2FE',  // índigo claro
        'green' => '#A7F3D0',   // verde claro
        'red' => '#FECACA',     // rojo claro
        'yellow' => '#FEF3C7',  // amarillo claro
    ];
    $backgroundColor = $colorMap[$color] ?? $color;
@endphp

<div class="flex flex-col items-center justify-center p-6 rounded-lg shadow-md text-gray-800"
     style="background-color: {{ $backgroundColor }};">
    <div class="text-3xl font-bold">{{ $value }}</div>
    <div class="mt-1 text-sm">{{ $label }}</div>
</div>

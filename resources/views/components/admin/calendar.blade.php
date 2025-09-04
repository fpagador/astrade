@props([
'mode' => 'holiday',
'year' => null,
'selectedDates' => [],
'holidayDates' => [],
'showCheckbox' => true,
'checkboxLabel' => 'Modo selección de festivos',
])

@php
    $year = $year ?? now()->year;
@endphp

<div class="mb-6 flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-2">
        <button type="button" id="btnPrev" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">&lt;</button>

        {{-- Month select --}}
        <select id="monthSelect" class="border rounded px-2 py-1 w-40">
            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $index => $month)
                <option value="{{ $index }}">{{ $month }}</option>
            @endforeach
        </select>

        <input
            type="number"
            id="year"
            class="border rounded px-2 py-1 w-20"
            min="2000" max="2100"
            value="{{ $year }}"
            required
            name="year"
        >

        <button type="button" id="btnNext" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">&gt;</button>
    </div>

    @if($showCheckbox ?? false)
        <div class="ml-auto flex items-center gap-2">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" id="enableHolidayMode" class="form-checkbox h-5 w-5">
                <span class="text-sm">{{ $checkboxLabel ?? 'Modo selección' }}</span>
            </label>
        </div>
    @endif
</div>

<div id="calendarGrid"
     class="grid grid-cols-7 gap-1 text-center mb-4"
     data-mode="{{ $mode }}"
     data-input="#selectedDates"
     data-holidays='@json($holidayDates)'>
</div>

{{-- Leyenda --}}
<div class="flex flex-wrap gap-4 mb-4 text-sm">
    <div class="flex items-center gap-2"><span class="w-4 h-4 bg-gray-100 border rounded inline-block"></span> Laborable</div>
    @if($mode === 'vacation')
        <div class="flex items-center gap-2"><span class="w-4 h-4 bg-green-500 border rounded inline-block"></span> Vacaciones seleccionadas</div>
        <div class="flex items-center gap-2"><span class="w-4 h-4 bg-yellow-200 border rounded inline-block"></span> Festivo</div>
    @else
        <div class="flex items-center gap-2"><span class="w-4 h-4 bg-yellow-200 border rounded inline-block"></span> Festivo seleccionado</div>
    @endif
    <div class="flex items-center gap-2"><span class="w-4 h-4 bg-gray-200 border rounded inline-block"></span> Fin de semana</div>
</div>

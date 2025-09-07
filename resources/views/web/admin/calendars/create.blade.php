@extends('layouts.app')

@section('title', 'Nueva Plantilla de Calendario')

@section('content')
    <div
        id="workCalendar-form-container"
        x-data="{ showClone: false, cloneSelected: false }"
        x-ref="container"
        class="max-w-5xl mx-auto bg-white p-6 rounded shadow"
        data-clone-url="{{ url('admin/calendars/__ID__/clone-data') }}"
    >
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Nueva Plantilla</h1>
            <button
                type="button"
                @click="showClone = !showClone"
                class="inline-block px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800"
            >
                Clonar plantilla existente
            </button>
        </div>

        <div x-show="showClone && !cloneSelected" x-cloak class="mb-6">
            <x-form.select
                id="clone_calendar_id"
                name="clone_calendar_id"
                label="Seleccionar plantilla para clonar"
                :options="$existingCalendars->pluck('name','id')->toArray()"
                placeholder="Elegir plantilla"
            />
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- MAIN FORM --}}
        <x-form.form-wrapper
            id="calendarTemplateForm"
            action="{{ route('admin.calendars.store') }}"
            method="POST"
        >
            @csrf

            {{-- NAME --}}
            <x-form.input label="Nombre de la plantilla" name="name" required />

            {{-- STATUS --}}
            <x-form.select
                name="status"
                label="Estado"
                :options="$statusOptions"
                :value="old('status', 'draft')"
            />
            <hr class="my-6">

            {{-- CALENDAR --}}
            <x-admin.calendar
                mode="holiday"
                :year="now()->year"
                :selectedDates="[]"
                :holidayDates="[]"
                :showCheckbox="true"
                checkboxLabel="Modo selección de festivos"
            />
            <input type="hidden" name="holidays_json" id="selectedDates" value='[]'>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-2 mt-4">
                <button type="submit" class="bg-indigo-900 text-white px-4 py-2 rounded">
                    Guardar
                </button>
                <a href="{{ url()->previous() }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">
                    Cancelar
                </a>
            </div>

        </x-form.form-wrapper>
    </div>
@endsection

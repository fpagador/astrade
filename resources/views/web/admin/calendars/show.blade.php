@extends('layouts.app')
@section('title', 'Ver Plantilla de Calendario')
@section('content')

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <div class="max-w-5xl mx-auto">
        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold mb-6">Ver {{ $template->name }}</h1>
                <div class="flex justify-end">
                    <a href="{{ route('admin.calendars.edit', $template) }}"
                       class="inline-flex items-center gap-x-2 mb-4 px-4 py-2 rounded button-success"
                       title="Editar">
                        <span>Editar Calendario laboral</span>
                        <i data-lucide="pencil" class="w-5 h-5 transition"></i>
                    </a>
                </div>
            </div>

            <hr class="border-gray-300 mb-6">

            {{-- Name --}}
            <x-form.input
                label="Nombre de la plantilla"
                name="name"
                :value="$template->name"
                disabled
            />

            {{-- Status --}}
            <x-form.select
                name="status"
                label="Estado"
                :options="$statusOptions"
                :selected="$template->status"
                disabled
            />

            {{-- Continuity calendar --}}
            @if($template->status !== \App\Enums\CalendarStatus::INACTIVE->value)
                <x-form.select
                    name="continuity_template_id"
                    label="Calendario de Continuidad"
                    :options="$futureCalendars->pluck('name','id')->toArray()"
                    :selected="$template->continuity_template_id ?? null"
                    placeholderOption="Ninguno"
                    disabled
                />
            @endif

            <hr class="my-6">

            {{-- Calendar component --}}
            <x-admin.calendar
                mode="holiday"
                :year="$template->year ?? now()->year"
                :selectedDates="$holidayDates"
                :holidayDates="$holidayDates"
                checkboxLabel="Modo selección de festivos"
                :showCheckbox="false"
                :yearEditable="false"
                :readonly="true"
            />

            <input type="hidden" name="holidays_json" id="selectedDates" value='@json($holidayDates)'>
        </div>

        {{-- Back button --}}
        <div class="mt-8">
            <a href="{{ route('admin.calendars.index') }}"
               class="inline-flex items-center px-4 py-2 button-extra rounded-md transition shadow">
                ← Volver a Calendarios laborales
            </a>
        </div>
    </div>


@endsection

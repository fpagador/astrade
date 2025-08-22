@extends('layouts.app')

@section('title', 'Editar Plantilla de Calendario')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Editar Plantilla</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <x-form.form-wrapper
        id="calendarTemplateForm"
        action="{{ route('admin.calendars.update', $template) }}"
        method="POST"
        class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')

        {{-- Name --}}
        <x-form.input label="Nombre de la plantilla" name="name" :value="old('name', $template->name)" required tooltip='Ej: Calendario general Región de Murcia' />

        {{-- Status --}}
        <x-form.select
            name="status"
            label="Estado"
            :options="['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo']"
            :selected="old('status', $template->status)"
        />

        <hr class="my-6">

        {{-- Calendar component --}}
        <x-admin.calendar
            mode="holiday"
            :year="$template->year ?? now()->year"
            :selectedDates="$template->days->where('day_type','holiday')->pluck('date')->toArray() ?? []"
            :showCheckbox="true"
            checkboxLabel="Modo selección de festivos"
        />

        {{-- Hidden field with selected holidays --}}
        <input
            type="hidden"
            name="holidays_json"
            id="selectedDates"
            value='@json($template->days->where("day_type", "holiday")->pluck("date")->toArray())'
        >

        {{-- Buttons --}}
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" data-open-modal="confirmModal_calendarTemplateForm" class="bg-indigo-900 text-white px-4 py-2 rounded">
                Guardar
            </button>
            <a href="{{ url()->previous() }}"
               class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">
                Cancelar
            </a>
        </div>

        {{-- Confirmation modal --}}
        <x-admin.confirm-dates-modal
            form-id="calendarTemplateForm"
            mode="holiday"
            modal-id="confirmModal_calendarTemplateForm"
        />

    </x-form.form-wrapper>
@endsection

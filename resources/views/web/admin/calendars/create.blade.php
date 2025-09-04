@extends('layouts.app')

@section('title', 'Nueva Plantilla de Calendario')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Nueva Plantilla</h1>

    <x-admin.alert-messages />

    <x-form.form-wrapper
        id="calendarTemplateForm"
        action="{{ route('admin.calendars.store') }}"
        method="POST"
        class="max-w-5xl mx-auto bg-white p-6 rounded shadow"
    >
        @csrf

        <x-form.input label="Nombre de la plantilla" name="name" required tooltip='Ej: Calendario general Región de Murcia' />
        <x-form.select
            name="status"
            label="Estado"
            :options="$statusOptions"
            :value="old('status', 'draft')"
        />

        <hr class="my-6">

        {{-- Calendar component --}}
        <x-admin.calendar
            mode="holiday"
            :year="now()->year"
            :selectedDates="[]"
            :showCheckbox="true"
            checkboxLabel="Modo selección de festivos"
        />

        {{-- Hidden field with selected holidays --}}
        <input type="hidden" name="holidays_json" id="selectedDates" data-dates="[]">

        {{-- Buttons --}}
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" data-open-modal="confirmModal_calendarTemplateFormCreate" class="bg-indigo-900 text-white px-4 py-2 rounded">
                Guardar
            </button>
            <a href="{{ url()->previous() }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">Cancelar</a>
        </div>

        {{-- Confirmation modal --}}
        <x-admin.confirm-dates-modal
            form-id="calendarTemplateForm"
            mode="holiday"
            modal-id="confirmModal_calendarTemplateFormCreate"
        />

    </x-form.form-wrapper>
@endsection

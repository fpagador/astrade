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
        <div x-data="{
            status: '{{ old('status', $template->status) }}',
            warningOpen: false,
            openConfirmation() {
                if (this.status === '{{ \App\Enums\CalendarStatus::INACTIVE->value }}' &&
                    {{ $template->users()->count() }} > 0)
                {
                    this.warningOpen = true;
                } else {
                    document.getElementById('calendarTemplateForm').submit();
                }
            },
            confirmWarning() {
                this.warningOpen = false;
                document.getElementById('calendarTemplateForm').submit();
            }
        }">
            <x-form.select
                name="status"
                label="Estado"
                :options="$statusOptions"
                :selected="old('status', $template->status)"
                x-model="status"
            />

            {{-- Continuity calendar --}}
            <template x-if="status !== '{{ \App\Enums\CalendarStatus::INACTIVE->value }}'">
                <x-form.select
                    name="continuity_template_id"
                    label="Calendario de Continuidad"
                    :options="$existingCalendars->pluck('name','id')->toArray()"
                    :selected="old('continuity_template_id', $template->continuity_template_id ?? null)"
                    placeholderOption="Ninguno"
                />
            </template>


            <hr class="my-6">

            {{-- Calendar component --}}
            <x-admin.calendar
                mode="holiday"
                :year="$template->year ?? now()->year"
                :selectedDates="$holidayDates"
                :holidayDates="$holidayDates"
                checkboxLabel="Modo selección de festivos"
            />

        {{-- Hidden field with selected holidays --}}
            <input
                type="hidden"
                name="holidays_json"
                id="selectedDates"
                value='@json($holidayDates)'
            >

        {{-- Buttons --}}
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" @click="openConfirmation()" class="bg-indigo-900 text-white px-4 py-2 rounded">
                    Guardar
                </button>

                <a href="{{ route('admin.calendars.index') }}"
                   class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">
                    Cancelar
                </a>

                {{-- Warning modal for assigned users --}}
                <x-admin.modal
                    title="Advertencia"
                    message="Existen usuarios actualmente asignados a este calendario laboral. Al inactivarlo, estos usuarios quedarán sin un calendario laboral asignado. ¿Desea proceder?"
                    confirm-label="Sí"
                    cancel-label="Cancelar"
                    confirm-action="confirmWarning()"
                    open="warningOpen"
                />

                {{-- Day confirmation modal --}}
                <x-admin.confirm-dates-modal
                    form-id="calendarTemplateForm"
                    mode="holiday"
                    modal-id="confirmModal_calendarTemplateForm"
                />
            </div>
        </div>

    </x-form.form-wrapper>
@endsection

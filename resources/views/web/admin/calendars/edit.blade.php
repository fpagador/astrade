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
        class="max-w-5xl mx-auto bg-white p-6 rounded shadow"
        x-data="calendarForm()"
    >
        @csrf
        @method('PUT')

        {{-- Template name --}}
        <x-form.input
            label="Nombre de la plantilla"
            name="name"
            :value="old('name', $template->name)"
            required
            tooltip="Ej: Calendario general Región de Murcia"
        />

        {{-- Status --}}
        <x-form.select
            name="status"
            label="Estado"
            x-data="{ isOldYear: {{ $template->year < now()->year ? 'true' : 'false' }} }"
            x-init="if (isOldYear) $el.querySelector('option[value=active]').disabled = true"
            :options="$statusOptions"
            :selected="old('status', $template->status)"
            x-model="status"
        />

        {{-- Continuity calendar --}}
        <template x-if="status !== '{{ \App\Enums\CalendarStatus::INACTIVE->value }}'">
            <x-form.select
                name="continuity_template_id"
                label="Calendario de Continuidad"
                :options="$futureCalendars->pluck('name','id')->toArray()"
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
            :yearEditable="false"
        />

        <input type="hidden" name="holidays_json" id="selectedDates" value='@json($holidayDates)'>

        {{-- Buttons --}}
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="bg-indigo-900 text-white px-4 py-2 rounded"
                    @click="openConfirmDaysModal()">
                Guardar
            </button>
            <a href="{{ route('admin.calendars.index') }}"
               class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">
                Cancelar
            </a>
        </div>

        {{-- Modal de confirmación de cambio de estado --}}
        <x-admin.modal
            title="Confirmación de cambio de estado"
            confirm-label="Confirmar"
            cancel-label="Cancelar"
            confirm-action="confirmStateChange()"
            open="stateConfirmOpen"
        >
            <p x-text="confirmMessage"></p>
        </x-admin.modal>

        {{-- Day confirmation modal --}}
        <div x-show="confirmDaysModalOpen"
             class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
             x-transition>
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6">
                <h3 class="text-xl font-semibold mb-4">Confirmar festivos</h3>
                <p class="text-sm text-gray-600 mb-3">Has marcado los siguientes días como festivos:</p>
                <ul class="list-disc list-inside text-sm max-h-60 overflow-auto mb-6">
                    <template x-for="d in dateList" :key="d">
                        <li x-text="d"></li>
                    </template>
                </ul>
                <div class="flex justify-end gap-2">
                    <!-- Solo cierra el modal, no hace submit -->
                    <button type="button" @click="confirmDaysModalOpen = false"
                            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                        Seguir editando
                    </button>

                    <!-- Evalúa advertencia antes de submit -->
                    <button type="button" @click="confirmAndCheckWarning()"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Confirmar y guardar
                    </button>
                </div>
            </div>
        </div>

    </x-form.form-wrapper>

    <script>
        window.calendarData = {
            oldStatus: '{{ old('status', $template->status) }}',
            templateStatus: '{{ \App\Enums\CalendarStatus::INACTIVE->value }}',
            userCount: {{ $template->users()->count() }},
            selectedDates: @json($holidayDates)
        };
    </script>
@endsection

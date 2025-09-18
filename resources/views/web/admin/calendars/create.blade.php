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

        {{-- ALERTS --}}
        <x-admin.alert-messages />

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
            <div x-data="{ status: '{{ old('status', 'draft') }}' }">
                <x-form.select
                    name="status"
                    label="Estado"
                    :options="$statusOptions"
                    :value="old('status', 'draft')"
                    x-model="status"
                    tooltip="- Borrador: calendario no terminado. - Activo: calendario activo actualmente. - Inactivo: calendario inactivo."
                />

                {{-- CONTINUITY SELECT --}}
                <template x-if="status !== '{{ \App\Enums\CalendarStatus::INACTIVE->value }}'">
                    <x-form.select
                        name="continuity_template_id"
                        label="Calendario de Continuidad"
                        :options="$existingCalendars->pluck('name','id')->toArray()"
                        :selected="old('continuity_template_id', null)"
                        placeholderOption="Ninguno"
                        tooltip="Cada año se inactivará el calendario anterior y se activará el calendario que esté configurado como continuidad."
                    />
                </template>
            </div>

            <div x-data="userSelector()" x-init="init(window.appCompanies, window.appUsers)"  class="mb-6">

                {{-- SELECT COMPANY --}}
                <x-form.select
                    name="company_id"
                    label="Asignar usuarios a la plantilla por Empresa"
                    :options="$companies->pluck('name','id')->toArray()"
                    placeholderOption="Seleccionar empresa..."
                    @change="loadCompanyUsers()"
                    x-model="selectedCompany"
                />

                {{-- SELECTED USERS --}}
                <div class="border p-2 rounded min-h-[50px] mb-2 flex flex-wrap gap-2">
                    <template x-for="user in selectedUsers" :key="user.id">
                        <div class="flex items-center rounded-full px-3 py-1 text-sm"
                             :class="companyColor(user.company_id)">
                            <span x-text="user.name + ' ' + user.surname"></span>
                            <button type="button" class="ml-2 font-bold text-red-500" @click="removeUser(user)">×</button>
                        </div>
                    </template>
                    <span x-show="selectedUsers.length === 0" class="text-gray-400 text-sm">No hay usuarios seleccionados</span>
                </div>

                {{-- INPUT TO SEARCH FOR USERS--}}
                <input type="text" x-model="searchQuery" @input="searchUsers" placeholder="Buscar usuarios por nombre..." class="border rounded px-2 py-1 w-full mb-2">

                {{-- SEARCH RESULTS --}}
                <div class="rounded max-h-40 overflow-auto">
                    <template x-for="user in searchResults" :key="user.id">
                        <div class="p-1 hover:bg-gray-200 rounded cursor-pointer" @click="addUser(user)">
                            <span x-text="user.name + ' ' + user.surname"></span>
                            <span class="ml-2 text-gray-500 text-xs" x-text="getCompanyName(user.company_id)"></span>
                        </div>
                    </template>

                </div>

                <template x-for="user in selectedUsers" :key="user.id">
                    <input type="hidden" name="assigned_users[]" :value="user.id">
                </template>
            </div>

            <hr class="my-6">

            {{-- CALENDAR --}}
            <x-admin.calendar
                mode="holiday"
                :year="now()->year"
                :selectedDates="[]"
                :holidayDates="[]"
                checkboxLabel="Modo selección de festivos"
            />
            <input type="hidden" name="holidays_json" id="selectedDates" value='[]'>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-2 mt-4">
                <button type="submit" class="bg-indigo-900 text-white px-4 py-2 rounded">
                    Guardar
                </button>
                <a href="{{ route('admin.calendars.index') }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">
                    Cancelar
                </a>
            </div>

        </x-form.form-wrapper>
    </div>
    <script>
        window.appCompanies = @json($companies);
        window.appUsers = @json($users);
    </script>
@endsection

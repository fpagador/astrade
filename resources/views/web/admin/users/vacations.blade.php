@extends('layouts.app')

@section('title', "Vacaciones de $user->name $user->surname")

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-semibold">Vacaciones de {{ $user->name }} {{ $user->surname }}</h1>
    </div>

    <hr class="border-gray-300 mb-6">
    <x-admin.alert-messages />

    <x-form.form-wrapper
        id="vacationForm"
        action="{{ route('admin.users.vacations.store', $user->id) }}"
        method="POST"
        class="max-w-5xl mx-auto bg-white p-6 rounded shadow"
    >
        @csrf

        {{-- Calendar component --}}
        <x-admin.calendar
            mode="vacation"
            :year="now()->year"
            :selectedDates="$vacationDates"
            :showCheckbox="true"
            checkboxLabel="Modo selección de vacaciones"
            :showYearInput="true"
        />

        <input type="hidden" name="dates_json" id="selectedDates" value="{{ json_encode($vacationDates) }}">

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" data-open-modal="confirmModal_vacationForm" class="bg-indigo-900 text-white px-4 py-2 rounded">
                Guardar vacaciones
            </button>
            <a href="{{ url()->previous() }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 text-center">Cancelar</a>
        </div>

        {{-- Confirmation modal --}}
        <x-admin.confirm-dates-modal
            form-id="vacationForm"
            mode="vacation"
            modal-id="confirmModal_vacationForm"
        />

    </x-form.form-wrapper>

    {{-- BACK BUTTON --}}
    <x-admin.back-to-users-button type="mobile" />
@endsection

@extends('layouts.app')

@section('title', 'Nueva Ubicación')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Nueva Empresa</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FORM --}}
    <x-form.form-wrapper action="{{ route('admin.companies.store') }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <x-form.input label="Nombre" name="name" required />
        <x-form.input label="Dirección" name="address" />
        <x-form.textarea label="Descripción" name="description" />

        {{-- Buttons --}}
        <x-form.button-group submit-text="Crear" />
    </x-form.form-wrapper>
@endsection

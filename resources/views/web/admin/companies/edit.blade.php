@extends('layouts.app')

@section('title', 'Editar Ubicación')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Editar Empresa</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FORM --}}
    <x-form.form-wrapper action="{{ route('admin.companies.update', $company) }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @method('PUT')

        <x-form.input label="Nombre" name="name" :value="old('name', $company->name)" required />
        <x-form.input label="Dirección" name="address" :value="old('address', $company->address)" />
        <x-form.textarea label="Descripción" name="description">{{ old('description', $company->description) }}</x-form.textarea>

        {{-- Buttons --}}
        <x-form.button-group submit-text="Actualizar" />
    </x-form.form-wrapper>
@endsection

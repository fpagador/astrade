@extends('layouts.app')

@section('title', 'Editar Ubicación')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Editar Empresa</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <hr class="border-gray-300 mb-6">

    {{-- FORM --}}
    <x-form.form-wrapper action="{{ route('admin.companies.update', $company) }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @method('PUT')

        <x-form.input label="Nombre" name="name" :value="old('name', $company->name)" required />
        <x-form.input label="Dirección" name="address" :value="old('address', $company->address)" />
        <x-form.textarea label="Descripción" name="description">{{ old('description', $company->description) }}</x-form.textarea>

        {{-- PHONES --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Teléfono <span class="text-red-500">*</span></h2>
            <div id="phones-container" class="space-y-4">
                @foreach(old('phones', $company->phones ?? [0]) as $index => $phone)
                    <div class="phone-card relative bg-indigo-100 border border-indigo-300 rounded p-4 flex flex-col">
                        @if(count(old('phones', $company->phones ?? [0])) > 1)
                            <button type="button"
                                    class="remove-phone absolute top-1 right-1 text-red-600 hover:text-red-800 font-bold text-xl leading-none"
                                    title="Eliminar teléfono">&times;</button>
                        @endif

                        <x-form.input
                            label="Nombre"
                            name="phones[{{ $index }}][name]"
                            :value="old('phones.'.$index.'.name', is_object($phone) ? $phone->name : ($phone['name'] ?? ''))"
                        />

                        <x-form.input
                            label="Número"
                            name="phones[{{ $index }}][phone_number]"
                            :value="old('phones.'.$index.'.phone_number', is_object($phone) ? $phone->phone_number : ($phone['phone_number'] ?? ''))"
                            type="number"
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,9)"
                            tooltip="Debe contener 9 dígitos."
                        />
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-phone-button" class="mt-4 px-4 py-2 button-extra rounded ">
                Añadir teléfono
            </button>
        </div>

        {{-- Buttons --}}
        <x-form.button-group submit-text="Actualizar" :cancelRoute="route('admin.companies.index')"/>
    </x-form.form-wrapper>
@endsection

@extends('layouts.app')

@section('title', 'Editar Ubicación')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Editar Ubicación</h1>

    {{-- ALERTS --}}
    @if(session('error'))
        <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ session('error') }}</strong>
        </div>
    @endif

    @if ($errors->has('general'))
        <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ $errors->first('general') }}</strong>
        </div>
    @endif

    <x-form.form-wrapper action="{{ route('admin.locations.update', $location) }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @method('PUT')

        <x-form.input label="Nombre" name="name" :value="old('name', $location->name)" required />
        <x-form.input label="Dirección" name="address" :value="old('address', $location->address)" />
        <x-form.textarea label="Descripción" name="description">{{ old('description', $location->description) }}</x-form.textarea>

        <div class="flex space-x-4 mt-6">
            <button type="submit" class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 flex-1">Actualizar</button>
            <a href="{{ route('admin.locations.index') }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 flex-1 text-center">Cancelar</a>
        </div>
    </x-form.form-wrapper>
@endsection

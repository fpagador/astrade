@extends('layouts.app')

@section('title', 'Cambiar Contraseña')

@section('content')
    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <div class="flex justify-center items-center min-h-[60vh]">
        <div class="w-full max-w-md bg-white p-6 rounded shadow-md">
            <h1 class="text-2xl font-semibold mb-6 text-center">
                Cambiar contraseña de {{ $user->name }} {{ $user->surname }}
            </h1>

            <x-form.form-wrapper action="{{ route('admin.users.update-password', $user->id) }}" method="PUT" class="max-w-lg mx-auto bg-white p-6 rounded shadow">

                <!-- PASSWORD -->
                <x-form.input
                    label="Contraseña"
                    name="password"
                    type="password"
                    required tooltip="La contraseña debe tener al menos 8 caracteres, incluidas mayúsculas, minúsculas, números y caracteres especiales."
                />

                <!-- CONFIRM PASSWORD  -->
                <x-form.input label="Confirmar contraseña" name="password_confirmation" type="password" required />

                {{-- BUTTONS --}}
                <x-form.button-group
                    submit-text="Actualizar"
                    :cancelRoute="route('admin.users.index', ['type' => \App\Enums\UserTypeEnum::mobile->value])"
                />

            </x-form.form-wrapper>
        </div>
    </div>

    {{-- BACK BUTTON --}}
    <x-admin.back-to-users-button :type="\App\Enums\UserTypeEnum::mobile->value" />
@endsection

@extends('layouts.app')

@section('title', 'Cambiar Contraseña')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold mb-6 text-center">
            Cambiar contraseña de {{ $user->name }} {{ $user->surname }}
        </h1>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <div class="flex justify-center items-start min-h-[60vh]">
        <div class="w-full max-w-md flex flex-col">
            <div class="bg-white p-6 rounded shadow-md">
                <x-form.form-wrapper action="{{ route('admin.users.update-password', $user->id) }}" method="PUT" class="max-w-lg mx-auto bg-white p-6 rounded shadow">

                    <!-- PASSWORD -->
                    <div class="relative">
                        <x-form.input
                            label="Contraseña"
                            name="password"
                            type="password"
                            required tooltip="La contraseña debe tener al menos 6 caracteres, incluidas mayúsculas, minúsculas y números"
                        />
                        <button type="button"
                                class="absolute right-3 top-9 text-gray-500 hover:text-gray-700 toggle-password"
                                data-target="password">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- CONFIRM PASSWORD  -->
                    <div class="relative">
                        <x-form.input
                            label="Confirmar contraseña"
                            name="password_confirmation"
                            type="password"
                            required
                        />
                        <button type="button"
                                class="absolute right-3 top-9 text-gray-500 hover:text-gray-700 toggle-password"
                                data-target="password_confirmation">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- BUTTONS --}}
                    <x-form.button-group
                        submit-text="Actualizar"
                        :cancelRoute="route('admin.users.index', ['type' => \App\Enums\UserTypeEnum::MOBILE->value])"
                    />

                </x-form.form-wrapper>
            </div>

            {{-- BACK BUTTON --}}
            <div class="mt-2 self-start">
                <x-admin.back-to-users-button :type="\App\Enums\UserTypeEnum::MOBILE->value" :back_url="$backUrl" />
            </div>
        </div>
    </div>
    <script>
        window.routes = {
            validatePassword: "{{ route('admin.users.validate-password') }}"
        };
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Nuevo Usuario</h1>

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

    <x-form.form-wrapper action="{{ route('admin.users.store') }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <input type="hidden" name="type" value="{{ request('type', 'mobile') }}">

        <x-form.input label="Nombre" name="name" required />
        <x-form.input label="Apellido" name="surname" required />

        <!-- DNI -->
        <label for="dni" class="block font-medium mb-1">
            DNI *
            <x-tooltip-info title="Información sobre el DNI" text="Debe contener 8 números y 1 letra (ejemplo: 12345678A)." />
        </label>
        <x-form.input name="dni" required />

        <!-- email-->
        <label for="email" class="block font-medium mb-1">
            Email *
            <x-tooltip-info title="Información sobre el Email" text="Debe contener un '@' y un '.' para ser válido." />
        </label>
        <x-form.input name="email" type="email" required />

        <!-- Phone -->
        <label for="phone" class="block font-medium mb-1">
            Teléfono *
            <x-tooltip-info title="Información sobre el Teléfono" text="Debe contener al menos 9 números." />
        </label>
        <x-form.input name="phone" required />

        <x-form.input label="Usuario" name="username" required />

        <!-- Rol -->
        <div class="mb-4">
            <label for="role_id" class="block font-medium mb-1">Rol <span class="text-red-600">*</span></label>
            <select name="role_id" id="role_id" required {{ request('role') ? 'disabled' : '' }} class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @foreach($assignableRoles as $role)
                    <option value="{{ $role->id }}"
                            data-role-name="{{ $role->role_name }}"
                        {{ old('role_id', $defaultRole ?? ($user->role_id ?? '')) == $role->id ? 'selected' : '' }}>
                        {{ \App\Enums\RoleEnum::from($role->role_name)->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Password -->
        <label for="password" class="block font-medium mb-1">
            Contraseña *
            <x-tooltip-info title="Información sobre la contraseña" text="Mínimo 8 caracteres. Recomendado: letras y números." />
        </label>
        <x-form.input name="password" type="password" required />

        <!-- Password Confirmation -->
        <x-form.input label="Confirmar contraseña" name="password_confirmation" type="password" required />

        <!-- Photo -->
        <label for="password" class="block font-medium mb-1">
            Foto *
            <x-tooltip-info title="Información sobre la foto" text="Archivo de imagen máximo 2MB" />
        </label>
        <x-form.input name="photo" type="file" />

        {{-- Fields that are only displayed when creating a user of type "user" --}}
        <!-- Company -->
        <x-form.select
            name="company_id"
            label="Empresa"
            :options="$companies->pluck('name', 'id')->prepend('-- Selecciona una empresa --', '')->toArray()"
            required
        />

        <!-- Work schedule -->
        <x-form.textarea
            name="work_schedule"
            label="Horario de trabajo"
            class="user-only"
        />

        <!-- Contract Type -->
        <x-form.select
            name="contract_type"
            label="Tipo de contrato"
            :options="[
                '' => '-- Selecciona un tipo --',
                'Temporal' => 'Temporal',
                'Indefinido' => 'Indefinido'
            ]"
        />

        <!-- Contract Start Date -->
        <x-form.input label="Fecha de inicio de contrato" name="contract_start_date" type="date" value="{{ old('contract_start_date') }}" />

        <!-- Checkbox: Can receive notifications -->
        <x-form.checkbox
            name="can_receive_notifications"
            label="Puede recibir notificaciones"
        />

        <!-- Notification type -->
        <x-form.select
            name="notification_type"
            label="Tipo de notificación"
            :options="[
                'none' => 'Ninguna',
                'visual' => 'Visual',
                'visual_audio' => 'Visual y Audio'
            ]"
        />

        {{-- Buttons --}}
        <x-form.button-group submit-text="Crear" />

    </x-form.form-wrapper>
@endsection

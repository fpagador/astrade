@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Edit User</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <x-form.form-wrapper action="{{ route('admin.users.update', $user->id) }}" method="PUT" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <input type="hidden" name="id" value="{{ $user->id }}">

        <!-- Name -->
        <x-form.input label="Nombre" name="name" required value="{{ old('name', $user->name) }}" />

        <!-- Surname -->
        <x-form.input label="Apellidos" name="surname" required value="{{ old('surname', $user->surname) }}" />

        <!-- DNI -->
        <x-form.input
            label="DNI"
            name="dni"
            required
            value="{{ old('dni', $user->dni) }}"
            tooltip="Debe contener 8 números y 1 letra (ejemplo: 12345678A)."
        />

        <!-- email-->
        <x-form.input
            label="Email"
            name="email"
            required
            value="{{ old('email', $user->email) }}"
            tooltip="Debe contener un '@' y un '.' para ser válido."
        />

        <!-- Phone -->
        <x-form.input
            label="Teléfono"
            name="phone"
            required
            value="{{ old('phone', $user->phone) }}"
            tooltip="Debe contener al menos 9 números."
        />

        <!-- Username -->
        <x-form.input label="Username" name="username" required value="{{ old('username', $user->username) }}" />

        <!-- Role -->
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

        <!-- Photo -->
        <div class="gap-4">
            <x-form.file label="Foto" name="photo" accept="image/*" />
            @if ($user->photo)
                <div class="mb-4">
                    <img src="{{ asset('storage/' . $user->photo) }}"
                         @click="$dispatch('open-image', '{{ asset('storage/' . $user->photo) }}')"
                         class="h-20 w-20 object-contain rounded cursor-pointer transition hover:brightness-110"
                         title="Ver foto actual">
                </div>
            @endif
        </div>

        {{-- Fields that are only displayed when creating a user of type "user" --}}

        <!-- Company -->
        <x-form.select
            label="Empresa"
            name="company_id"
            class="user-only"
            :options="$companies->pluck('name', 'id')->prepend('-- Selecciona una empresa --', '')->toArray()"
            :selected="old('company_id', $user->company_id)"
        />

        <!-- Work schedule -->
        <x-form.textarea
            label="Horario de trabajo"
            name="work_schedule"
            class="user-only"
        >{{ old('work_schedule', $user->work_schedule) }}</x-form.textarea>

        <!-- Contract Type -->
        <x-form.select
            label="Tipo de contrato"
            name="contract_type"
            class="user-only"
            :options="[
                '' => '-- Selecciona un tipo --',
                'Temporal' => 'Temporal',
                'Indefinido' => 'Indefinido',
            ]"
            :selected="old('contract_type', $user->contract_type)"
        />

        <!-- Contract Start Date -->
        <x-form.input
            label="Fecha de inicio de contrato"
            name="contract_start_date"
            type="date"
            class="user-only"
            value="{{ old('contract_start_date', optional($user?->contract_start_date)->format('Y-m-d')) }}"
        />

        <!-- Checkbox: Can receive notifications -->
        <x-form.checkbox
            name="can_receive_notifications"
            label="Puede recibir notificaciones"
            class="user-only"
            :checked="old('can_receive_notifications', $user->can_receive_notifications)"
        />

        <!-- Notification type -->
        <x-form.select
            label="Tipo de notificación"
            name="notification_type"
            class="user-only"
            :options="[
                'none' => 'None',
                'visual' => 'Visual',
                'visual_audio' => 'Visual y Audio',
            ]"
            :selected="old('notification_type', $user->notification_type)"
        />

        {{-- Buttons --}}
        <x-form.button-group submit-text="Actualizar" />
    </x-form.form-wrapper>

    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

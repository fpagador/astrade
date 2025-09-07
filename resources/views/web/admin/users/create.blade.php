@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Nuevo Usuario</h1>

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <x-form.form-wrapper action="{{ route('admin.users.store') }}" method="POST" class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        @php
            $type = request('type', '\App\Enums\UserTypeEnum::MANAGEMENT->value');
        @endphp
        <input type="hidden" name="type" value="{{ $type}}">

        {{-- Grid for main fields --}}
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Name -->
            <x-form.input
                label="Nombre"
                name="name"
                required
                value="{{ old('name') }}"
            />
            <!-- Surname -->
            <x-form.input
                label="Apellido"
                name="surname"
                required
                value="{{ old('surname') }}"
            />
            <!-- DNI -->
            <x-form.input
                label="DNI/NIE"
                name="dni"
                required
                value="{{ old('dni') }}"
                tooltip="DNI: Debe contener 8 números y 1 letra (ejemplo: 12345678A). NIE: Debe contener 1 letra inicial (XYZ), 7 números y 1 letra de control (ejemplo: X1234567B)."
            />
            <!-- Email -->
            <x-form.input
                label="Email"
                name="email"
                required
                value="{{ old('email') }}"
                tooltip="Debe contener un @ y un . para ser válido."
            />
            <!-- Password -->
            <div class="relative">
                <x-form.input
                    label="Contraseña"
                    name="password"
                    type="password"
                    required
                    tooltip="La contraseña debe tener al menos 8 caracteres, incluidas mayúsculas, minúsculas, números y caracteres especiales."
                />
                <button type="button"
                        class="absolute right-3 top-9 text-gray-500 hover:text-gray-700 toggle-password"
                        data-target="password">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Password Confirmation -->
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

            <!-- Phone -->
            <x-form.input
                label="Teléfono"
                name="phone"
                required
                value="{{ old('phone') }}"
                type="number"
                inputmode="numeric"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,9)"
                tooltip="Debe contener 9 dígitos."
            />
            <!-- Username -->
            <x-form.input
                label="Usuario"
                name="username"
                required
                value="{{ old('username') }}"
            />
            <!-- Role -->
            <div class="mb-4">
                <label for="role_id" class="block font-medium mb-1">Rol <span class="text-red-600">*</span></label>
                <select name="role_id" id="role_id" required {{ request('role') ? 'disabled' : '' }} class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @foreach($assignableRoles as $role)
                        <option value="{{ $role->id }}" data-role-name="{{ $role->role_name }}" {{ old('role_id', $defaultRole ?? ($user->role_id ?? '')) == $role->id ? 'selected' : '' }}>
                            {{ \App\Enums\RoleEnum::from($role->role_name)->label() }}
                        </option>
                    @endforeach
                </select>
                @if (request('role'))
                    <input type="hidden" name="role_id" value="{{ $defaultRole ?? ($user->role_id ?? '') }}">
                @endif
            </div>

            <!-- Photo -->
            <div x-data="imageSelector()" class="mb-4">
                <label class="block font-medium mb-1" for="photo">Foto</label>

                <div class="flex items-center space-x-2 mb-4">
                    <!-- Select file button -->
                    <label for="photo" class="cursor-pointer bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 transition">
                        Seleccionar archivo
                    </label>
                    <span x-text="filename" class="text-gray-700"></span>
                </div>

                <!-- Miniature -->
                <div class="mb-4" x-show="confirmedImageUrl" x-cloak>
                    <img
                        :src="confirmedImageUrl"
                        alt="Preview"
                        class="h-32 w-32 object-cover rounded cursor-pointer hover:brightness-110 transition"
                        @click="openLarge(confirmedImageUrl)"
                    />
                </div>
                <input type="file" name="photo" id="photo" class="hidden" @change="previewImage($event)">

                {{-- Confirmation modal --}}
                <x-admin.image-confirmation-modal />
            </div>
        </div>

        {{-- Grid for user-only fields (mobile users) --}}
        <div class="grid gap-4 md:grid-cols-2 mt-6 user-only">
            <!-- Company -->
            <x-form.select
                name="company_id"
                label="Empresa"
                :options="$companies->pluck('name', 'id')->prepend('-- Selecciona una empresa --', '')->toArray()"
                :selected="old('company_id')"
            />
            <!-- Work Calendar -->
            <x-form.select
                name="work_calendar_template_id"
                label="Calendario Laboral"
                :options="$workCalendarTemplate->pluck('name', 'id')->prepend('-- Selecciona un calendario laboral --', '')->toArray()"
                :selected="old('work_calendar_template_id')"
            />
            <!-- Work schedule -->
            <x-form.textarea
                name="work_schedule"
                label="Horario de trabajo"
            />
            <!-- Contract Type -->
            <x-form.select
                name="contract_type"
                label="Tipo de contrato"
                :options="$contractOptions"
                :selected="old('contract_type')"
            />
            <!-- Contract Start Date -->
            <x-form.input
                label="Fecha de inicio de contrato"
                name="contract_start_date"
                type="date"
                value="{{ old('contract_start_date') }}"
            />
            <!-- Notification type -->
            <x-form.select
                name="notification_type"
                label="Tipo de notificación"
                :options="$notificationTypeOptions"
                value="{{ old('notification_type') }}"
            />
        </div>

        {{-- Buttons --}}
        <div class="mt-6">
            <x-form.button-group submit-text="Crear" :cancelRoute="route('admin.users.index', ['type' => $type])" />
        </div>
    </x-form.form-wrapper>
    <script>
        window.routes = {
            validateField: "{{ route('admin.users.validate-field') }}"
        };
    </script>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold mb-6">Editar Usuario {{ $user->name }} {{ $user->surname }}</h1>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <x-form.form-wrapper action="{{ route('admin.users.update', $user->id) }}" method="POST" class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        @method('PUT')
        <input type="hidden" name="id" value="{{ $user->id }}">

        {{-- Grid for main fields --}}
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Name -->
            <x-form.input
                label="Nombre"
                name="name"
                required
                value="{{ old('name', $user->name) }}"
            />
            <!-- Surname -->
            <x-form.input
                label="Apellidos"
                name="surname"
                required
                value="{{ old('surname', $user->surname) }}"
            />
            <!-- DNI -->
            <x-form.input
                label="DNI/NIE"
                name="dni"
                required
                value="{{ old('dni', $user->dni) }}"
                tooltip="DNI: Debe contener 8 números y 1 letra (ejemplo: 12345678A). NIE: Debe contener 1 letra inicial (XYZ), 7 números y 1 letra de control (ejemplo: X1234567B)."
            />
            <!-- Email -->
            <x-form.input
                label="Email"
                name="email"
                required
                value="{{ old('email', $user->email) }}"
                tooltip="Debe contener un @ y un . para ser válido."
            />
            <!-- Phone -->
            <x-form.input
                label="Teléfono"
                name="phone"
                required
                value="{{ old('phone', $user->phone) }}"
                type="number"
                inputmode="numeric"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,9)"
                tooltip="Debe contener 9 dígitos."
            />
            <!-- Username -->
            <x-form.input
                label="Username"
                name="username"
                required
                value="{{ old('username', $user->username) }}"
            />
            <!-- Role -->
            <div class="mb-4">
                <label for="role_id" class="block font-medium mb-1">Rol <span class="text-red-600">*</span></label>
                <select name="role_id" id="role_id" required {{ request('role') ? 'disabled' : '' }} class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @foreach($assignableRoles as $role)
                        <option value="{{ $role->id }}" data-role-name="{{ $role->role_name }}" {{ old('role_id', $defaultRole ?? $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ \App\Enums\RoleEnum::from($role->role_name)->label() }}
                        </option>
                    @endforeach
                </select>
                @if (request('role'))
                    <input type="hidden" name="role_id" value="{{ $defaultRole ?? $user->role_id }}">
                @endif
            </div>

            {{-- Photo --}}
            <!-- Hidden inputs to persist data -->
            <input type="hidden" name="photo_base64" id="photo_base64" value="{{ old('photo_base64') }}">
            <input type="hidden" name="photo_name" id="photo_name" value="{{ old('photo_name') }}">
            <div x-data="imageSelector()" class="mb-4">
                <label class="block font-medium mb-1" for="photo">Foto</label>
                <div class="flex items-center space-x-2 mb-4">
                    <!-- Select file button -->
                    <label for="photo" class="cursor-pointer button-success px-4 py-2 rounded transition">
                        Seleccionar archivo
                    </label>
                    <span x-text="filename" class="text-gray-700"></span>
                </div>

                {{-- Show current photo --}}
                @if ($user->photo)
                    <div class="mb-4" x-show="!confirmedImageUrl" x-cloak>
                        <img
                            src="{{ asset('storage/' . $user->photo) }}"
                            alt="Preview"
                            @click="$dispatch('open-image', { src: '{{ asset('storage/' . $user->photo) }}' })"
                            class="h-32 w-32 object-contain rounded cursor-pointer transition hover:brightness-110"
                            title="Ver foto actual">
                    </div>
                @endif

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
                :selected="old('company_id', $user->company_id)"
            />
            <!-- Work Calendar -->
            <x-form.select
                name="work_calendar_template_id"
                label="Calendario laboral"
                :options="$workCalendarTemplate->pluck('name', 'id')->prepend('-- Selecciona una plantilla --', '')->toArray()"
                :selected="old('work_calendar_template_id', $user->work_calendar_template_id)"
            />
            <!-- Work schedule -->
            <x-form.textarea
                name="work_schedule"
                label="Horario de trabajo">{{ old('work_schedule', $user->work_schedule) }}
            </x-form.textarea>
            <!-- Contract Type -->
            <x-form.select
                name="contract_type"
                label="Tipo de contrato"
                :options="$contractOptions"
                :selected="old('contract_type', $user->contract_type)"
            />
            <!-- Contract Start Date -->
            <x-form.input
                name="contract_start_date"
                label="Fecha de inicio de contrato"
                type="text"
                value="{{ old('contract_start_date', optional($user->contract_start_date)->format('Y-m-d')) }}"
                placeholder="dd/mm/yy"
                data-flatpickr
            />
            <!-- Notification type -->
            <x-form.select
                name="notification_type"
                label="Tipo de notificación"
                :options="$notificationTypeOptions"
                :selected="old('notification_type', $user->notification_type)"
            />
        </div>

        {{-- Buttons --}}
        <div class="mt-6">
            @php $type = request('type', '\App\Enums\UserTypeEnum::MANAGEMENT->value'); @endphp
            <x-form.button-group submit-text="Actualizar" :cancelRoute="route('admin.users.index', ['type' => $type])" />
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

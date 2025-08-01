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
        <x-form.input label="Nombre" name="name" required />
        <x-form.input label="Apellido" name="surname" required />
        <x-form.input label="DNI" name="dni" required />
        <x-form.input label="Email" name="email" type="email" required />
        <x-form.input label="Teléfono" name="phone" required />
        <x-form.input label="Usuario" name="username" required />
        <div>
            <label for="role_id">Rol</label>
            <select name="role_id" id="role_id" class="form-select">
                @foreach($assignableRoles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                        {{ \App\Enums\RoleEnum::from($role->role_name)->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-form.input label="Contraseña" name="password" type="password" required />
        <x-form.input label="Confirmar contraseña" name="password_confirmation" type="password" required />

        <x-form.input label="Foto" name="photo" type="file" />

        <x-form.input label="Horario de trabajo" name="work_schedule" />
        <x-form.input label="Tipo de contrato" name="contract_type" />
        <x-form.input label="Fecha de inicio de contrato" name="contract_start_date" type="date" />

        <div class="mb-4">
            <label for="notification_type" class="block font-medium mb-1">Tipo de notificación</label>
            <select name="notification_type" id="notification_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="none" {{ old('notification_type') == 'none' ? 'selected' : '' }}>Ninguna</option>
                <option value="visual" {{ old('notification_type') == 'visual' ? 'selected' : '' }}>Visual</option>
                <option value="visual_audio" {{ old('notification_type') == 'visual_audio' ? 'selected' : '' }}>Visual y Audio</option>
            </select>
            @error('notification_type')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4 flex items-center space-x-2">
            <input type="checkbox" name="can_receive_notifications" id="can_receive_notifications" value="1" {{ old('can_receive_notifications') ? 'checked' : '' }} class="rounded focus:ring-indigo-400" />
            <label for="can_receive_notifications" class="font-medium">Puede recibir notificaciones</label>
        </div>

        <div class="flex space-x-4 mt-6">
            <button type="submit" class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 flex-1">Crear</button>
            <a href="{{ route('admin.users.index') }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 flex-1 text-center">Cancelar</a>
        </div>
    </x-form.form-wrapper>
@endsection

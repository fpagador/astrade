@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Editar Usuario</h1>

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

    <x-form.form-wrapper action="{{ route('admin.users.update', $user->id) }}" method="PUT" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <input type="hidden" name="id" value="{{ $user->id }}">

        <x-form.input label="Nombre" name="name" required value="{{ $user->name }}" />
        <x-form.input label="Apellido" name="surname" required value="{{ $user->surname }}" />
        <x-form.input label="DNI" name="dni" required value="{{ $user->dni }}" />
        <x-form.input label="Email" name="email" type="email" value="{{ $user->email }}" />
        <x-form.input label="Teléfono" name="phone" required value="{{ $user->phone }}" />
        <x-form.input label="Usuario" name="username" required value="{{ $user->username }}" />
        <div>
            <label for="role" class="block font-medium mb-1">Rol</label>
            <select name="role_id" id="role" required class="w-full border rounded px-3 py-2 focus:ring-indigo-400 focus:outline-none">
                @foreach($assignableRoles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                        {{ ucfirst($role->role_name) }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-form.input label="Foto" name="photo" type="file" />
        <x-form.input label="Horario de trabajo" name="work_schedule" value="{{ $user->work_schedule }}" />
        <x-form.input label="Tipo de contrato" name="contract_type" value="{{ $user->contract_type }}" />
        <x-form.input label="Fecha de inicio de contrato" name="contract_start_date" type="date" value="{{ $user->contract_start_date }}" />

        <div class="mb-4">
            <label for="notification_type" class="block font-medium mb-1">Tipo de notificación</label>
            <select name="notification_type" id="notification_type" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="none" {{ old('notification_type', $user->notification_type) == 'none' ? 'selected' : '' }}>Ninguna</option>
                <option value="visual" {{ old('notification_type', $user->notification_type) == 'visual' ? 'selected' : '' }}>Visual</option>
                <option value="visual_audio" {{ old('notification_type', $user->notification_type) == 'visual_audio' ? 'selected' : '' }}>Visual y Audio</option>
            </select>
            @error('notification_type')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4 flex items-center space-x-2">
            <input type="checkbox" name="can_receive_notifications" id="can_receive_notifications" value="1" {{ old('can_receive_notifications', $user->can_receive_notifications) ? 'checked' : '' }} class="rounded focus:ring-indigo-400" />
            <label for="can_receive_notifications" class="font-medium">Puede recibir notificaciones</label>
        </div>

        <div class="flex space-x-4 mt-6">
            <button type="submit" class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 flex-1">Actualizar</button>
            <a href="{{ route('admin.users.index') }}" class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 flex-1 text-center">Cancelar</a>
        </div>
    </x-form.form-wrapper>
@endsection

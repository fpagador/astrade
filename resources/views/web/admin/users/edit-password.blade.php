@extends('layouts.app')

@section('title', 'Cambiar Contraseña')

@section('content')
    {{-- ALERTS --}}
    @if(session('success'))
        <div class="w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

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

    <div class="flex justify-center items-center min-h-[60vh]">
        <div class="w-full max-w-md bg-white p-6 rounded shadow-md">
            <h1 class="text-2xl font-semibold mb-6 text-center">
                Cambiar contraseña de {{ $user->name }} {{ $user->surname }}
            </h1>

            <form action="{{ route('admin.users.update-password', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- PASSWORD --}}
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Nueva contraseña
                    </label>
                    <input type="password" name="password" id="password"
                           class="form-input mt-1 block w-full rounded border-gray-300 shadow-sm @error('password') border-red-500 @enderror">
                    @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- CONFIRM PASSWORD --}}
                <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirmar contraseña
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-input mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>

                {{-- BUTTONS --}}
                <div class="flex gap-2 flex items-center justify-between">
                    <a href="{{ route('admin.users.index') }}"
                       class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800 transition shadow">
                        Actualizar contraseña
                    </button>

                </div>
            </form>
        </div>
    </div>

    {{-- BACK BUTTON --}}
    <div class="mt-8">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition shadow">
            ← Volver a usuarios
        </a>
    </div>
@endsection

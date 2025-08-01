@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Usuarios</h1>

        @can('create', \App\Models\User::class)
        <a href="{{ route('admin.users.create') }}"
           class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
            Nuevo Usuario
        </a>
        @endcan
    </div>

    <hr class="border-gray-300 mb-6">

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

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" value="{{ request('name') }}" class="form-input w-full">
        </div>
        <div>
            <label for="dni" class="block text-sm font-medium text-gray-700">DNI</label>
            <input type="text" name="dni" id="dni" value="{{ request('dni') }}" class="form-input w-full">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="text" name="email" id="email" value="{{ request('email') }}" class="form-input w-full">
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Rol</label>
            <select name="role" id="role" class="form-select w-full">
                <option value="">Todos</option>
                @foreach(\App\Enums\RoleEnum::options() as $value => $label)
                    <option value="{{ $value }}" {{ request('role') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                Filtrar
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </a>
        </div>
    </form>

    {{-- TABLE HEADER --}}
    <div class="grid grid-cols-8 bg-indigo-900 text-white font-medium text-sm rounded-t-md px-4 py-2">
        <div>ID</div>
        <div>Nombre</div>
        <div>Apellido</div>
        <div>DNI</div>
        <div>Email</div>
        <div>Teléfono</div>
        <div>Rol</div>
        <div>Acciones</div>
    </div>

    {{-- ROWS --}}
    @foreach($users as $user)
        <div class="grid grid-cols-8 items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
            <div>{{ $user->id }}</div>
            <div>{{ $user->name }}</div>
            <div>{{ $user->surname }}</div>
            <div>{{ $user->dni }}</div>
            <div>{{ $user->email }}</div>
            <div>{{ $user->phone }}</div>
            <div>{{ $user->role ? \App\Enums\RoleEnum::from($user->role->role_name)->label() : '' }} </div>
            <div class="flex gap-2">

                {{-- EDIT --}}
                <a href="{{ route('admin.users.edit', $user->id) }}" title="Editar">
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- VIEW TASKS --}}
                <a href="{{ route('admin.users.tasks', $user->id) }}" title="Ver tareas">
                    <i data-lucide="list-todo" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- CHANGE PASSWORD --}}
                @can('changePassword', $user)
                    @if($user->hasRole('user'))
                        <a href="{{ route('admin.users.edit-password', $user) }}" title="Cambiar contraseña">
                            <i data-lucide="key-round" class="w-5 h-5 text-yellow-600 hover:text-yellow-700 transition"></i>
                        </a>
                    @endif
                @endcan

                {{-- DELETE --}}
                @can('delete', $user)
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                      onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Eliminar">
                        <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
    @endforeach

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $users->appends(request()->query())->links() }}
    </div>
@endsection

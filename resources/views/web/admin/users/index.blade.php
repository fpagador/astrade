@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    @can('viewAdmin', \App\Models\User::class)
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-semibold mb-6">Usuarios de Gestión Interna</h1>

            @can('create', \App\Models\User::class)
                <a href="{{ route('admin.users.create', ['type' => request('type', \App\Enums\UserTypeEnum::MANAGEMENT->value)]) }}"
                   class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
                    Nuevo Usuario
                </a>
            @endcan
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('admin.users.index', ['type' => 'management']) }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
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
                <a href="{{ route('admin.users.index', ['type' => 'management']) }}"
                   class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- EXPORT EXCEL BUTTON --}}
        <div class="flex justify-end mb-6">
            <a href="{{ route('admin.users.export', request()->query()) }}"
               class="mt-1 inline-block px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800 transition shadow">
                Exportar en Excel
            </a>
        </div>

        {{-- TABLE HEADER --}}
        <div class="grid grid-cols-8 bg-indigo-900 text-white font-medium text-sm rounded-t-md px-4 py-2">
            <div><x-admin.sortable-column label="Nombre" field="name" default="true" /></div>
            <div><x-admin.sortable-column label="Apellidos" field="surname" /></div>
            <div><x-admin.sortable-column label="DNI/NIE" field="dni" /></div>
            <div><x-admin.sortable-column label="Email" field="email" /></div>
            <div><x-admin.sortable-column label="Teléfono" field="phone" /></div>
            <div><x-admin.sortable-column label="Rol" field="role" /></div>
            <div><x-admin.sortable-column label="Puede recibir llamada" field="can_be_called" /></div>
            <div>Acciones</div>
        </div>

        {{-- ROWS --}}
        @foreach($users as $user)
            <div class="grid grid-cols-8 items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
                <div>{{ $user->name }}</div>
                <div>{{ $user->surname }}</div>
                <div>{{ $user->dni }}</div>
                <div>{{ $user->email }}</div>
                <div>{{ $user->phone }}</div>
                <div>{{ $user->role ? \App\Enums\RoleEnum::from($user->role->role_name)->label() : '' }} </div>
                <div class="text-center">
                    <i
                        data-lucide="{{ $user->can_be_called ? 'phone' : 'x' }}"
                        class="w-5 h-5 {{ $user->can_be_called ? 'text-green-600' : 'text-red-600' }}">
                    </i>
                </div>
                <div class="flex gap-2">
                    {{-- VIEW USER --}}
                    <a href="{{ route('admin.users.show', [
                                    'user' => $user->id,
                                    'type' => request('type', 'management'),
                                    'back_url' => url()->full()
                                ])}}"
                       title="Ver usuario"
                    >
                        <i data-lucide="eye" class="w-5 h-5 text-blue-600 hover:text-blue-700 transition"></i>
                    </a>

                    {{-- EDIT --}}
                    <a
                        href="{{ route('admin.users.edit', [
                                'user' => $user->id, 'role' => $user->role->role_name ?? null ,
                                'type' => request('type', \App\Enums\UserTypeEnum::MANAGEMENT->value),
                                ]) }}"
                        title="Editar"
                    >
                        <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                    </a>

                    {{-- ASSIGN CALL --}}
                    <form action="{{ route('admin.users.toggleCall', $user) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2 py-1 rounded hover:opacity-90 transition"
                                title="{{ $user->can_be_called ? 'Eliminar recepción de llamadas' : 'Asignar recepción de llamadas' }}">
                            <i
                                data-lucide="{{ $user->can_be_called ? 'x' : 'phone' }}"
                                class="w-5 h-5 p-1 rounded-full {{ $user->can_be_called ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }}">
                            </i>
                        </button>
                    </form>

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
    @endcan
@endsection

@extends('layouts.app')

@section('title', 'Usuarios Móviles')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Usuarios Móviles</h1>

        @can('create', \App\Models\User::class)
            <a href="{{ route('admin.users.create', ['role' => \App\Enums\RoleEnum::USER->value,'type' => request('type', \App\Enums\UserTypeEnum::MOBILE->value)]) }}"
                   class="inline-block mb-4 px-4 py-2 rounded button-success">
                Nuevo Usuario
            </a>
        @endcan
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.users.index', ['type' => 'mobile']) }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <input type="hidden" name="type" value="{{ request('type', 'mobile') }}">
        <x-form.input
            name="name"
            label="Nombre"
            type="text"
            value="{{ request('name') }}"
        />

        <x-form.input
            name="dni"
            label="DNI"
            type="text"
            value="{{ request('dni') }}"
        />

        <x-form.input
            name="email"
            label="Email"
            type="text"
            value="{{ request('email') }}"
        />

        <x-form.select
            name="company_id"
            label="Empresa"
            :options="['' => '-- Todas --'] + $companies->pluck('name', 'id')->toArray()"
            :selected="request('company_id', '')"
        />

        <div class="mb-4">
            <button type="submit" class="mt-1 px-4 py-2 rounded button-success shadow">Filtrar</button>
            <a href="{{ route('admin.users.index', ['type' => 'management']) }}" class="mt-1 inline-block px-4 py-2 rounded button-cancel shadow">Limpiar</a>
        </div>
    </form>

    {{-- EXPORT EXCEL BUTTON --}}
    <div class="flex justify-end mb-6">
        <a href="{{ route('admin.users.export', request()->query()) }}"
           class="mt-1 inline-block px-4 py-2 rounded button-success transition shadow">
            Exportar en Excel
        </a>
    </div>
    @php
        $gridCols = 'grid-cols-[1fr_1fr_1fr_2fr_1fr_1fr_5rem_10rem]';
    @endphp
    {{-- TABLE HEADER --}}
    <div class="hidden md:grid grid {{ $gridCols }} table-header font-medium text-sm rounded-t-md px-4 py-2">
        <div><x-admin.sortable-column label="Nombre" field="name" default="true" /></div>
        <div><x-admin.sortable-column label="Apellidos" field="surname" /></div>
        <div><x-admin.sortable-column label="DNI/NIE" field="dni" /></div>
        <div><x-admin.sortable-column label="Email" field="email" /></div>
        <div><x-admin.sortable-column label="Teléfono" field="phone" /></div>
        <div><x-admin.sortable-column label="Empresa" field="company" /></div>
        <div class="flex justify-center"><x-admin.sortable-column label="!" field="has_warning" /></div>
        <div>Acciones</div>
    </div>

    {{-- ROWS --}}
    @forelse($users as $user)

        {{-- DESKTOP VERSION --}}
        <div class="hidden md:grid {{ $gridCols }} items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs">{{ $user->name }}</div>
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs">{{ $user->surname }}</div>
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs">{{ $user->dni }}</div>
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs" title="{{ $user->email }}">
                {{ $user->email }}
            </div>
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs">{{ $user->phone }}</div>

            {{-- Empresa --}}
            <div class="truncate whitespace-nowrap overflow-hidden max-w-xs" title="{{ optional($user->company)->name }}">
                @if($user->company)
                    <a href="{{ route('admin.companies.index', ['name' => $user->company->name]) }}"
                       class="text-indigo-700 hover:underline">
                        {{ $user->company->name }}
                    </a>
                @else
                    —
                @endif
            </div>

            {{-- Warning --}}
            <div class="justify-center flex">
                @if($user->has_warning)
                    <div class="relative group">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-yellow-500"></i>
                        <div class="absolute left-1/2 -translate-x-1/2 mt-2 hidden group-hover:block
                        bg-red-900 text-white text-xs rounded px-2 py-1 w-max max-w-xs z-10 shadow-lg">
                            <strong>{{ $user->warning_title }}</strong>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex gap-2 truncate whitespace-nowrap overflow-hidden max-w-xs">
                {{-- VIEW --}}
                <a href="{{ route('admin.users.show', ['user' => $user->id,'type' => request('type','mobile'),'back_url' => url()->full()]) }}"
                   title="Ver usuario">
                    <i data-lucide="eye" class="w-5 h-5 text-blue-600 hover:text-blue-700 transition"></i>
                </a>

                {{-- EDIT --}}
                <a href="{{ route('admin.users.edit', ['user' => $user->id,'role' => $user->role->role_name ?? null,'type' => request('type', \App\Enums\UserTypeEnum::MOBILE->value)]) }}"
                   title="Editar">
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- CHANGE PASSWORD --}}
                <a href="{{ route('admin.users.edit-password', ['user' => $user->id, 'back_url' => url()->full()]) }}"
                   title="Cambiar contraseña">
                    <i data-lucide="key-round" class="w-5 h-5 text-yellow-600 hover:text-yellow-700 transition"></i>
                </a>

                {{-- TASKS --}}
                <a href="{{ route('admin.users.tasks', ['user' => $user,'back_url' => url()->full()]) }}"
                   title="Ver tareas">
                    <i data-lucide="list-todo" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- ABSENCES --}}
                <a href="{{ route('admin.users.absences', ['user' => $user->id, 'back_url' => url()->full()]) }}"
                   title="Vacaciones / Ausencias">
                    <i data-lucide="calendar" class="w-5 h-5 text-green-600 hover:text-green-700 transition"></i>
                </a>

                {{-- DELETE --}}
                <form action="{{ route('admin.users.destroy', $user->id) }}"
                      method="POST"
                      class="delete-form"
                      data-message="¿Está seguro de eliminar este usuario?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Eliminar">
                        <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- MOBILE VERSION (CARDS) --}}
        <div class="md:hidden bg-white rounded shadow p-4 mb-4 text-sm">

            <p><span class="font-medium">Nombre:</span> {{ $user->name }}</p>
            <p><span class="font-medium">Apellidos:</span> {{ $user->surname }}</p>
            <p><span class="font-medium">DNI/NIE:</span> {{ $user->dni }}</p>
            <p><span class="font-medium">Email:</span> {{ $user->email }}</p>
            <p><span class="font-medium">Teléfono:</span> {{ $user->phone }}</p>

            <p>
                <span class="font-medium">Empresa:</span>
                @if($user->company)
                    <a href="{{ route('admin.companies.index', ['name' => $user->company->name]) }}"
                       class="text-indigo-700 hover:underline">
                        {{ $user->company->name }}
                    </a>
                @else
                    —
                @endif
            </p>

            @if($user->has_warning)
                <p class="mt-2 flex items-center gap-2 text-yellow-600">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span>{{ $user->warning_title }}</span>
                </p>
            @endif

            {{-- Actions --}}
            <div class="flex gap-3 mt-4 items-center">

                <a href="{{ route('admin.users.show', ['user' => $user->id,'type' => request('type','mobile'),'back_url' => url()->full()]) }}"
                   title="Ver usuario">
                    <i data-lucide="eye" class="w-6 h-6 text-blue-600 hover:text-blue-700"></i>
                </a>

                <a href="{{ route('admin.users.edit', ['user' => $user->id,'role' => $user->role->role_name ?? null,'type' => request('type', \App\Enums\UserTypeEnum::MOBILE->value)]) }}"
                   title="Editar">
                    <i data-lucide="pencil" class="w-6 h-6 text-indigo-800 hover:text-indigo-900"></i>
                </a>

                <a href="{{ route('admin.users.edit-password', ['user' => $user->id, 'back_url' => url()->full()]) }}"
                   title="Cambiar contraseña">
                    <i data-lucide="key-round" class="w-6 h-6 text-yellow-600 hover:text-yellow-700"></i>
                </a>

                <a href="{{ route('admin.users.tasks', ['user' => $user, 'back_url' => url()->full()]) }}"
                   title="Ver tareas">
                    <i data-lucide="list-todo" class="w-6 h-6 text-indigo-800 hover:text-indigo-900"></i>
                </a>

                <a href="{{ route('admin.users.absences', ['user' => $user->id, 'back_url' => url()->full()]) }}"
                   title="Vacaciones / Ausencias">
                    <i data-lucide="calendar" class="w-6 h-6 text-green-600 hover:text-green-700"></i>
                </a>

                <form action="{{ route('admin.users.destroy', $user->id) }}"
                      method="POST"
                      data-message="¿Está seguro de eliminar este usuario?"
                      class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Eliminar">
                        <i data-lucide="trash-2" class="w-6 h-6 text-red-600 hover:text-red-700"></i>
                    </button>
                </form>

            </div>

        </div>

    @empty
        <div class="col-span-6 text-center text-sm py-6 bg-white border border-t-0 rounded-b-md">
            No hay usuarios móviles registrados.
        </div>
    @endforelse

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $users->appends(request()->query())->links() }}
    </div>
@endsection

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel de Administración</title>
    {{-- Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 text-gray-900 font-sans min-h-screen flex flex-col" x-data="{ sidebarOpen: false }" data-page="{{ str_replace('.', '-', Route::currentRouteName()) }}">
{{-- Offcanvas Overlay --}}
<div class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
     x-show="sidebarOpen" x-transition
     @click="sidebarOpen = false" x-cloak></div>

<div class="flex flex-1">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 w-64 bg-gray-500 text-white z-50 transform md:translate-x-0 md:block transition-transform duration-200 ease-in-out"
           :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" x-cloak>
        {{-- Logo --}}
        <div class="h-20 border-b border-gray-200 flex items-center justify-center px-4 md:px-14">
            <img src="{{ asset('images/logo_admin.png') }}" alt="Logo" class="w-96 h-auto mx-auto">
        </div>

        <nav class="mt-6 px-4 space-y-2 text-sm">
            {{-- Dashboard --}}
            <x-admin.nav-link route="admin.dashboard" icon="layout-dashboard" label="Dashboard" />

            {{-- Menú Usuarios para admin --}}
            @if(auth()->user()->hasRole('admin'))
                <div x-data="{ open: @json(request()->routeIs('admin.users.*')) }" class="space-y-1">
                    {{-- Main button Users --}}
                    <button
                        @click="open = !open"
                        class="flex items-center gap-3 w-full py-2 px-4 rounded hover:bg-gray-700 transition
                        {{ request()->routeIs('admin.users.*') ? 'bg-gray-800' : '' }}"
                    >
                        <i data-lucide="users" class="w-5 h-5 shrink-0"></i>
                            <span class=" text-sm font-medium text-white">Usuarios</span>
                        <i data-lucide="chevron-down" :class="{ 'rotate-180': open }" class="w-4 h-4 transition-transform"></i>
                    </button>

                    {{-- Submenu --}}
                    <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                        @can('viewAdmin', \App\Models\User::class)
                            <x-admin.nav-link
                                route="admin.users.index"
                                :parameters="['type' => 'management']"
                                icon="user-check"
                                label="Gestión Interna"
                            />
                        @endcan

                        <x-admin.nav-link
                            route="admin.users.index"
                            :parameters="['type' => 'mobile']"
                            icon="smartphone"
                            label="Móviles"
                        />
                    </div>
                </div>
            @endif

            {{-- Users menu for manager--}}
            @if(auth()->user()->hasRole('manager'))
                <x-admin.nav-link
                    route="admin.users.index"
                    :parameters="['type' => 'mobile']"
                    icon="users"
                    label="Usuarios Móviles"
                    :active="request()->routeIs('admin.users.index') && request('type') === 'mobile'"
                />
            @endif

            {{-- Other menus --}}
            <x-admin.nav-link route="admin.calendars.index" icon="calendar-days" label="Calendarios laborales" />
            <x-admin.nav-link route="admin.companies.index" icon="map-pin" label="Empresas" />
            <x-admin.nav-link route="admin.task_completion_logs.index" icon="check-circle" label="Registro de Finalización de Tareas" />
            @can('viewLogs', \App\Models\Log::class)
                <x-admin.nav-link route="admin.logs.index" icon="file-text" label="Logs" />
            @endcan
        </nav>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-h-screen md:ml-64">
        {{-- Header --}}
        <header class="h-16 bg-gray-200 shadow px-4 flex items-center justify-between md:px-6">
            <div class="flex items-center gap-3">
                {{-- Toggle Sidebar on Mobile --}}
                <button class="md:hidden text-gray-700" @click="sidebarOpen = true">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="text-lg font-semibold">Panel de Administración</div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-700">
                    @php
                        $user = Auth::user();
                        $nombre = trim(($user->name ?? '') . ' ' . ($user->surname ?? ''));
                    @endphp
                    @if (!empty($nombre))
                        {{ $nombre }}
                    @elseif (!empty($user->username))
                        {{ $user->username }}
                    @else
                        —
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-sm bg-gray-800 text-white px-3 py-1.5 rounded hover:bg-gray-700 transition">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 md:p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="bg-gray-200 border-t p-4 text-center text-sm text-gray-700 mt-auto">
            &copy; {{ now()->year }} Astrade. Todos los derechos reservados.
        </footer>
    </div>
</div>

@stack('modals')
@stack('scripts')
</body>
</html>

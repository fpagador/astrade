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
</head>
<body class="bg-gray-100 text-gray-900 font-sans min-h-screen flex flex-col" x-data="{ sidebarOpen: false }">

{{-- Offcanvas Overlay --}}
<div
    class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
    x-show="sidebarOpen"
    x-transition
    @click="sidebarOpen = false"
    x-cloak>
</div>

<div class="flex flex-1">
    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 w-64 bg-gray-500 text-white z-50 transform md:translate-x-0 md:block transition-transform duration-200 ease-in-out"
        :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
        x-cloak>

        {{-- Logo --}}
        <div class="h-20 border-b border-gray-200 flex items-center justify-center px-4 md:px-14">
            <img src="{{ asset('images/logo_admin.png') }}" alt="Logo" class="w-96 h-auto mx-auto">
        </div>

        <nav class="mt-6 px-4 space-y-2 text-sm">
            <x-admin.nav-link route="admin.dashboard" icon="layout-dashboard" label="Dashboard" />
            <x-admin.nav-link route="admin.users.index" icon="users" label="Usuarios" />
            <x-admin.nav-link route="admin.tasks.index" icon="plus-square" label="Tareas" />
            <x-admin.nav-link route="admin.calendar.index" icon="calendar-days" label="Calendario" />
            <x-admin.nav-link route="admin.notifications.index" icon="bell" label="Notificaciones" />
            <x-admin.nav-link route="admin.locations.index" icon="map-pin" label="Ubicaciones" />
            <x-admin.nav-link route="admin.tasksCompletionLog.index" icon="check-circle" label="Registro de Finalización de Tareas" />
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
                    <button
                        type="submit"
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

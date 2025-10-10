@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="w-full p-4 space-y-6">
        {{-- Page header --}}
        <h1 class="text-3xl font-semibold mb-6">Panel de Control</h1>
        <hr class="border-gray-300 mb-6">

        {{-- USERS INFO BLOCK --}}
        <section>
            <h2 class="text-lg font-semibold mb-2">Usuarios</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-admin.kpi :value="$totalUsers" label="Usuarios registrados" color="green"/>
                <x-admin.kpi :value="$usersManagement" label="Usuarios gestión" color="green"/>
                <x-admin.kpi :value="$usersMobile" label="Usuarios móviles" color="green"/>
                <x-admin.kpi :value="$usersWithoutCalendar" label="Sin calendario laboral" color="green"/>
            </div>
        </section>

        {{-- TASKS INFO BLOCK --}}
        <section class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Tareas</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <x-admin.kpi :value="$tasksToday" label="Tareas hoy" color="yellow"/>
                <x-admin.kpi :value="$tasksTomorrow" label="Tareas mañana" color="yellow"/>
                <x-admin.kpi :value="$recurrentTasks" label="Tareas recurrentes activas" color="yellow"/>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                <x-admin.kpi :value="$pendingTasks" label="Tareas pendientes" color="red"/>
                <x-admin.kpi :value="$usersWithPendingTasks" label="Usuarios con tareas pendientes" color="red"/>
                <x-admin.kpi :value="$delayedSubtasks" label="Subtareas retrasadas" color="red"/>
            </div>
        </section>

        {{-- COMPANIES AND WORK CALENDARS BLOCK --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-6">
            <div>
                <h2 class="text-lg font-semibold mb-2">Empresas</h2>
                <div class="grid grid-cols-1 gap-4">
                    <x-admin.kpi :value="$totalCompanies" label="Empresas registradas" color="indigo"/>
                </div>
            </div>
            <div>
                <h2 class="text-lg font-semibold mb-2">Calendarios Laborales</h2>
                <div class="grid grid-cols-1 gap-4">
                    <x-admin.kpi :value="$activeCalendars" label="Calendarios laborales activos" color="indigo"/>
                </div>
            </div>
        </section>

        {{-- Tasks by Day --}}
        <section class="mt-6 bg-white rounded-lg shadow p-4">
            <h2 class="font-semibold mb-2">Tareas por día</h2>
            <div id="tasksByDayChart"></div>
        </section>

        {{-- Users without tasks --}}
        <section class="mt-6 bg-white rounded-lg shadow p-4">
            <h2 class="font-semibold mb-2">Usuarios sin tareas</h2>
            <div id="usersWithoutTasksChart"></div>
        </section>

        {{-- Employees by company --}}
        <section class="mt-6 bg-white rounded-lg shadow p-4">
            <h2 class="font-semibold mb-2">Empleados por empresa</h2>
            <div id="employeesByCompanyChart"></div>
        </section>

        {{-- AUSENCIAS Y FESTIVOS MES ACTUAL --}}
        <section class="mt-8">
            <h2 class="text-xl font-semibold mb-4">
                Ausencias y Festivos {{ now()->locale('es')->translatedFormat('F') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {{-- Vacaciones mes actual --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::VACATION->value }}">
                        Vacaciones
                    </h3>
                    <ul class="divide-y divide-gray-200">
                        @forelse($userVacationsThisMonth as $absenceData)
                            <li class="px-2 py-1 text-sm">
                                <strong>{{ $absenceData['user']->name }} {{ $absenceData['user']->surname }}</strong>
                                <ul class="ml-4 list-disc">
                                    @foreach($absenceData['periods'] as [$start, $end])
                                        <li>
                                            @if($start->equalTo($end))
                                                {{ $start->format('d/m') }}
                                            @else
                                                {{ $start->format('d/m') }} al {{ $end->format('d/m') }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin vacaciones</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Ausencias legales mes actual --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::LEGAL_ABSENCE->value }}">
                        Ausencias legales
                    </h3>
                    <ul class="divide-y divide-gray-200">
                        @forelse($userLegalAbsencesThisMonth as $absenceData)
                            <li class="px-2 py-1 text-sm">
                                <strong>{{ $absenceData['user']->name }} {{ $absenceData['user']->surname }}</strong>
                                <ul class="ml-4 list-disc">
                                    @foreach($absenceData['periods'] as [$start, $end])
                                        <li>
                                            @if($start->equalTo($end))
                                                {{ $start->format('d/m') }}
                                            @else
                                                {{ $start->format('d/m') }} al {{ $end->format('d/m') }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin ausencias legales</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Festivos mes actual --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::HOLIDAY->value }}">
                        Festivos
                    </h3>
                    <ul class="px-2 py-1 text-sm">
                        @forelse($calendarDaysThisMonth as $day)
                            <li class="ml-4 list-disc">
                                {{ $day->date->format('d/m/Y') }}
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin festivos</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- AUSENCIAS Y FESTIVOS MES SIGUIENTE --}}
            <h2 class="text-xl font-semibold mb-4">
                Ausencias y Festivos {{ now()->addMonth()->locale('es')->translatedFormat('F') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {{-- Vacaciones mes siguiente --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::VACATION->value }}">
                        Vacaciones
                    </h3>
                    <ul class="divide-y divide-gray-200">
                        @forelse($userVacationsNextMonth as $absenceData)
                            <li class="px-2 py-1 text-sm">
                                <strong>{{ $absenceData['user']->name }} {{ $absenceData['user']->surname }}</strong>
                                <ul class="ml-4 list-disc">
                                    @foreach($absenceData['periods'] as [$start, $end])
                                        <li>
                                            @if($start->equalTo($end))
                                                {{ $start->format('d/m') }}
                                            @else
                                                {{ $start->format('d/m') }} al {{ $end->format('d/m') }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin vacaciones</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Ausencias legales mes siguiente --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::LEGAL_ABSENCE->value }}">
                        Ausencias legales
                    </h3>
                    <ul class="divide-y divide-gray-200">
                        @forelse($userLegalAbsencesNextMonth as $absenceData)
                            <li class="px-2 py-1 text-sm">
                                <strong>{{ $absenceData['user']->name }} {{ $absenceData['user']->surname }}</strong>
                                <ul class="ml-4 list-disc">
                                    @foreach($absenceData['periods'] as [$start, $end])
                                        <li>
                                            @if($start->equalTo($end))
                                                {{ $start->format('d/m') }}
                                            @else
                                                {{ $start->format('d/m') }} al {{ $end->format('d/m') }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin ausencias legales</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Festivos mes siguiente --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-2 {{ \App\Enums\CalendarColor::HOLIDAY->value }}">
                        Festivos
                    </h3>
                    <ul class="divide-y divide-gray-200">
                        @forelse($calendarDaysNextMonth as $day)
                            <li class="ml-4 list-disc">
                                {{ $day->date->format('d/m/Y') }}
                            </li>
                        @empty
                            <li class="px-2 py-1 text-gray-500 text-sm">Sin festivos</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <script>
        window.tasksByDayData = @json($tasksByDay);
        window.usersWithoutTasksByDay = @json($usersWithoutTasksByDay);
        window.employeesByCompany = @json($employeesByCompany);

        window.dashboardRoutes = {
            tasksByDay: "{{ route('admin.dashboard.tasks-by-day', ['day' => '__DAY__']) }}",
            usersWithoutTasks: "{{ route('admin.dashboard.users-without-tasks', ['day' => '__DAY__']) }}",
            employeesByCompanyRoute: "{{ route('admin.dashboard.employees-by-company', ['companyId' => '__ID__']) }}"
        };
    </script>
@endsection

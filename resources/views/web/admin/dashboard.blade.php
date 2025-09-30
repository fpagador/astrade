@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<!--   <div class="w-full p-4 space-y-6">
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
            <h2 class="font-semibold mb-2">Tareas por día (mes actual)</h2>
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

        {{-- PENDING TASKS AND SUBTASKS --}}
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            {{-- Tasks pending --}}
            <div class="bg-white rounded-lg shadow p-4 overflow-x-auto">
                <h2 class="font-semibold mb-2">Tareas pendientes</h2>
                <div class="flex font-semibold bg-gray-100 px-4 py-2 rounded-t">
                    <div class="w-40">Nombre</div>
                    <div class="flex-1 min-w-0 px-2">Título tarea</div>
                    <div class="w-24 text-right">Fecha</div>
                </div>
                <ul class="divide-y divide-gray-200">
                    @foreach(\App\Models\Task::with('user')->where('status','pending')->orderBy('scheduled_date')->get() as $task)
                        <li class="flex px-4 py-2 hover:bg-gray-50">
                        <span class="w-40 truncate" title="{{ $task->user->name ?? '—' }} {{ $task->user->surname ?? '' }}">
                            {{ $task->user->name ?? '—' }} {{ $task->user->surname ?? '' }}
                        </span>
                            <span class="flex-1 min-w-0 px-2 truncate" title="{{ $task->title }}">{{ $task->title }}</span>
                            <span class="w-24 text-right text-sm text-gray-500">{{ $task->scheduled_date->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Subtasks pending --}}
            <div class="bg-white rounded-lg shadow p-4 overflow-x-auto">
                <h2 class="font-semibold mb-2">Subtareas pendientes</h2>
                <div class="flex font-semibold bg-gray-100 px-4 py-2 rounded-t">
                    <div class="w-40">Nombre</div>
                    <div class="flex-1 min-w-0 px-2">Subtarea</div>
                    <div class="w-24 text-right">Fecha</div>
                </div>
                <ul class="divide-y divide-gray-200">
                    @foreach(\App\Models\Subtask::with('task.user')->where('status','pending')->get() as $subtask)
                        <li class="flex px-4 py-2 hover:bg-gray-50">
                        <span class="w-40 truncate" title="{{ $subtask->task->user->name ?? '—' }} {{ $subtask->task->user->surname ?? '' }}">
                            {{ $subtask->task->user->name ?? '—' }} {{ $subtask->task->user->surname ?? '' }}
                        </span>
                            <span class="flex-1 min-w-0 px-2 truncate" title="{{ $subtask->title }}">{{ $subtask->title }}</span>
                            <span class="w-24 text-right text-sm text-gray-500">{{ $subtask->task?->scheduled_date?->format('d/m/Y') ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- LAST ASSIGNED TASKS --}}
        <section class="mt-6 bg-white rounded-lg shadow p-4 overflow-x-auto">
            <h2 class="font-semibold mb-2">Últimas tareas asignadas</h2>
            <div class="flex font-semibold bg-gray-100 px-4 py-2 rounded-t">
                <div class="w-40">Nombre</div>
                <div class="flex-1 min-w-0 px-2">Título tarea</div>
                <div class="w-24 text-right">Fecha</div>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach(\App\Models\Task::with('user')->latest()->take(5)->get() as $task)
                    <li class="flex px-4 py-2 hover:bg-gray-50">
                    <span class="w-40 truncate" title="{{ $task->user->name ?? '—' }} {{ $task->user->surname ?? '' }}">
                        {{ $task->user->name ?? '—' }} {{ $task->user->surname ?? '' }}
                    </span>
                        <span class="flex-1 min-w-0 px-2 truncate" title="{{ $task->title }}">{{ $task->title }}</span>
                        <span class="w-24 text-right text-sm text-gray-500">{{ $task->scheduled_date->format('d/m/Y') }}</span>
                    </li>
                @endforeach
            </ul>
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
    {{-- ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // --- TASKS BY DAY ---
            var tasksByDayOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: true }
                },
                series: [{
                    name: 'Tareas',
                    data: @json(array_values($tasksByDay))
                }],
                xaxis: {
                    categories: @json(array_keys($tasksByDay)),
                    labels: { rotate: -45 }
                },
                plotOptions: {
                    bar: { columnWidth: '50%', distributed: true }
                },
                tooltip: {
                    y: {
                        formatter: function (val) { return val + " tareas"; }
                    }
                }
            };
            var tasksByDayChart = new ApexCharts(document.querySelector("#tasksByDayChart"), tasksByDayOptions);
            tasksByDayChart.render();

            // Click event to show tasks for a day
            tasksByDayChart.addEventListener('click', function(event, chartContext, config) {
                var day = config.globals.categoryLabels[config.dataPointIndex];
                fetch(`/admin/dashboard/tasks-by-day/${day}`)
                    .then(res => res.json())
                    .then(data => {
                        let list = data.map(t => `<li>${t.title}</li>`).join('');
                        Swal.fire({
                            title: `Tareas del ${day}`,
                            html: `<ul>${list}</ul>`,
                            width: 600
                        });
                    });
            });

            // --- USERS WITHOUT TASKS ---
            var usersWithoutTasksOptions = {
                chart: {
                    type: 'bar',
                    height: 350
                },
                series: [{
                    name: 'Usuarios sin tareas',
                    data: [@json($usersWithoutTasks->count())]
                }],
                xaxis: {
                    categories: ['Sin tareas']
                }
            };
            var usersWithoutTasksChart = new ApexCharts(document.querySelector("#usersWithoutTasksChart"), usersWithoutTasksOptions);
            usersWithoutTasksChart.render();

            // --- EMPLOYEES BY COMPANY ---
            var employeesByCompanyOptions = {
                chart: { type: 'bar', height: 350 },
                series: [{
                    name: 'Empleados',
                    data: @json($employeesByCompany->pluck('total'))
                }],
                xaxis: {
                    categories: @json($employeesByCompany->pluck('company.name')),
                    labels: { rotate: -45 }
                }
            };
            var employeesByCompanyChart = new ApexCharts(document.querySelector("#employeesByCompanyChart"), employeesByCompanyOptions);
            employeesByCompanyChart.render();

        });
    </script>
@endsection

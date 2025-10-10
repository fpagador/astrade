@extends('layouts.app')

@section('title', 'Tareas del Usuario')

@section('content')
    <div x-data="calendarView()" class="flex flex-col gap-4">
        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">
                <span class="text-gray-500">Usuario:</span> {{ $user->name }} {{ $user->surname }}
            </h1>
            <div class="flex items-center gap-4">
                @can('create',\App\Models\User::class)
                    <a
                        :href="!isVacationDay ? getNewTaskUrl() : '#'"
                        :class="{'opacity-50 cursor-not-allowed': isVacationDay}"
                        :disabled="isVacationDay"
                        @click="if(isVacationDay) alert('No se pueden crear tareas en días de vacaciones.')"
                        class="inline-block mb-4 px-4 py-2 rounded button-success"
                    >
                        Nueva tarea
                    </a>
                @endcan
            </div>
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- SELECT BUTTON --}}
        <div class="flex justify-end">
            <div class="relative w-40">
                <select x-model="viewMode"
                        class="block w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2 pl-8 pr-3 rounded focus:outline-none focus:border-indigo-500">
                    <option value="weekly">Semanal</option>
                    <option value="daily">Diario</option>
                </select>

                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-gray-400">
                    <i data-lucide="calendar" x-show="$data.viewMode==='weekly'" class="w-5 h-5"></i>
                    <i data-lucide="calendar-clock" x-show="$data.viewMode==='daily'" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            {{-- MINI CALENDAR --}}
            <div class="w-1/4 bg-white p-2 rounded shadow">
                <div class="mb-4 flex justify-between items-center gap-2">
                    <button x-on:click="prevMonth()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">&lt;</button>
                    <select
                        x-model="currentMonth"
                        x-on:change="onMonthYearChange()"
                        x-init="$nextTick(() => { $el.value = currentMonth })"
                        class="flex-1 border rounded px-3 py-1"
                    >
                        <template x-for="(name, index) in monthNames" :key="index">
                            <option :value="index" x-text="name"></option>
                        </template>
                    </select>

                    <select
                        x-model="currentYear"
                        x-on:change="onMonthYearChange()"
                        x-init="$nextTick(() => { $el.value = currentYear })"
                        class="flex-1 border rounded px-3 py-1"
                    >
                        <template x-for="year in yearsRange" :key="year">
                            <option :value="year" x-text="year"></option>
                        </template>
                    </select>
                    <button x-on:click="nextMonth()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">&gt;</button>
                </div>
                <div id="miniCalendar" class="grid grid-cols-7 gap-1"></div>

                {{-- LEYEND --}}
                <div class="mt-6 text-sm space-y-4">
                    {{-- First block: Day with tasks and recurring tasks --}}
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 inline-block bg-blue-600 rounded-full"></span> Día con tareas
                        </div>
                        <div class="flex items-center gap-1">
                            <i data-lucide="repeat" class="w-4 h-4 text-blue-600"></i> Tarea recurrente
                        </div>
                    </div>

                    {{-- Second block: Colors according to type of task --}}
                    <div class="flex flex-wrap gap-4" x-data="{ calendarColors: @js($calendarColors) }">
                        <div class="flex items-center gap-1">
                            <span class="w-4 h-4 inline-block bg-blue-200 rounded-sm"></span> Día actual
                        </div>
                        <template x-for="color in calendarColors" :key="color.class">
                            <div class="flex items-center gap-1">
                                <span class="w-4 h-4 inline-block rounded-sm border border-gray-300" :class="color.class"></span>
                                <span x-text="color.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            {{-- CALENDAR / VIEWS --}}
            <div class="flex-1">
                {{-- WEEKLY VIEW --}}
                <div x-show="viewMode==='weekly'"
                     class="bg-white p-2 rounded shadow"
                >
                    <div class="relative mb-2">
                        <!-- Flecha izquierda -->
                        <button @click="goPrevWeek()"
                                class="absolute left-0 top-0 bottom-0 flex items-center justify-center px-2 hover:bg-gray-200 rounded">
                            <i data-lucide="chevron-left" class="w-6 h-6"></i>
                        </button>

                        <!-- Encabezado de días como grid de 7 columnas -->
                        <div class="grid grid-cols-7 text-center border-b border-gray-300">
                            <template x-for="day in displayedDays" :key="day.date">
                                <div class="py-2 font-semibold"
                                     :class="{'bg-blue-200': day.isSelected}">
                                    <div x-text="day.label"></div>
                                    <div x-text="day.weekday"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Flecha derecha -->
                        <button @click="goNextWeek()"
                                class="absolute right-0 top-0 bottom-0 flex items-center justify-center px-2 hover:bg-gray-200 rounded">
                            <i data-lucide="chevron-right" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        <template x-for="day in displayedDays" :key="day.date">
                            <div class="border border-gray-300 min-h-[200px] flex flex-col p-1">
                                <template x-for="task in tasks[day.date] ?? []" :key="task.id">
                                    <div class="p-2 mb-1 rounded shadow cursor-pointer"
                                         :style="{ backgroundColor: task.color ?? '#FFFFFF' }"
                                         :class="window.calendarColors[task.type?.toUpperCase()]?.class"
                                         x-on:click="openTask(task.id, day.date)">

                                        <div class="font-semibold truncate flex items-center gap-2" :title="task.title">
                                            <span x-text="task.title"></span>
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1 flex items-center gap-1">
                                            <span x-text="task.scheduled_time ?? 'No asignada'"></span>
                                            <i data-lucide="repeat" class="w-4 h-4 text-blue-600" x-show="task.recurrent_task_id"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div id="task-detail-container" class="min-h-[40px] mt-2"></div>
                </div>

                {{-- DAILY VIEW --}}
                <div x-show="viewMode==='daily'">
                    <div x-data="dailyControls('{{ $date }}')" x-init="initDaily()">
                        <div class="flex items-center gap-4 mb-6">
                            <button @click="prevDay()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Anterior</button>
                            <button @click="today()" class="px-3 py-1 rounded bg-indigo-900 text-white hover:bg-indigo-800">Hoy</button>
                            <button @click="nextDay()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Siguiente</button>
                            <span class="ml-4 font-semibold text-gray-700" x-text="formatDate(selectedDate)"></span>
                        </div>
                    </div>

                    <div id="daily-tasks-container">
                        {{-- Aquí se cargará el partial daily-tasks via AJAX --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BACK BUTTON --}}
    <x-admin.back-to-users-button :type="\App\Enums\UserTypeEnum::MOBILE->value" :back_url="$backUrl" />

    <script>
        window.tasksByDate = @json($tasksByDate);
        window.specialDays = @json($specialDays);
        window.calendarColors = @json($calendarColors);
        window.newTaskBaseUrl = "{{ route('admin.users.tasks.create', ['user' => $user]) }}";
        window.dailyTasksBaseUrl = "{{ route('admin.users.tasks.daily', ['user' => $user]) }}";
        window.taskDetailBaseUrl = "{{ route('admin.users.tasks.detail', ['task' => '__ID__']) }}";
    </script>

    @push('modals')
        <x-admin.image-modal />
        <x-admin.recurrent-task-modal />
    @endpush
@endsection

@extends('layouts.app')
@section('title', 'Calendario de Tareas')
@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            <span class="text-gray-500">Usuario:</span> {{ $user->name }} {{ $user->surname }}
        </h1>
        @can('create', \App\Models\User::class)
            <a href="{{ route('admin.users.tasks.create', ['userId' => $user->id, 'date' => $date]) }}"
               class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
                Nueva tarea
            </a>
        @endcan
    </div>
    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    <div x-data="calendarView()" class="flex gap-6">
        {{-- LEFT PANEL: Mini calendario --}}
        <div class="w-1/4 bg-white p-2 rounded shadow">
            <div class="mb-4 flex justify-between items-center gap-2">
                <button x-on:click="prevMonth()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">&lt;</button>
                <select x-model="currentMonth" x-on:change="onMonthYearChange()"
                        class="flex-1 border rounded px-3 py-1 appearance-none bg-white
                               bg-[url('data:image/svg+xml;utf8,<svg fill=%22%233B82F6%22 height=%2212%22 viewBox=%220 0 20 20%22 width=%2212%22 xmlns=%22http://www.w3.org/2000/svg%22><path d=%22M5.516 7.548l4.484 4.468 4.484-4.468L15.516 9l-5 5-5-5z%22/></svg>')]
                               bg-no-repeat bg-right pr-6 text-center">
                    <template x-for="(name, index) in monthNames" :key="index">
                        <option :value="index" x-text="name" :selected="index === currentMonth"></option>
                    </template>
                </select>
                <select x-model="currentYear" x-on:change="onMonthYearChange()"
                        class="flex-1 border rounded px-3 py-1 appearance-none bg-white
                               bg-[url('data:image/svg+xml;utf8,<svg fill=%22%233B82F6%22 height=%2212%22 viewBox=%220 0 20 20%22 width=%2212%22 xmlns=%22http://www.w3.org/2000/svg%22><path d=%22M5.516 7.548l4.484 4.468 4.484-4.468L15.516 9l-5 5-5-5z%22/></svg>')]
                               bg-no-repeat bg-right pr-6 text-center">
                    <template x-for="year in yearsRange" :key="year">
                        <option :value="year" x-text="year" :selected="year === currentYear"></option>
                    </template>
                </select>
                <button x-on:click="nextMonth()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">&gt;</button>
            </div>
            <div id="miniCalendar" class="grid grid-cols-7 gap-1"></div>
        </div>

        {{-- MAIN PANEL: Tasks columns --}}
        <div class="flex-1 overflow-x-auto bg-white p-2 rounded shadow">
            {{-- Day headers --}}
            <div class="flex gap-2 border-b border-gray-300 mb-2">
                <template x-for="day in displayedDays" :key="day.date">
                    <div class="flex-1 text-center py-2 bg-gray-100 font-semibold border-r border-gray-300">
                        <div x-text="day.label"></div>
                        <div x-text="day.weekday"></div>
                    </div>
                </template>
            </div>

            {{-- Tasks per day --}}
            <div class="grid grid-cols-7 gap-2">
                <template x-for="day in displayedDays" :key="day.date">
                    <div class="border border-gray-300 min-h-[200px] flex flex-col p-1">
                        <template x-for="task in tasks[day.date] ?? []" :key="task.id">
                            <div
                                class="p-2 mb-1 rounded shadow text-black cursor-pointer w-full overflow-hidden"
                                :style="{ backgroundColor: task.color === '' ? '#ffffff' : task.color }"
                                x-on:click="openTask(task.id, day.date)"
                            >
                                {{-- TITLE --}}
                                <div class="font-semibold truncate w-full overflow-hidden whitespace-nowrap" :title="task.title">
                                    <span x-text="task.title"></span>
                                </div>
                                {{-- HORA DE INICIO --}}
                                <div class="text-sm text-gray-600 mt-1" x-text="task.scheduled_time ?? 'No asignada'"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- DETAIL PANEL (server HTML inserted here) --}}
    <div id="task-detail-container" class="min-h-[40px]"></div>

    {{-- BACK BUTTON --}}
    <x-admin.back-to-users-button :type="\App\Enums\UserTypeEnum::MOBILE->value" />

    <script>
        function calendarView() {
            // Utility function to format dates in local (YYYY-MM-DD)
            function formatDateLocal(date) {
                return date.getFullYear() + '-'
                    + String(date.getMonth() + 1).padStart(2, '0') + '-'
                    + String(date.getDate()).padStart(2, '0');
            }

            const today = new Date();

            return {
                currentDate: today,
                displayedDays: [],
                tasks: @json($tasksByDate),
                monthLabel: '',
                currentMonth: today.getMonth(),
                currentYear: today.getFullYear(),
                monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                yearsRange: Array.from({length: 11}, (_, i) => today.getFullYear() - 5 + i),

                init() {
                    this.updateDisplayedDays(7);
                    this.updateMonthLabel();
                    this.renderMiniCalendar();
                },

                updateDisplayedDays(count) {
                    this.displayedDays = [];
                    const startDay = new Date(this.currentDate);

                    const dayOfWeek = startDay.getDay();
                    const diff = (dayOfWeek === 0 ? -6 : 1 - dayOfWeek);
                    startDay.setDate(startDay.getDate() + diff);

                    for (let i = 0; i < count; i++) {
                        const day = new Date(startDay);
                        day.setDate(startDay.getDate() + i);
                        // 🔹 Usamos fecha local en lugar de toISOString()
                        const dayKey = formatDateLocal(day);
                        this.displayedDays.push({
                            date: dayKey,
                            label: day.getDate(),
                            weekday: day.toLocaleDateString('es-ES', { weekday: 'short' })
                        });
                    }

                    // Clear the detail panel when changing weeks
                    document.getElementById('task-detail-container').innerHTML = '';
                },

                prevMonth() {
                    this.currentMonth--;
                    if (this.currentMonth < 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    }
                    this.onMonthYearChange();
                },

                nextMonth() {
                    this.currentMonth++;
                    if (this.currentMonth > 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    }
                    this.onMonthYearChange();
                },

                updateMonthLabel() {
                    const date = new Date(this.currentYear, this.currentMonth, 1);
                    this.monthLabel = date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
                },

                renderMiniCalendar() {
                    const mini = document.getElementById('miniCalendar');
                    if (!mini) return;

                    mini.innerHTML = '';

                    const weekDays = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
                    weekDays.forEach(d => {
                        const header = document.createElement('div');
                        header.textContent = d;
                        header.className = 'text-center font-semibold text-gray-700 bg-gray-100 py-1 rounded';
                        mini.appendChild(header);
                    });

                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);

                    const startWeekday = (firstDay.getDay() + 6) % 7; // lunes=0
                    for (let i = 0; i < startWeekday; i++) mini.appendChild(document.createElement('div'));

                    for (let d = 1; d <= lastDay.getDate(); d++) {
                        const dayDate = new Date(this.currentYear, this.currentMonth, d);
                        const dayKey = formatDateLocal(dayDate);

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'border p-1 rounded relative hover:bg-gray-200';
                        btn.textContent = d;

                        if (this.tasks[dayKey]?.length) {
                            const indicator = document.createElement('div');
                            indicator.className = 'absolute bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 rounded-full';
                            indicator.style.backgroundColor = '#3B82F6';
                            btn.appendChild(indicator);
                        }

                        btn.addEventListener('click', () => {
                            // Always show the week starting from Monday
                            let monday = new Date(dayDate);
                            const dayOfWeek = monday.getDay();
                            const diff = (dayOfWeek === 0 ? -6 : 1 - dayOfWeek); // Sunday=0
                            monday.setDate(monday.getDate() + diff);

                            this.currentDate = monday;
                            this.updateDisplayedDays(7);

                            // Clear detail when changing day/week
                            document.getElementById('task-detail-container').innerHTML = '';
                        });

                        mini.appendChild(btn);
                    }
                },

                onMonthYearChange() {
                    this.updateMonthLabel();
                    this.renderMiniCalendar();
                    this.currentDate = new Date(this.currentYear, this.currentMonth, 1);
                    this.updateDisplayedDays(7);
                },

                openTask(taskId, taskDate = null) {
                    const dateParam = taskDate ?? (this.displayedDays.length ? this.displayedDays[0].date : formatDateLocal(new Date()));

                    fetch(`{{ route('admin.users.tasks.detail', ['task' => '__ID__']) }}?date=${dateParam}`.replace('__ID__', taskId), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.text())
                        .then(html => {
                            document.getElementById('task-detail-container').innerHTML = html;
                            if (window.createIcons && window.lucideIcons) {
                                window.createIcons({ icons: window.lucideIcons });
                            }
                            document.getElementById('task-detail-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
                        })
                        .catch(() => alert('No se pudo cargar la tarea'));
                }
            }
        }
    </script>

    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

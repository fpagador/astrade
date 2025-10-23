@extends('layouts.app')

@section('title', 'Registro de Tareas')

@section('content')
    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Registro de Tareas por Usuario</h1>

        {{-- EXPORT EXCEL BUTTON --}}
            <a href="{{ route('admin.task_logs.export', request()->query()) }}"
               class="inline-block mb-4 px-4 py-2 rounded button-success">
                Exportar en Excel
            </a>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.task_logs.index') }}"
          class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">

        <div>
            <label for="user_name" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
            <input type="text" name="user_name" id="user_name" value="{{ $filters['user_name'] ?? '' }}" class="form-input w-full">
        </div>

        <div>
            <label for="task_title" class="block text-sm font-medium text-gray-700">Título de Tarea</label>
            <input type="text" name="task_title" id="task_title" value="{{ $filters['task_title'] ?? '' }}" class="form-input w-full">
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado de tarea</label>
            <select name="status" id="status" class="form-select w-full">
                <option value="">Todos</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completada</option>
            </select>
        </div>

        <div>
            <label for="date" class="block text-sm font-medium text-gray-700">Fecha</label>
            <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="form-input w-full">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="mt-1 px-4 py-2 rounded button-success shadow">Filtrar</button>
            <a href="{{ route('admin.task_logs.index') }}" class="mt-1 inline-block px-4 py-2 rounded button-cancel shadow">Limpiar</a>
        </div>
    </form>

    {{-- LISTADO --}}
    <div class="space-y-6">
        @forelse ($users as $user)
            <div x-data="{ showDates: false, openDates: {}, openTasks: {} }"
                 class="bg-white shadow rounded-md border border-gray-200 overflow-hidden">

                {{-- HEADER DE USUARIO --}}
                <div class="flex justify-between items-center px-4 py-3 bg-gray-50 cursor-pointer hover:bg-gray-100"
                     @click="showDates = !showDates">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-indigo-100 text-indigo-700 font-semibold rounded-full">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h2 class="font-semibold text-gray-800">
                            {{ $user->name }} {{ $user->surname }}
                        </h2>
                    </div>

                    <span class="text-sm text-gray-500">
                    {{ $user->tasks->count() }} tareas
                </span>
                </div>

                {{-- FECHAS (nivel 1) --}}
                <div x-show="showDates" x-collapse>
                    @if (!empty($user->tasks_by_date) && $user->tasks_by_date->count())
                        @foreach ($user->tasks_by_date as $dateKey => $tasks)
                            @php
                                $isNoDate = $dateKey === 'sin_fecha';
                                $displayDate = $isNoDate ? 'Sin fecha' : \Carbon\Carbon::parse($dateKey)->format('d/m/Y');
                            @endphp

                            <div class="border-t border-gray-100">
                                <div class="flex items-center justify-between px-4 py-2 bg-gray-100 cursor-pointer hover:bg-gray-200"
                                     @click="openDates['{{ $dateKey }}'] = ! openDates['{{ $dateKey }}']">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i>
                                        <span class="font-bold text-gray-800">{{ $displayDate }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $tasks->count() }} tarea{{ $tasks->count() > 1 ? 's' : '' }}
                                    </div>
                                </div>

                                {{-- TAREAS (nivel 2) --}}
                                <div x-show="openDates['{{ $dateKey }}']" x-collapse>
                                    @foreach ($tasks as $task)
                                        <div x-data class="border-t border-gray-50 flex hover:bg-gray-50 cursor-pointer transition"
                                             {{-- Toggle por id de task dentro del objeto openTasks --}}
                                             @click="$event.stopPropagation(); openTasks[{{ $task->id }}] = ! openTasks[{{ $task->id }}]">

                                            {{-- Barra lateral: verde si completed, amarillo si pending --}}
                                            <div class="w-2"
                                                 style="background-color: {{ $task->status === 'completed' ? '#00B050' : '#FFC000' }}"></div>

                                            {{-- CONTENIDO DE LA TAREA --}}
                                            <div class="flex-1 px-4 py-3">
                                                <div class="flex justify-between items-center">
                                                    <div class="flex items-center gap-3">
                                                        <p class="font-medium text-gray-800 flex items-center gap-2">
                                                            {{ $task->title }}
                                                            @if ($task->is_recurrent)
                                                                <i data-lucide="repeat" class="w-4 h-4 text-blue-600"></i>
                                                            @endif
                                                        </p>

                                                        <p class="text-sm text-gray-500">
                                                            @if ($task->scheduled_time)
                                                                {{ \Carbon\Carbon::parse($task->scheduled_time)->format('H:i') }}
                                                            @else
                                                                Sin hora
                                                            @endif
                                                        </p>
                                                    </div>

                                                    <span class="text-xs px-2 py-1 rounded-full uppercase tracking-wide font-medium"
                                                          style="background-color: {{ $task->status === 'completed' ? '#00B050' : '#FFC000' }};">
                                                    {{ $task->status_label }}
                                                </span>
                                                </div>

                                                {{-- SUBTAREAS (nivel 3) --}}
                                                <div x-show="openTasks[{{ $task->id }}]" x-collapse>
                                                    @if ($task->subtasks->count())
                                                        <ul class="divide-y divide-gray-100 bg-gray-50 mt-2 ml-6 rounded-md border border-gray-100">
                                                            @foreach ($task->subtasks as $subtask)
                                                                <li class="flex justify-between items-center px-4 py-2 text-sm">
                                                                    <div class="flex items-center space-x-2">
                                                                    <span class="w-2 h-2 rounded-full"
                                                                          style="background-color: {{ $subtask->status === 'completed' ? '#00B050' : '#FFC000' }};">
                                                                    </span>
                                                                        <span class="text-gray-700 flex items-center gap-1">
                                                                        <i data-lucide="chevron-right" class="w-3 h-3 text-gray-400"></i>
                                                                        {{ $subtask->title }}
                                                                    </span>
                                                                    </div>
                                                                    <span class="text-xs text-gray-500">
                                                                    {{ ucfirst($subtask->status_label) }}
                                                                </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="px-6 py-2 text-sm text-gray-500 ml-6">Sin subtareas.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="px-4 py-3 text-sm text-gray-500">No hay tareas para este usuario.</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-sm py-6 bg-white border rounded-md">
                No hay usuarios con tareas registradas.
            </div>
        @endforelse
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-6">
        {{ $users->appends(request()->query())->links() }}
    </div>
@endsection

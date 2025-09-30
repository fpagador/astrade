<div>
    {{-- FILTROS --}}
    <form id="daily-filters" @submit.prevent="$dispatch('filters-changed', {
        title: $event.target.title.value,
        status: $event.target.status.value
    })"
          class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Título de tarea</label>
            <input type="text" name="title" id="title"
                   value="{{ request('title') }}"
                   class="form-input w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="status" id="status"
                    class="form-select mt-1 block w-full rounded border-gray-300 shadow-sm">
                <option value="">Todos</option>
                @foreach(\App\Enums\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status')==$status->value)>
                    {{ status_label($status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                Filtrar
            </button>
            <button type="button"
                    @click="$dispatch('filters-changed', { title: '', status: '' })"
                    class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </button>
        </div>
    </form>

    {{-- LISTA DE TAREAS --}}
    @if($tasks->count())
        <div class="space-y-4">
            @foreach ($tasks as $task)
                @php
                    $totalSubtasks = $task->subtasks->count();
                    $completedSubtasks = $task->subtasks->where('status', 'completed')->count();
                    $progressPercent = $totalSubtasks > 0
                        ? round(($completedSubtasks / $totalSubtasks) * 100)
                        : 0;
                    $barColor = match (true) {
                        $progressPercent < 30 => 'bg-red-500',
                        $progressPercent < 70 => 'bg-yellow-400',
                        default => 'bg-green-500',
                    };
                    $timeKey = $task->scheduled_time ? $task->scheduled_time->format('H:i') : 'no_hora';
                    $isConflict = isset($timeCounts[$timeKey]) && $timeCounts[$timeKey] > 1;
                @endphp

                <div x-data="{ open: false }"
                     class="rounded shadow p-6 border-4"
                     style="border-color: {{ $task->color === '' ? '#e5e7eb' : $task->color }}; background-color: #ffffff;">

                    {{-- HEADER --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-semibold text-gray-800">{{ $task->title }}</h2>
                                @if($task->recurrent_task_id)
                                    <i data-lucide="repeat" class="w-5 h-5 text-blue-600" title="Tarea recurrente"></i>
                                @endif
                            </div>
                            <p class="text-gray-600">{{ $task->description }}</p>
                            <p class="text-sm text-gray-500">
                                Hora: {{ $task->scheduled_time?->format('H:i') ?? 'No asignada' }}
                                @if($task->estimated_duration_minutes)
                                    · Duración: {{ $task->estimated_duration_minutes }} min
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">
                                Estado: {{ status_label($task->status) }}
                                · Fecha: {{ $task->scheduled_date?->format('d/m/Y') ?? 'No asignada' }}
                            </p>

                            {{-- PICTOGRAM --}}
                            @if ($task->pictogram_path)
                                <div class="mt-2">
                                    <button type="button"
                                            @click="$dispatch('open-image', { src: '{{ asset('storage/' . $task->pictogram_path) }}' })"
                                            title="Ver pictograma de {{ $task->title }}"
                                            class="h-32 w-32 object-contain rounded cursor-pointer transition hover:brightness-110">
                                        <img src="{{ asset('storage/' . $task->pictogram_path) }}"
                                             alt="Pictograma de {{ $task->title }}"
                                             class="object-contain w-full h-full">
                                    </button>
                                </div>
                            @endif

                            {{-- PROGRESS --}}
                            @if($totalSubtasks)
                                <div class="mt-2">
                                    @if($progressPercent === 100)
                                        <div class="flex items-center text-green-600 text-sm font-semibold mb-1">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i>
                                            ¡Todas las subtareas completadas!
                                        </div>
                                    @else
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>{{ $completedSubtasks }} / {{ $totalSubtasks }} subtareas</span>
                                            <span>{{ $progressPercent }}%</span>
                                        </div>
                                    @endif
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="h-full transition-all duration-500 {{ $barColor }}"
                                             style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- ACTIONS --}}
                        <div class="flex items-center gap-3">
                            {{-- Time conflict --}}
                            @if($isConflict)
                                <button title="Conflicto de horario con otra tarea"
                                        class="w-8 h-8 flex items-center justify-center bg-yellow-200 border-2 border-red-600 text-red-800 rounded-none hover:bg-yellow-300 transition"
                                        type="button">
                                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                </button>
                            @endif

                            {{-- Edit --}}
                            @if($task->recurrentTask)
                                <button type="button"
                                        @click="$dispatch('open-action-modal', {
                                            taskId: {{ $task->id }},
                                            type: 'edit',
                                            editUrl: '{{ route('admin.users.tasks.edit', ['user' => $user->id, 'id' => $task->id, 'date' => $date]) }}'
                                        })"
                                        title="Editar tarea recurrente">
                                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800"></i>
                                </button>
                            @else
                                <a href="{{ route('admin.users.tasks.edit', ['user' => $user->id, 'id' => $task->id, 'date' => $date]) }}" title="Editar">
                                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                                </a>
                            @endif

                            {{-- Delete --}}
                            <button type="button"
                                    @click="$dispatch('open-action-modal', {
                                        taskId: {{ $task->id }},
                                        userId: {{ $user->id }},
                                        deleteUrl: '{{ route('admin.users.tasks.destroy', ['user' => $user->id, 'task' => $task->id, 'date' => $date]) }}',
                                        type: 'delete',
                                        isRecurrent: {{ $task->recurrentTask ? 'true' : 'false' }}
                                    })"
                                    title="Eliminar tarea">
                                <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                            </button>

                            {{-- View Subtasks --}}
                            @if ($task->subtasks->count())
                                <button type="button"
                                        @click="open = !open"
                                        class="focus:outline-none transition-transform duration-300"
                                        :class="{ 'rotate-180': open }"
                                        title="Ver/Ocultar subtareas">
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600 hover:text-gray-800 transition-transform"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- SUBTASKS --}}
                    <div x-show="open" x-transition.duration.300ms x-cloak
                         class="mt-4 bg-indigo-50 p-3 rounded">
                        <ul class="space-y-2">
                            @foreach ($task->subtasks as $subtask)
                                <li class="flex justify-between items-start border-b border-indigo-100 pb-2">
                                    <div class="flex items-start gap-2">
                                        <i data-lucide="corner-down-right" class="w-4 h-4 text-indigo-600 mt-1"></i>
                                        <div>
                                            <p class="text-base font-semibold text-gray-800 leading-tight">
                                                {{ $subtask->title }}
                                            </p>
                                            @if ($subtask->pictogram_path)
                                                <div class="mt-2">
                                                    <button type="button"
                                                            @click="$dispatch('open-image', { src: '{{ asset('storage/' . $subtask->pictogram_path) }}' })"
                                                            title="Ver pictograma de {{ $subtask->title }}"
                                                            class="h-32 w-32 object-contain rounded cursor-pointer transition hover:brightness-110">
                                                        <img src="{{ asset('storage/' . $subtask->pictogram_path) }}"
                                                             alt="Pictograma de {{ $subtask->title }}"
                                                             class="object-contain w-full h-full">
                                                    </button>
                                                </div>
                                            @endif
                                            <p class="text-sm text-gray-600 mt-1">{{ $subtask->description }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Estado: <span class="capitalize">{{ status_label($subtask->status) }}</span> ·
                                                Fecha: {{ $subtask->scheduled_date?->format('d/m/Y') ?? 'Sin fecha' }}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center text-gray-600 bg-white rounded p-6 shadow">
            @if (! $hasAnyTasks)
                Este usuario no tiene tareas asignadas.
            @else
                No hay tareas con esos criterios.
            @endif
        </div>
    @endif

    {{-- PAGINATION --}}
    @if ($tasks->hasPages())
        <div class="mt-6">
            {{ $tasks->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>

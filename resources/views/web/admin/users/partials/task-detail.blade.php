<div id="task-detail-container" class="mt-8">
    <div class="rounded shadow p-6 border-4"
         style="border-color: {{ $task->color === '' ? '#e5e7eb' : $task->color }}; background-color: #ffffff;">
        <div class="flex justify-between items-start mb-2">
            <div class="space-y-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $task->title }}</h2>
                <h5 class="text-md font-semibold text-gray-700">{{ $task->description }}</h5>
                <p class="text-sm text-gray-600">
                    Hora inicio: {{ $task->scheduled_time?->format('H:i') ?? 'No asignada' }}
                    @if($task->estimated_duration_minutes)
                        · Duración estimada: {{ $task->estimated_duration_minutes }} min
                    @endif
                </p>

                @php
                    $timeKey = $task->scheduled_time ? $task->scheduled_time->format('H:i') : 'no_hora';
                    $isConflict = isset($timeCounts[$timeKey]) && $timeCounts[$timeKey] > 1;
                @endphp

                <p class="text-sm text-gray-600">
                    Estado: <span class="capitalize">{{ status_label($task->status) }}</span> ·
                    Fecha: {{ $task->scheduled_date?->format('d/m/Y') ?? 'No asignada' }}
                </p>
            </div>
            <div class="flex items-center gap-3">

                {{-- SQUARE ALERT BUTTON --}}
                @if($isConflict)
                    <button title="Conflicto de horario con otra tarea"
                            class="w-8 h-8 flex items-center justify-center bg-yellow-200 border-2 border-red-600 text-red-800 rounded-none hover:bg-yellow-300 transition"
                            type="button"
                    >
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </button>
                @endif

                {{-- VIEW PICTOGRAM --}}
                @if ($task->pictogram_path)
                    <button type="button"
                            @click="$dispatch('open-image', '{{ asset('storage/' . $task->pictogram_path) }}')"
                            title="Ver pictograma"
                            class="ml-2 text-gray-600 hover:text-gray-900">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                @endif

                {{-- EDIT --}}
                <a href="{{ route('admin.users.tasks.edit', ['user' => $user->id, 'id' => $task->id, 'date' => $date]) }}" title="Editar">
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- DELETE --}}
                <form action="{{ route('admin.users.tasks.destroy', ['user' => $user->id, 'task' => $task->id, 'date' => $date]) }}"
                      method="POST"
                      onsubmit="return confirm('¿Está seguro de eliminar esta tarea del usuario {{ $user->name }}?');"
                      class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Eliminar">
                        <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- SUBTASKS --}}
        {{-- SUBTASKS --}}
        @if($task->subtasks->count())
            @php
                $total = $task->subtasks->count();
                $completed = $task->subtasks->where('status', 'completed')->count();
                $percent = $total ? round(($completed / $total) * 100) : 0;
                $barColor = $percent < 30 ? 'bg-red-500' : ($percent < 70 ? 'bg-yellow-400' : 'bg-green-500');
            @endphp

            {{-- PROGRESS --}}
            <div class="mt-3">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>{{ $completed }} / {{ $total }} subtareas completadas</span>
                    <span>{{ $percent }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="h-full transition-all duration-500 {{ $barColor }}" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <div class="mt-4 space-y-4">
                @foreach ($task->subtasks as $subtask)
                    <div class="bg-gray-50 p-4 rounded shadow-sm border border-gray-200 hover:shadow-md transition">
                        <div class="flex items-start gap-4">
                            <i data-lucide="corner-down-right" class="w-5 h-5 text-indigo-600 mt-1"></i>
                            <div class="flex-1">
                                {{-- TITLE --}}
                                <p class="text-base font-semibold leading-tight">{{ $subtask->title }}</p>

                                {{-- SUBTASK PICTOGRAM --}}
                                @if ($subtask->pictogram_path)
                                    <div class="mt-2">
                                        <button type="button"
                                                @click="$dispatch('open-image', '{{ asset('storage/' . $subtask->pictogram_path) }}')"
                                                title="Ver pictograma de {{ $subtask->title }}"
                                                class="w-52 h-52 flex items-center justify-center rounded border hover:shadow-lg transition cursor-pointer p-1 bg-white hover:scale-105">
                                            <img src="{{ asset('storage/' . $subtask->pictogram_path) }}"
                                                 alt="Pictograma de {{ $subtask->title }}"
                                                 class="object-contain w-full h-full">
                                        </button>
                                    </div>
                                @endif

                                {{-- DESCRIPTION --}}
                                <p class="text-sm mt-2">{{ $subtask->description }}</p>

                                {{-- STATUS AND DATE --}}
                                <p class="text-xs text-gray-500 mt-1">
                                    Estado: <span class="capitalize">{{ status_label($subtask->status) }}</span> ·
                                    Fecha: {{ $subtask->scheduled_date?->format('d/m/Y') ?? 'Sin fecha' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

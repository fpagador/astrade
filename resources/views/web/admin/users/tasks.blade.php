@extends('layouts.app')

@section('title', 'Tareas del Usuario')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            <span class="text-gray-500">Usuario:</span> {{ $user->name }} {{ $user->surname }}
        </h1>

        @can('create',\App\Models\User::class)
        <a href="{{ route('admin.tasks.create', ['userId' => $user->id]) }}"
           class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
            Nueva tarea
        </a>
        @endcan
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ session('error') }}</strong>
        </div>
    @endif

    @if ($errors->has('general'))
        <div class="w-full bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ $errors->first('general') }}</strong>
        </div>
    @endif

    {{-- FILTERS--}}
    <form method="GET" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Título de tarea</label>
            <input type="text" name="title" id="title" value="{{ request('title') }}" class="form-input w-full">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="status" id="status" class="form-select mt-1 block w-full rounded border-gray-300 shadow-sm">
                <option value="">Todos</option>
                @foreach(\App\Enums\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}">
                        {{ status_label($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="scheduled_date" class="block text-sm font-medium text-gray-700">Fecha</label>
            <input type="date" name="scheduled_date" id="scheduled_date"
                   value="{{ request('scheduled_date') }}"
                   class="form-input mt-1 block w-full rounded border-gray-300 shadow-sm"/>
        </div>
        <div>
            <button type="submit"
                    class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                Filtrar
            </button>
        </div>
        <div>
            <a href="{{ route('admin.users.tasks', $user->id) }}"
               class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </a>
        </div>
    </form>

    <form id="bulk-delete-form" action="{{ route('admin.users.tasks.destroy', $user->id) }}" method="POST">
        @csrf
        @method('DELETE')

        <div class="flex justify-between items-center mb-2 p-4">
            <div class="flex items-start">
                <input type="checkbox" id="select-all" class="mr-2 task-checkbox mt-1">
                <label for="select-all" class="text-sm font-medium text-gray-700">Seleccionar todas</label>
            </div>
            <button type="submit"
                    onclick="return confirm('¿Estás seguro de eliminar las tareas seleccionadas?')"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition shadow">
                Eliminar Tareas seleccionadas
            </button>
        </div>

        {{-- TASKS --}}
        <div class="space-y-4">
            @forelse ($tasks as $task)
                @php
                    $totalSubtasks = $task->subtasks->count();
                    $completedSubtasks = $task->subtasks->where('status', 'completed')->count();
                    $progressPercent = $totalSubtasks > 0 ? round(($completedSubtasks / $totalSubtasks) * 100) : 0;
                    $barColor = match (true) {
                        $progressPercent < 30 => 'bg-red-500',
                        $progressPercent < 70 => 'bg-yellow-400',
                        default => 'bg-green-500',
                    };
                @endphp

                <div x-data="{ open: false }" class="bg-white rounded shadow p-4 @if($progressPercent === 100) border-2 border-green-500 @endif">
                    <div class="flex items-start justify-between mb-2">
                        <input type="checkbox" name="selected_tasks[]" value="{{ $task->id }}" class="task-checkbox mr-2">
                    </div>
                    {{-- MAIN TASK --}}
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">{{ $task->title }}</h2>
                            <h5 class="text-lm font-semibold text-gray-700">{{ $task->description }}</h5>

                            <p class="text-sm text-gray-600">
                                Estado: <span class="capitalize">{{ status_label($task->status) }}</span> ·
                                Fecha: {{ $task->scheduled_date?->format('d/m/Y') ?? 'No asignada' }}
                            </p>

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
                                            <span>{{ $completedSubtasks }} / {{ $totalSubtasks }} subtareas completadas</span>
                                            <span>{{ $progressPercent }}%</span>
                                        </div>
                                    @endif
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="h-full transition-all duration-500 {{ $barColor }}" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- EDIT --}}
                            <a href="{{ route('admin.tasks.edit', $task->id) }}" title="Editar">
                                <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                            </a>

                            {{-- VIEW PICTOGRAM --}}
                            @if ($task->pictogram_path)
                                <button
                                    @click="$dispatch('open-image', '{{ asset('storage/' . $task->pictogram_path) }}')"
                                    title="Ver pictograma"
                                    class="ml-2 text-gray-600 hover:text-gray-900"
                                >
                                    <i data-lucide="search" class="w-5 h-5"></i>
                                </button>
                            @endif

                            {{-- DELETE --}}
                            <form action="{{ route('admin.users.tasks.destroy', [$user->id, $task->id]) }}" method="POST"
                                  onsubmit="return confirm('¿Está seguro de eliminar esta tarea del usuario {{ $user->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                                </button>
                            </form>

                            {{-- VIEW SUBTASKS --}}
                            @if ($task->subtasks->count())
                                <button
                                    type="button"
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
                    <div x-show="open" x-transition.duration.300ms x-cloak class="mt-4 bg-indigo-50 p-3 rounded">
                        <ul class="space-y-2">
                            @foreach ($task->subtasks as $subtask)
                                <li class="flex justify-between items-start border-b border-indigo-100 pb-2">
                                    <div class="flex items-start gap-2">
                                        <i data-lucide="corner-down-right" class="w-4 h-4 text-indigo-600 mt-1"></i>
                                        <div>
                                            {{-- TITLE --}}
                                            <p class="text-base font-semibold text-gray-800 leading-tight">{{ $subtask->title }}</p>

                                            {{-- SUBTASK PICTOGRAM --}}
                                            @if ($subtask->pictogram_path)
                                                <div class="mt-1">
                                                    <img src="{{ asset('storage/' . $subtask->pictogram_path) }}" alt="Pictograma de {{ $subtask->title }}" class="w-10 h-10 object-contain rounded">
                                                </div>
                                            @endif

                                            {{-- DESCRIPTION --}}
                                            <p class="text-sm text-gray-600 mt-1">{{ $subtask->description }}</p>

                                            {{-- STATUS AND DATE --}}
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
            @empty
                <div class="text-center text-gray-600 bg-white rounded p-6 shadow">
                    @if (! $hasAnyTasks)
                        Este usuario no tiene tareas asignadas.
                    @else
                        No hay tareas con esos criterios.
                    @endif
                </div>
            @endforelse
            </form>
        </div>

    {{-- PAGINATION --}}
    @if ($tasks->hasPages())
        <div class="mt-6">
            {{ $tasks->appends(request()->query())->links('vendor.pagination.tailwind') }}
        </div>
    @endif

    {{-- BACK BUTTON --}}
    <div class="mt-8">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition shadow">
            ← Volver a usuarios
        </a>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
    @push('scripts')
        <script src="{{ asset('js/task.js') }}"></script>
    @endpush
@endsection

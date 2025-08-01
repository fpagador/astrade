@extends('layouts.app')

@section('title', 'Crear Tarea')

@section('content')
    <div
        id="task-form-container"
        class="max-w-3xl mx-auto bg-white p-8 rounded shadow"
        x-data="cloneTaskForm()"
        x-init="init()"
        data-fetch-url="{{ url('/admin/users/task') }}"
        data-asset="{{ asset('storage') }}"
    >
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">
                Crear Tarea para {{ $user->name }}
            </h1>

            <button
                type="button"
                @click="showClone = !showClone"
                class="inline-block px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800"
            >
                Clonar tarea existente
            </button>
        </div>

        <div x-show="showClone" x-cloak class="mb-6">
            <label for="task-cloner" class="block text-sm font-medium text-gray-700 mb-1">
                Buscar tarea para clonar
            </label>
            <select
                id="task-cloner"
                class="form-select w-full max-w-full border-0 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
            >
                <option value=""></option>
                @foreach($existingTasks as $task)
                    <option value="{{ $task->id }}">
                        {{ $task->title }} — {{ $task->user?->name ?? 'Sin usuario' }}
                    </option>
                @endforeach
            </select>
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
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

        {{-- FORM --}}
        <form action="{{ route('admin.tasks.store', ['id' => $user->id]) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="assigned_by" value="{{ auth()->id() }}">

            {{-- TITLE --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-indigo-500 focus:outline-none" required>
            </div>

            {{-- DESCRIPTION --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" id="description" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-indigo-500 focus:outline-none">{{ old('description') }}</textarea>
            </div>

            {{-- PLANNING --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="scheduled_date" class="form-input w-full" value="{{ old('scheduled_date') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                    <input type="time" name="scheduled_time" class="form-input w-full" value="{{ old('scheduled_time') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración estimada (min)</label>
                    <input type="number" name="estimated_duration_minutes" min="1" class="form-input w-full" value="{{ old('estimated_duration_minutes') }}">
                </div>
            </div>

            {{-- PICTOGRAM --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pictograma</label>
                <div class="relative group h-20 aspect-square inline-block">
                    <img
                        id="pictogram-preview"
                        class="h-full max-w-full object-contain rounded cursor-pointer transition group-hover:brightness-110 hidden"
                    />
                </div>
                <input type="file" name="pictogram" accept="image/*" class="form-input w-full">
            </div>

            {{-- ORDER AND STATE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                    <input type="number" name="order" min="0" class="form-input w-full" value="{{ old('order', 0) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" class="form-select w-full">
                        <option value="">Seleccionar</option>
                        @foreach(\App\Enums\TaskStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ status_label($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- RECURRENT --}}
            <div class="border-t pt-6 mt-6">
                <label class="inline-flex items-center mb-4">
                    <input type="hidden" name="is_recurrent" value="0">
                    <input type="checkbox" x-model="recurrent" name="is_recurrent" value="1" class="form-checkbox text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">¿Tarea recurrente?</span>
                </label>
                <div x-show="recurrent" x-cloak class="space-y-4 bg-gray-50 p-4 rounded border border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Días de la semana</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-600">
                            @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $day)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="form-checkbox text-indigo-600">
                                    <span class="ml-2 capitalize">{{ __($day) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                            <input type="date" name="recurrent_start_date" class="form-input w-full" value="{{ old('recurrent_start_date') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin (opcional)</label>
                            <input type="date" name="recurrent_end_date" class="form-input w-full" value="{{ old('recurrent_end_date') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBTASKS --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Subtareas</h2>
                <template x-for="(subtask, index) in subtasks" :key="index">
                    <div class="relative bg-gray-50 p-4 rounded border mb-4">
                        <button type="button" class="absolute top-2 right-2 text-red-500" @click="removeSubtask(index)">✕</button>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Título</label>
                            <input type="text" :name="'subtasks['+index+'][title]'" class="form-input w-full" x-model="subtask.title" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea :name="'subtasks['+index+'][description]'" class="form-textarea w-full" rows="2" x-model="subtask.description"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nota</label>
                            <textarea :name="'subtasks['+index+'][note]'" class="form-textarea w-full" rows="2" x-model="subtask.note"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pictograma</label>
                            <input type="file" :name="'subtask_files['+index+']'" class="form-input w-full" accept="image/*">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Orden</label>
                                <input type="number" :name="'subtasks['+index+'][order]'" class="form-input w-full" x-model="subtask.order">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select :name="'subtasks['+index+'][status]'" class="form-select w-full" x-model="subtask.status">
                                    <option value="">Seleccionar</option>
                                    @foreach(\App\Enums\TaskStatus::cases() as $status)
                                        <option value="{{ $status->value }}">{{ status_label($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
                <button type="button" class="inline-flex items-center bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-500" @click="addSubtask()">
                    + Añadir Subtarea
                </button>
            </div>

            {{-- ACTIONS --}}
            <div class="flex justify-end space-x-4 pt-6">
                <a href="{{ route('admin.users.tasks', $user->id) }}" class="inline-block px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </a>
                <button type="submit" class="inline-block px-4 py-2 bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Guardar
                </button>
            </div>
        </form>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

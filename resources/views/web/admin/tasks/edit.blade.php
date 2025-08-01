@extends('layouts.app')

@section('title', 'Editar Tarea')

@section('content')
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6 text-gray-800">Editar Tarea para {{ $task->user->name }}</h1>

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

        <form action="{{ route('admin.tasks.update', $task->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="user_id" value="{{ $task->user_id }}">
            <input type="hidden" name="assigned_by" value="{{ $task->assigned_by }}">

            {{-- TITLE --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-indigo-500 focus:outline-none"
                       required>
            </div>

            {{-- DESCRIPTION --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" id="description" rows="4"
                          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $task->description) }}</textarea>
            </div>

            {{-- PLANNING DETAILS --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date', optional($task->scheduled_date)->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                    <input type="time" name="scheduled_time" value="{{ old('scheduled_time', optional($task->scheduled_time)->format('H:i')) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración estimada (min)</label>
                    <input type="number" name="estimated_duration_minutes" min="1" class="form-input w-full"
                           value="{{ old('estimated_duration_minutes', $task->estimated_duration_minutes) }}">
                </div>
            </div>

            {{-- PICTOGRAM --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pictograma</label>

                @if ($task->pictogram_path)
                    <div class="relative group h-20 aspect-square inline-block">
                        <img
                            src="{{ asset('storage/' . $task->pictogram_path) }}"
                            @click="$dispatch('open-image', '{{ asset('storage/' . $task->pictogram_path) }}')"
                            title="Ver Pictograma actual"
                            class="h-full max-w-full object-contain rounded cursor-pointer transition group-hover:brightness-110"
                        />

                        {{-- Icono de lupa al hacer hover --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded opacity-0 group-hover:opacity-100 transition pointer-events-none">
                            <i data-lucide="search" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                @endif
                <input type="file" name="pictogram" accept="image/*" class="form-input w-full mt-2">
            </div>

            {{-- ORDER AND STATE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                    <input type="number" name="order" min="0" class="form-input w-full" value="{{ old('order', $task->order ?? 0) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" class="form-select w-full">
                        <option value="">Seleccionar</option>
                        @foreach(\App\Enums\TaskStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', $task->status) === $status->value ? 'selected' : '' }}>
                                {{ status_label($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- RECURRENT --}}
            <div x-data="{ recurrent: {{ old('is_recurrent', $task->recurrentTask ? 'true' : 'false') }} }" class="border-t pt-6 mt-6">
                <label class="inline-flex items-center mb-4">
                    {{-- Hidden para enviar siempre el valor 0 cuando está desmarcado --}}
                    <input type="hidden" name="is_recurrent" value="0">

                    <input type="checkbox" x-model="recurrent" name="is_recurrent" value="1" class="form-checkbox text-indigo-600"
                        {{ old('is_recurrent', $task->is_recurrent) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-700">¿Tarea recurrente?</span>
                </label>

                <div x-show="recurrent" x-cloak class="space-y-4 bg-gray-50 p-4 rounded border border-gray-200">
                    {{-- Días de la semana --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Días de la semana</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-600">
                            @php
                                $days = old('days_of_week', $task->recurrentTask?->days_of_week ?? []);
                            @endphp
                            @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $day)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $day }}"
                                           class="form-checkbox text-indigo-600"
                                        {{ in_array($day, $days) ? 'checked' : '' }}>
                                    <span class="ml-2 capitalize">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- DATES --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                            <input type="date" name="recurrent_start_date" class="form-input w-full"
                                   value="{{ old('recurrent_start_date', optional($task->recurrentTask?->start_date)->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin (opcional)</label>
                            <input type="date" name="recurrent_end_date" class="form-input w-full"
                                   value="{{ old('recurrent_end_date', optional($task->recurrentTask?->end_date)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBTASKS --}}
            <div x-data="editTaskForm({{ Js::from(old('subtasks', $subtasksArray)) }})" class="mt-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Subtareas</h2>
                <template x-for="(subtask, index) in subtasks" :key="index">
                    <div class="relative bg-gray-50 p-4 rounded border border-gray-200 mb-4 space-y-3">
                        <template x-if="subtasks.length > 1">
                            <button type="button"
                                    class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-sm"
                                    @click="removeSubtask(index)">
                                ✕
                            </button>
                        </template>

                        <input type="hidden" :name="`subtasks[${index}][id]`" :value="subtask.id ?? ''">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Título</label>
                            <input type="text" class="form-input w-full" required :name="`subtasks[${index}][title]`" x-model="subtask.title">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea class="form-textarea w-full" rows="2" :name="`subtasks[${index}][description]`" x-model="subtask.description"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nota</label>
                            <textarea class="form-textarea w-full" rows="2" :name="`subtasks[${index}][note]`" x-model="subtask.note"></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Orden</label>
                                <input type="number" class="form-input w-full" :name="`subtasks[${index}][order]`" x-model="subtask.order">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select class="form-select w-full" :name="`subtasks[${index}][status]`" x-model="subtask.status">
                                    <option value="">Seleccionar</option>
                                    @foreach(\App\Enums\TaskStatus::cases() as $status)
                                        <option value="{{ $status->value }}">{{ status_label($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-data="{ storageBaseUrl: '{{ asset('storage') }}' }">
                            <label class="block text-sm font-medium text-gray-700">Pictograma actual:</label>

                            <template x-if="subtask.pictogram_path">
                                <div class="mb-2 relative group w-20 aspect-square">
                                    <img
                                        :src="`${storageBaseUrl}/${subtask.pictogram_path}`"
                                        class="w-full h-full object-contain rounded cursor-pointer transition group-hover:brightness-110"
                                        @click="window.dispatchEvent(new CustomEvent('open-image', { detail: `${storageBaseUrl}/${subtask.pictogram_path}` }))"
                                        title="Ver Pictograma actual"
                                    />

                                    <!-- Overlay lupa -->
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                        <i data-lucide="search" class="w-5 h-5 text-white"></i>
                                    </div>
                                </div>
                            </template>

                            <input
                                type="file"
                                :name="`subtask_pictograms[${subtask.id ?? 'new_' + index}]`"
                                accept="image/*"
                            >
                        </div>
                    </div>
                </template>
                <button type="button"
                        class="inline-flex items-center bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-500"
                        @click="addSubtask()">
                    + Añadir Subtarea
                </button>
            </div>

            {{-- ACTIONS --}}
            <div class="flex justify-end space-x-4 pt-6">
                <a href="{{ route('admin.users.tasks', $task->user_id) }}"
                   class="inline-block px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-block px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

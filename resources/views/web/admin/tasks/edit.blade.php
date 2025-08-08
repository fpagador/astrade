@extends('layouts.app')

@section('title', 'Editar Tarea')

@section('content')
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6 text-gray-800">Editar Tarea para {{ $task->user->name }}</h1>

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        <x-form.form-wrapper action="{{ route('admin.tasks.update', $task->id) }}" method="PUT" class="space-y-6">

            <input type="hidden" name="user_id" value="{{ $task->user_id }}">
            <input type="hidden" name="assigned_by" value="{{ $task->assigned_by }}">

            {{-- TITLE --}}
            <x-form.input
                label="Título"
                name="title"
                value="{{ old('title', $task->title) }}"
                required
            />

            {{-- DESCRIPTION --}}
            <x-form.textarea
                label="Descripción"
                name="description"
                rows="4"
            >
                {{ old('description', $task->description) }}
            </x-form.textarea>

            {{-- PLANNING DETAILS --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form.input type="date" name="scheduled_date" label="Fecha"
                              value="{{ old('scheduled_date', optional($task->scheduled_date)->format('Y-m-d')) }}" />

                <x-form.input type="time" name="scheduled_time" label="Hora"
                              value="{{ old('scheduled_time', optional($task->scheduled_time)->format('H:i')) }}" />

                <x-form.input type="number" name="estimated_duration_minutes" label="Duración estimada (min)"
                              min="1"
                              value="{{ old('estimated_duration_minutes', $task->estimated_duration_minutes) }}" />
            </div>

            {{-- PICTOGRAM AND STATE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-form.file label="Pictograma" name="pictogram" accept="image/*" />

                    @if ($task->pictogram_path)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $task->pictogram_path) }}"
                                 @click="$dispatch('open-image', '{{ asset('storage/' . $task->pictogram_path) }}')"
                                 class="h-20 w-20 object-contain rounded cursor-pointer transition hover:brightness-110"
                                 title="Ver pictograma actual">
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" class="form-select w-full mt-2">
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
                <x-form.checkbox name="is_recurrent" x-model="recurrent" value="1" label="¿Tarea recurrente?" />

                <div x-show="recurrent" x-cloak class="space-y-4 bg-gray-50 p-4 rounded border border-gray-200">
                    {{-- Days of the week --}}
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
                        <x-form.input type="date" name="recurrent_start_date" label="Fecha de inicio"
                                      value="{{ old('recurrent_start_date', optional($task->recurrentTask?->start_date)->format('Y-m-d')) }}" />
                        <x-form.input type="date" name="recurrent_end_date" label="Fecha de fin (opcional)"
                                      value="{{ old('recurrent_end_date', optional($task->recurrentTask?->end_date)->format('Y-m-d')) }}" />
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

            {{-- BUTTONS --}}
            <x-form.button-group submit-text="Actualizar" />
        </x-form.form-wrapper>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

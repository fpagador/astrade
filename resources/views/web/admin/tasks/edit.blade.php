@extends('layouts.app')

@section('title', 'Editar Tarea')

@section('content')
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6 text-gray-800">Editar Tarea para {{ $task->user->name }}</h1>

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        <x-form.form-wrapper action="{{ route('admin.tasks.update', $task->id) }}" method="PUT" class="space-y-6">
            <input type="hidden" name="date" value="{{ $date }}">
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
                              value="{{ old('scheduled_date', optional($task->scheduled_date)->format('Y-m-d')) }}" required />

                <x-form.input type="time" name="scheduled_time" label="Hora"
                              value="{{ old('scheduled_time', optional($task->scheduled_time)->format('H:i')) }}" required />

                <x-form.input type="number" name="estimated_duration_minutes" label="Duración estimada (min)"
                              min="1"
                              value="{{ old('estimated_duration_minutes', $task->estimated_duration_minutes) }}" />
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
                <input type="file" name="pictogram" accept="image/*" class="form-input w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>

            {{-- COLOR AND STATE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="color"
                        id="color-input"
                        class="w-full h-10 rounded focus:outline-none border"
                        value="{{ old('color', $task->color ?? '#FFFFFF') }}"
                        style="background-color: {{ old('color', $task->color ?? '#FFFFFF') }}; color: transparent;
                        {{ (old('color', $task->color ?? '#FFFFFF') === '#FFFFFF') ? 'border: 1px solid #ccc;' : 'border: none;' }}"
                    >

                    <div class="flex flex-wrap justify-center gap-2 mt-2">
                        @foreach($colors as $c)
                            <div
                                class="color-swatch w-8 h-8 rounded cursor-pointer border border-gray-300"
                                style="background-color: {{ $c }};"
                                data-color="{{ $c }}"
                            ></div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                    <select name="status" class="form-select w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400" required>
                        @foreach(\App\Enums\TaskStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', $task->status) == $status->value ? 'selected' : '' }}>
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
            <div
                x-data="editTaskForm({{ Js::from(old('subtasks', $subtasksArray)) }})"
                class="mt-8"
            >
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Subtareas</h2>
                <div x-ref="subtasksContainer">
                    <template x-for="(subtask, index) in subtasks" :key="subtask.id ?? index">
                        <div
                            class="relative bg-gray-50 p-4 rounded border mb-4 flex items-center gap-3 subtask"
                            @dragstart="dragStart($event, index)"
                            @dragover.prevent="dragOver"
                            @drop.prevent="drop($event, index)"
                        >
                            <button
                                type="button"
                                class="absolute top-2 right-2 text-red-500"
                                @click="removeSubtask(index)"
                            >✕</button>

                            <div class="drag-handle cursor-move p-2 bg-gray-300 rounded select-none" draggable="true" title="Arrastrar">☰</div>

                            <input type="hidden" :name="`subtasks[${index}][id]`" :value="subtask.id ?? ''">
                            <input type="hidden" :name="`subtasks[${index}][order]`" :value="index">

                            <div class="flex-1 space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Título <span class="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        :name="`subtasks[${index}][title]`"
                                        class="form-input w-full"
                                        x-model="subtask.title"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                                    <textarea
                                        :name="`subtasks[${index}][description]`"
                                        class="form-textarea w-full"
                                        rows="2"
                                        x-model="subtask.description"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nota</label>
                                    <textarea
                                        :name="`subtasks[${index}][note]`"
                                        class="form-textarea w-full"
                                        rows="2"
                                        x-model="subtask.note"
                                    ></textarea>
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
                                        class="form-input w-full"
                                        accept="image/*"
                                    >
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado <span class="text-red-500">*</span></label>
                                        <select class="form-select w-full" :name="`subtasks[${index}][status]`" x-model="subtask.status">
                                            @foreach(\App\Enums\TaskStatus::cases() as $status)
                                                <option value="{{ $status->value }}" :selected="subtask.status == '{{ $status->value }}'">{{ status_label($status) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-500"
                    @click="addSubtask()"
                >
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

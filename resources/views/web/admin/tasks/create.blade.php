@extends('layouts.app')

@section('title', 'Crear Tarea')

@section('content')
    <div
        id="task-form-container"
        class="max-w-3xl mx-auto bg-white p-8 rounded shadow"
        x-data="cloneTaskForm(@json(session('oldSubtasks', [])))"
        x-init="init()"
        data-fetch-url="{{ url('/admin/users/task') }}"
        data-asset="{{ asset('storage') }}"
        data-conflict-check-url="{{ url('/admin/users/{userId}/tasks/check-conflict') }}"
        data-non-working-check-url="{{ url('/admin/users/{userId}/tasks/check-nonworking') }}"
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

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        <div x-show="showClone" x-cloak class="mb-6">
            <x-form.select name="task-cloner" label="Buscar tarea para clonar" :options="$existingTasks->pluck('title', 'id')->toArray()" placeholder="Seleccionar tarea" />
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- MAIN FORM --}}
        <x-form.form-wrapper
            action="{{ route('admin.users.tasks.store', ['user' => $user, 'date' => $date])  }}"
            method="POST"
            class="space-y-6">
            @csrf

            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="assigned_by" value="{{ auth()->id() }}">

            {{-- TITLE --}}
            <x-form.input name="title" label="Título" required value="{{ old('title') }}" />

            {{-- DESCRIPTION --}}
            <x-form.textarea name="description" label="Descripción" rows="4">{{ old('description') }}</x-form.textarea>

            {{-- PLANNING --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form.input name="scheduled_date" label="Fecha" type="date" value="{{ old('scheduled_date', $date) }}" required/>
                <x-form.input name="scheduled_time" label="Hora" type="time" value="{{ old('scheduled_time') }}" required/>
                <x-form.input name="estimated_duration_minutes" label="Duración estimada (min)" type="number" min="1" value="{{ old('estimated_duration_minutes') }}"/>
            </div>

            {{-- NOTIFICATIONS --}}
            <div class="items-center grid grid-cols-1 md:grid-cols-3 gap-4 items-end"
                 x-data="{ notifications: {{ old('notifications_enabled') ? 'true' : 'false' }} }"
            >
                <div class="col-span-2 flex items-center gap-2">
                    <x-form.checkbox
                        name="notifications_enabled"
                        label="Activar notificaciones para esta tarea"
                        x-model="notifications"
                        :checked="old('notifications_enabled')" />
                </div>

                <div :class="{'invisible': !notifications}">
                    <x-form.input
                        name="reminder_minutes"
                        label="Recordatorio (minutos antes)"
                        type="number"
                        min="1"
                        value="{{ old('reminder_minutes', 15) }}"
                        required
                    />
                </div>
            </div>

            {{-- PICTOGRAM AND COLOR --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-form.file
                        name="pictogram_path"
                        label="Pictograma"
                        class="  py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        preview
                    />
                </div>
                <div>
                    <label class="block font-medium mb-1 flex items-center gap-1">Color</label>
                    <input
                        type="text"
                        name="color"
                        id="color-input"
                        class="w-full h-10 rounded focus:outline-none border"
                        value="{{ old('color', $task->color ?? '#FFFFFF') }}"
                        style="background-color: {{ old('color', $task->color ?? '#FFFFFF') }}; color: transparent;
                        {{ (old('color', $task->color ?? '#FFFFFF') === '#FFFFFF') ? 'border: 1px solid #ccc;' : 'border: none;' }}"
                    />
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
            </div>

            {{-- RECURRENT --}}
            <div class="border-t pt-6 mt-6">
                <x-form.checkbox name="is_recurrent" label="¿Tarea recurrente?" x-model="recurrent" :checked="old('is_recurrent')" />

                <div x-show="recurrent" x-cloak class="space-y-4 bg-gray-50 p-4 rounded border border-gray-200">
                    <div
                        x-data="{
                            allSelected: false,
                            weekDays: {{ Js::from(array_keys($weekDays)) }},
                            toggleAll(event) {
                                let checkboxes = $el.querySelectorAll('input[name=\'days_of_week[]\']');
                                checkboxes.forEach(cb => cb.checked = event.target.checked);
                            },
                            updateAllSelected() {
                                let checkboxes = $el.querySelectorAll('input[name=\'days_of_week[]\']');
                                this.allSelected = Array.from(checkboxes).every(cb => cb.checked);
                            }
                        }"
                    >
                        {{-- Title and select all --}}
                        <div class="flex justify-between items-center mb-2">
                            <span class="block text-sm font-medium text-gray-700">Días de la semana *</span>
                            <label class="inline-flex items-center text-sm font-medium text-gray-700">
                                <span class="mr-2">Seleccionar todos</span>
                                <input
                                    type="checkbox"
                                    class="form-checkbox text-indigo-600"
                                    x-model="allSelected"
                                    @change="toggleAll($event)"
                                >
                            </label>
                        </div>

                        {{-- Days of the week --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-gray-600">
                            @foreach($weekDays as $english => $spanish)
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        name="days_of_week[]"
                                        value="{{ $english }}"
                                        class="form-checkbox text-indigo-600 mr-2"
                                        @change="updateAllSelected()"
                                    >
                                    <span class="capitalize">{{ $spanish }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBTASKS --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Subtareas</h2>
                <div x-ref="subtasksContainer">
                    <template x-for="(subtask, index) in subtasks" :key="subtask.id ?? index">
                        <div
                            class="relative bg-gray-50 p-4 rounded border mb-4 flex items-center gap-3 subtask"
                            @dragstart="dragStart($event, index)"
                            @dragover="dragOver"
                            @drop="drop($event, index)"
                        >
                            <button type="button" class="absolute top-2 right-2 text-red-500" @click="removeSubtask(index)">✕</button>

                            <!-- Handle para drag -->
                            <div class="drag-handle cursor-move p-2 bg-gray-300 rounded select-none" draggable="true" title="Arrastrar">
                                ☰
                            </div>
                            <input type="hidden" :name="'subtasks['+index+'][order]'" :value="index">
                            <div class="flex-1 space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Título *</label>
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
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" class="inline-flex items-center bg-green-600 text-white text-sm px-3 py-1.5 rounded hover:bg-green-500" @click="addSubtask()">
                    + Añadir Subtarea
                </button>
            </div>

            {{-- ACTIONS --}}
            <x-form.button-group
                submit-text="Crear"
                :cancelRoute="route('admin.users.tasks', [
                    'user' => $user->id,
                    'date' => $date,
                    'viewMode' => $viewMode
                ])"
            />
        </x-form.form-wrapper>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

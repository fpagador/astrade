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
            <x-form.select name="task-cloner" label="Buscar tarea para clonar" :options="$existingTasks->pluck('title', 'id')->toArray()" placeholder="Seleccionar tarea" />
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- MAIN FORM --}}
        <x-form.form-wrapper action="{{ route('admin.tasks.store', ['id' => $user->id])  }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="assigned_by" value="{{ auth()->id() }}">

            {{-- TITLE --}}
            <x-form.input name="title" label="Título" required />

            {{-- DESCRIPTION --}}
            <x-form.textarea name="description" label="Descripción" rows="4" />

            {{-- PLANNING --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form.input name="scheduled_date" label="Fecha" type="date" />
                <x-form.input name="scheduled_time" label="Hora" type="time" />
                <x-form.input name="estimated_duration_minutes" label="Duración estimada (min)" type="number" min="1" />
            </div>

            {{-- PICTOGRAM AND STATE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.file name="pictogram" label="Pictograma" preview />
                <x-form.select name="status" label="Estado" :options="\App\Enums\TaskStatus::cases(), true" />
            </div>

            {{-- RECURRENT --}}
            <div class="border-t pt-6 mt-6">
                <x-form.checkbox name="is_recurrent" label="¿Tarea recurrente?" x-model="recurrent" />

                <div x-show="recurrent" x-cloak class="space-y-4 bg-gray-50 p-4 rounded border border-gray-200">
                    <div>
                        {{-- Days of the week --}}
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
                        <x-form.input name="recurrent_start_date" label="Fecha de inicio" type="date" />
                        <x-form.input name="recurrent_end_date" label="Fecha de fin (opcional)" type="date" />
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
            <x-form.button-group submit-text="Crear" />
        </x-form.form-wrapper>
    </div>
    @push('modals')
        <x-admin.image-modal />
    @endpush
@endsection

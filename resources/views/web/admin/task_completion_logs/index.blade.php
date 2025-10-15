@extends('layouts.app')

@section('title', 'Logs de Finalización de Tareas')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Registro de Finalización de Tareas</h1>
    </div>

    <hr class="border-gray-300 mb-6">
    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.task_completion_logs.index') }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="user_name" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
            <input type="text" name="user_name" id="user_name" value="{{ request('user_name') }}" class="form-input w-full">
        </div>
        <div>
            <label for="task_title" class="block text-sm font-medium text-gray-700">Nombre de Tarea</label>
            <input type="text" name="task_title" id="task_title" value="{{ request('task_title') }}" class="form-input w-full">
        </div>
        <div>
            <label for="subtask_title" class="block text-sm font-medium text-gray-700">Nombre de Subtarea</label>
            <input type="text" name="subtask_title" id="subtask_title" value="{{ request('subtask_title') }}" class="form-input w-full">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 shadow">Filtrar</button>
            <a href="{{ route('admin.task_completion_logs.index') }}" class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 shadow">Limpiar</a>
        </div>
    </form>

    {{-- TABLE HEADER --}}
    <div class="grid grid-cols-4 table-header font-medium text-sm rounded-t-md px-4 py-2">
        <div><x-admin.sortable-column label="Usuario" field="name" default="true" /></div>
        <div><x-admin.sortable-column label="Tarea" field="task" /></div>
        <div><x-admin.sortable-column label="Subtarea" field="subtask" /></div>
        <div><x-admin.sortable-column label="Completado en" field="completed_at" /></div>
    </div>

    {{-- ROWS --}}
    @forelse($logs as $log)
        <div class="grid grid-cols-4 items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
            <div>
                @if($log->user)
                    <a href="{{ route('admin.users.index', ['name' => $log->user->name, 'type' => \App\Enums\UserTypeEnum::MOBILE->value]) }}"
                       class="text-indigo-700 hover:underline">
                        {{ $log->user->name }}
                    </a>

                @else
                    —
                @endif
            </div>
            <div>
                @if($log->task)
                    <a href="{{ route('admin.users.tasks', ['user' => $log->user, 'title' => $log->task->title]) }}"
                       class="text-indigo-700 hover:underline" title="Ver tarea filtrada">
                        {{ $log->task->title }}
                    </a>
                @else
                    —
                @endif
            </div>
            <div>
                {{ $log->subtask?->title ?? '—' }}
            </div>
            <div>
                {{ $log->completed_at?->format('d/m/Y H:i') ?? '—' }}
            </div>
        </div>
    @empty
        <div class="col-span-6 text-center text-sm py-6 bg-white border border-t-0 rounded-b-md">
            No hay logs de finalización disponibles.
        </div>
    @endforelse

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $logs->appends(request()->query())->links() }}
    </div>
@endsection

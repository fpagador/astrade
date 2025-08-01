@extends('layouts.app')

@section('title', 'Todas las Tareas')

@section('content')
    <h1 class="text-3xl font-semibold mb-6">Todas las Tareas</h1>
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
    {{-- FILTERS --}}
    <form method="GET" class="mb-6 flex flex-wrap gap-4">
        <input type="text" name="user" placeholder="Buscar por usuario"
               value="{{ request('user') }}"
               class="border border-gray-300 rounded px-3 py-2" />
        <input type="text" name="title" placeholder="Buscar por título"
               value="{{ request('title') }}"
               class="border border-gray-300 rounded px-3 py-2"/>
        <select name="recurrent" class="border border-gray-300 rounded px-3 py-2">
            <option value="">Todas</option>
            <option value="yes" @selected(request('recurrent') == 'yes')>Recurrentes</option>
            <option value="no" @selected(request('recurrent') == 'no')>No recurrentes</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
            Filtrar
        </button>
    </form>

    {{-- TABLE-STYLE HEADINGS --}}
    <div class="grid grid-cols-11 bg-indigo-900 text-white text-sm font-semibold px-3 py-2 rounded-t">
        <div>ID</div>
        <div>Usuario</div>
        <div>Título</div>
        <div>Descripción</div>
        <div>Fecha</div>
        <div>Hora</div>
        <div>Duración</div>
        <div>Orden</div>
        <div>Estado</div>
        <div>Recurrente</div>
        <div>Pictograma</div>
    </div>

    {{-- BOARD STYLE BODY --}}
    <div class="border border-gray-200 divide-y rounded-b shadow text-sm">
        @forelse ($query as $task)
            <div x-data="{ open: false }" class="group">

                {{-- MAIN ROW --}}
                <div class="grid grid-cols-11 px-3 py-2 hover:bg-indigo-100 cursor-pointer" @click="open = !open">
                    <div>
                        <span x-show="!open">▶</span>
                        <span x-show="open">▼</span>
                        {{ $task->id }}
                    </div>
                    <div>{{ $task->user->name }} {{ $task->user->surname }}</div>
                    <div class="font-medium">{{ $task->title }}</div>
                    <div>{{ \Illuminate\Support\Str::limit($task->description, 50) }}</div>
                    <div>{{ optional($task->scheduled_date)?->format('d/m/Y') }}</div>
                    <div>{{ optional($task->scheduled_time)?->format('H:i') }}</div>
                    <div>{{ $task->estimated_duration_minutes }}</div>
                    <div>{{ $task->order }}</div>
                    <div>
                    <span class="px-2 py-1 rounded text-xs font-semibold
                        @if($task->status == 'completed') bg-green-200 text-green-800
                        @elseif($task->status == 'in_progress') bg-blue-200 text-blue-800
                        @elseif($task->status == 'overdue') bg-red-200 text-red-800
                        @else bg-yellow-200 text-yellow-800 @endif">
                        {{ str_replace('_', ' ', $task->status) }}
                    </span>
                    </div>
                    <div class="text-center">
                        @if($task->recurrent_task_id) ✅ @else ❌ @endif
                    </div>
                    <div>
                        @if($task->pictogram_path)
                            <img src="{{ asset('storage/' . $task->pictogram_path) }}" class="w-8 h-8 object-contain" alt="Pictograma">
                        @else —
                        @endif
                    </div>
                </div>

                {{-- SUBTASKS --}}
                <div x-show="open" x-cloak class="bg-gray-50 px-5 py-4 text-sm text-gray-800">
                    <strong>Subtareas:</strong>
                    @if($task->subtasks->count())
                        <div class="grid gap-4 mt-3">
                            @foreach($task->subtasks as $subtask)
                                <div class="border rounded-md p-3 bg-white shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="text-md font-semibold">{{ $subtask->title }}</h4>
                                            <p class="text-gray-600 text-sm mb-1">{{ $subtask->description }}</p>
                                            @if($subtask->note)
                                                <p class="text-xs italic text-gray-500">Nota: {{ $subtask->note }}</p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-400">
                                                Orden: {{ $subtask->order }} ·
                                                Estado:
                                                <span class="px-2 py-0.5 rounded text-xs font-medium
                                    @if($subtask->status == 'completed') bg-green-200 text-green-800
                                    @elseif($subtask->status == 'in_progress') bg-blue-200 text-blue-800
                                    @elseif($subtask->status == 'overdue') bg-red-200 text-red-800
                                    @else bg-yellow-200 text-yellow-800
                                    @endif">
                                    {{ str_replace('_', ' ', ucfirst($subtask->status)) }}
                                </span>
                                            </p>
                                        </div>
                                        {{-- IMAGES OF THE SUBTASK --}}
                                        @if($subtask->images && $subtask->images->count())
                                            <div class="flex gap-2 flex-wrap ml-4 max-w-xs">
                                                @foreach($subtask->images as $image)
                                                    <img src="{{ asset('storage/' . $image->image_path) }}" class="w-12 h-12 rounded object-cover border" alt="Subtask Image">
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mt-1">No hay subtareas.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-4 text-center text-gray-500">No se encontraron tareas.</div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $query->appends(request()->query())->links() }}
    </div>
@endsection

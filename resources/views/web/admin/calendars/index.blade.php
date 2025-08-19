@extends('layouts.app')

@section('title', 'Calendarios Laborales')

@section('content')

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-semibold mb-6">Calendarios Laborales</h1>
                <a href="{{ route('admin.calendars.create') }}"
                   class="inline-block px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800"> Nueva Plantilla
                </a>
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('admin.calendars.index') }}"
              class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="name" id="name" value="{{ request('name') }}" class="form-input w-full">
            </div>

            <div>
                <label for="year" class="block text-sm font-medium text-gray-700">Año</label>
                <input type="number" name="year" id="year" value="{{ request('year') }}" class="form-input w-full">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="">Todos</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status', '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                    Filtrar
                </button>
                <a href="{{ route('admin.calendars.index') }}"
                   class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- TABLE HEADER --}}
        <div class="grid grid-cols-[2fr_1fr_1fr_1fr_auto] bg-indigo-900 text-white font-medium text-sm rounded-t-md px-4 py-2">
            <div>Nombre</div>
            <div>Año</div>
            <div>Estado</div>
            <div>Días Festivos</div>
            <div>Acciones</div>
        </div>

        {{-- ROWS --}}
        @forelse($templates as $template)
            <div class="grid grid-cols-[2fr_1fr_1fr_1fr_auto] items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
                <div>{{ $template->name }}</div>
                <div>{{ $template->year }}</div>
                <div>{{ \App\Enums\CalendarStatus::label(\App\Enums\CalendarStatus::from($template->status)) }}</div>
                <div>{{ $template->holidays_count }}</div>
                <div class="flex gap-2">
                    {{-- EDIT --}}
                    <a href="{{ route('admin.calendars.edit', $template) }}" title="Editar">
                        <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                    </a>
                    {{-- DELETE --}}
                    <form action="{{ route('admin.calendars.destroy', $template) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar plantilla?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Eliminar">
                            <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                        </button>
                    </form>
                </div>
            </div>

        @empty
            <div class="px-4 py-3 bg-white text-gray-500 border-b">
                No hay plantillas de calendario creadas.
            </div>
        @endforelse

        {{-- PAGINATION --}}
        <div class="mt-6">
            {{ $templates->appends(request()->query())->links() }}
        </div>
@endsection

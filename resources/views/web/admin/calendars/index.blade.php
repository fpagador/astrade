@extends('layouts.app')

@section('title', 'Calendarios Laborales')

@section('content')

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-semibold mb-6">Calendarios Laborales</h1>
                <a href="{{ route('admin.calendars.create') }}"
                   class="inline-block mb-4 px-4 py-2 rounded button-success"> Nueva Plantilla
                </a>
        </div>

        <hr class="border-gray-300 mb-6">

        {{-- ALERTS --}}
        <x-admin.alert-messages />

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('admin.calendars.index') }}"
              class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">

            <x-form.input
                name="name"
                label="Nombre"
                type="text"
                value="{{ request('name')}}"
            />

            <x-form.input
                name="year"
                label="Año"
                type="number"
                value="{{ request('year')}}"
            />

            <x-form.select
                name="status"
                label="Estado"
                :options="['' => 'Todos'] + $statuses"
                :selected="request('status', '')"
            />

            <div class="mb-4">
                <button type="submit" class="mt-1 px-4 py-2 rounded button-success shadow">Filtrar</button>
                <a href="{{ route('admin.calendars.index') }}" class="mt-1 inline-block px-4 py-2 rounded button-cancel shadow">Limpiar</a>
            </div>
        </form>

        {{-- TABLE HEADER --}}
        <div class="grid grid-cols-[2fr_1fr_1fr_2fr_1fr_auto] table-header font-medium text-sm rounded-t-md px-4 py-2">
            <div><x-admin.sortable-column label="Nombre" field="name" default="true" /></div>
            <div><x-admin.sortable-column label="Año" field="year" /></div>
            <div><x-admin.sortable-column label="Estado" field="status" /></div>
            <div>Calendario de Continuidad</div>
            <div>Días Festivos</div>
            <div>Acciones</div>
        </div>

        {{-- ROWS --}}
        @forelse($templates as $template)
            <div class="grid grid-cols-[2fr_1fr_1fr_2fr_1fr_auto] items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
                <div>{{ $template->name }}</div>
                <div>{{ $template->year }}</div>
                <div>{{ \App\Enums\CalendarStatus::label(\App\Enums\CalendarStatus::from($template->status)) }}</div>
                <div>
                    @if($template->continuityTemplate)
                        {{ $template->continuityTemplate->name }} ({{ $template->continuityTemplate->year }})
                    @else
                        <span class="text-gray-400 italic">N/A</span>
                    @endif
                </div>
                <div>{{ $template->holidays_count }}</div>
                <div class="flex gap-2">
                    {{-- VIEW --}}
                    <a href="{{ route('admin.calendars.show', $template) }}"
                       title="Ver plantilla de calendario" class="flex items-center justify-center">
                        <i data-lucide="eye" class="w-5 h-5 text-blue-600 hover:text-blue-700 transition"></i>
                    </a>

                    {{-- EDIT --}}
                    <a href="{{ route('admin.calendars.edit', $template) }}" title="Editar">
                        <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                    </a>

                    {{-- DELETE --}}
                    <form action="{{ route('admin.calendars.destroy', $template) }}" method="POST"
                          data-users="{{ $template->users()->count() }}"
                          data-confirm-delete>
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Eliminar">
                            <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-6 text-center text-sm py-6 bg-white border border-t-0 rounded-b-md">
                No hay plantillas de calendarios laborales creadas.
            </div>
        @endforelse

        {{-- PAGINATION --}}
        <div class="mt-6">
            {{ $templates->appends(request()->query())->links() }}
        </div>
@endsection

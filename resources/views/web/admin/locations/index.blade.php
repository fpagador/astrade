@extends('layouts.app')

@section('title', 'Ubicaciones')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Ubicaciones</h1>
            <a href="{{ route('admin.locations.create') }}"
               class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
                Nueva Ubicación
            </a>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="w-full bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6 text-base font-semibold">
            <strong>{{ session('success') }}</strong>
        </div>
    @endif
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
    <form method="GET" action="{{ route('admin.locations.index') }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" value="{{ request('name') }}" class="form-input w-full">
        </div>

        <div>
            <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" name="address" id="address" value="{{ request('address') }}" class="form-input w-full">
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                Filtrar
            </button>
            <a href="{{ route('admin.locations.index') }}"
               class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </a>
        </div>
    </form>

    {{-- TABLE HEADER --}}
    <div class="grid grid-cols-[2fr_3fr_3fr_1fr] bg-indigo-900 text-white font-medium text-sm rounded-t-md px-4 py-2">
        <div>Nombre</div>
        <div>Dirección</div>
        <div>Descripción</div>
        <div>Acciones</div>
    </div>

    {{-- ROWS --}}
    @foreach($locations as $location)
        <div class="grid grid-cols-[2fr_3fr_3fr_1fr] items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
            <div class="px-2">{{ $location->name }}</div>
            <div class="px-2">{{ $location->address ?? '-' }}</div>
            <div class="px-2">{{ $location->description ?? '-' }}</div>
            <div class="flex justify-center gap-2 px-2">
                {{-- EDIT --}}
                <a href="{{ route('admin.locations.edit', $location->id) }}" title="Editar">
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- DELETE --}}
                    <form action="{{ route('admin.locations.destroy', $location->id) }}" method="POST"
                          onsubmit="return confirm('¿Está seguro de eliminar esta ubicación?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Eliminar">
                            <i data-lucide="trash-2" class="w-5 h-5 text-red-600 hover:text-red-700 transition"></i>
                        </button>
                    </form>
            </div>
        </div>
    @endforeach

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $locations->appends(request()->query())->links() }}
    </div>
@endsection

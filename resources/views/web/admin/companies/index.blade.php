@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Empresas</h1>
            <a href="{{ route('admin.companies.create') }}"
               class="inline-block mb-4 px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800">
                Nueva Empresa
            </a>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.companies.index') }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
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
            <a href="{{ route('admin.companies.index') }}"
               class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </a>
        </div>
    </form>

    {{-- TABLE HEADER --}}
    <div class="grid grid-cols-[2fr_3fr_3fr_3fr_1fr] bg-indigo-900 text-white font-medium text-sm rounded-t-md px-4 py-2">
        <div><x-admin.sortable-column label="Nombre" field="name" default="true" /></div>
        <div><x-admin.sortable-column label="Dirección" field="address" /></div>
        <div><x-admin.sortable-column label="Descripción" field="description" /></div>
        <div>Teléfonos</div>
        <div>Acciones</div>
    </div>

    {{-- ROWS --}}
    @foreach($companies as $company)
        <div class="grid grid-cols-[2fr_3fr_3fr_3fr_1fr] items-center px-4 py-3 border-b hover:bg-indigo-50 text-sm bg-white">
            <div class="px-2">{{ $company->name }}</div>
            <div class="px-2">{{ $company->address ?? '-' }}</div>
            <div class="px-2">{{ $company->description ?? '-' }}</div>
            <div class="px-2 space-y-1">
                @foreach($company->phones as $phone)
                    <div>
                        <span class="font-semibold">{{ $phone->name }}:</span> {{ $phone->phone_number }}
                    </div>
                @endforeach
            </div>
            <div class="flex justify-center gap-2 px-2">
                {{-- EDIT --}}
                <a href="{{ route('admin.companies.edit', $company->id) }}" title="Editar">
                    <i data-lucide="pencil" class="w-5 h-5 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- DELETE --}}
                    <form action="{{ route('admin.companies.destroy', $company->id) }}"
                          method="POST"
                          data-message="¿Está seguro de eliminar esta empresa?"
                          class="delete-form"
                    >
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
        {{ $companies->appends(request()->query())->links() }}
    </div>
@endsection

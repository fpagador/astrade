@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Empresas</h1>
            <a href="{{ route('admin.companies.create') }}"
               class="inline-block mb-4 px-4 py-2 button-success rounded ">
                Nueva Empresa
            </a>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.companies.index') }}" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <x-form.input
            name="name"
            label="Nombre"
            type="text"
            value="{{ request('name')}}"
        />

        <x-form.input
            name="address"
            label="Dirección"
            type="text"
            value="{{ request('address')}}"
        />

        <div class="mb-4">
            <button type="submit" class="mt-1 px-4 py-2 rounded button-success shadow">Filtrar</button>
            <a href="{{ route('admin.companies.index') }}" class="mt-1 inline-block px-4 py-2 rounded button-cancel shadow">Limpiar</a>
        </div>
    </form>

    {{-- TABLE HEADER --}}
    <div class="hidden md:grid grid-cols-[2fr_3fr_3fr_3fr_1fr] table-header font-medium text-sm rounded-t-md px-4 py-2">
        <div><x-admin.sortable-column label="Nombre" field="name" default="true" /></div>
        <div><x-admin.sortable-column label="Dirección" field="address" /></div>
        <div><x-admin.sortable-column label="Descripción" field="description" /></div>
        <div>Teléfonos</div>
        <div>Acciones</div>
    </div>

    {{-- ROWS --}}
    @forelse($companies as $company)
        <div class="grid grid-cols-1 md:grid-cols-[2fr_3fr_3fr_3fr_1fr] items-start md:items-center px-4 py-4 border-b hover:bg-indigo-50 text-sm bg-white gap-3">

            {{-- Name --}}
            <div>
                <span class="md:hidden font-semibold">Nombre:</span>
                {{ $company->name }}
            </div>

            {{-- Direction --}}
            <div>
                <span class="md:hidden font-semibold">Dirección:</span>
                {{ $company->address ?? '-' }}
            </div>

            {{-- Description --}}
            <div>
                <span class="md:hidden font-semibold">Descripción:</span>
                {{ $company->description ?? '-' }}
            </div>

            {{-- Phones --}}
            <div>
                <span class="md:hidden font-semibold">Teléfonos:</span>
                <div class="space-y-1">
                    @foreach($company->phones as $phone)
                        <div>
                            <span class="font-semibold">{{ $phone->name }}:</span> {{ $phone->phone_number }}
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-4 mt-4 items-center">

                {{-- EDIT --}}
                <a href="{{ route('admin.companies.edit', $company->id) }}" title="Editar">
                    <i data-lucide="pencil" class="w-6 h-6 text-indigo-800 hover:text-indigo-900 transition"></i>
                </a>

                {{-- DELETE --}}
                <form action="{{ route('admin.companies.destroy', $company->id) }}"
                      method="POST"
                      data-message="¿Está seguro de eliminar esta empresa?"
                      class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Eliminar">
                        <i data-lucide="trash-2" class="w-6 h-6 text-red-600 hover:text-red-700 transition"></i>
                    </button>
                </form>

            </div>

        </div>
    @empty
        <div class="col-span-6 text-center text-sm py-6 bg-white border border-t-0 rounded-b-md">
            No hay empresas creadas.
        </div>
    @endforelse

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $companies->appends(request()->query())->links() }}
    </div>
@endsection

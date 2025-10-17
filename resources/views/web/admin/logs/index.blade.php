@extends('layouts.app')

@section('title', 'Logs del sistema')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-semibold mb-6">Logs del sistema</h1>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- ALERTS --}}
    <x-admin.alert-messages />

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.logs.index') }}"
          class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="date_from" class="block text-sm font-medium text-gray-700">Fecha desde</label>
            <input type="text" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-input w-full" placeholder="dd/mm/yy" data-flatpickr>
        </div>

        <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700">Fecha hasta</label>
            <input type="text" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-input w-full" placeholder="dd/mm/yy" data-flatpickr>
        </div>

        <div>
            <label for="level" class="block text-sm font-medium text-gray-700">Nivel</label>
            <select name="level" id="level" class="form-select w-full">
                <option value="">Todos</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>
                        {{ ucfirst($lvl) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-gray-700">Mensaje</label>
            <input type="text" name="message" id="message" value="{{ request('message') }}"
                   class="form-input w-full">
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="mt-1 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition shadow">
                Filtrar
            </button>
            <a href="{{ route('admin.logs.index') }}"
               class="mt-1 inline-block px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition shadow">
                Limpiar
            </a>
        </div>
    </form>

    {{-- LOG TABLE --}}
    <table class="w-full text-sm">
        <thead class="table-header">
        <tr>
            <th class="px-4 py-2 text-left">Fecha Creación</th>
            <th class="px-4 py-2 text-left">Tipo</th>
            <th class="px-4 py-2 text-left">Mensaje</th>
            <th class="px-4 py-2 text-left">Contexto</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($logs as $log)
            <tr class="bg-white border-b">
                <td class="px-4 py-2">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                <td class="px-4 py-2">{{ $log->level }}</td>
                <td class="px-4 py-2">{{ $log->message }}</td>
                <td class="px-4 py-2">
                    <button onclick="toggleContext('context-{{ $log->id }}', this)"
                            class="text-blue-700 hover:underline text-sm">
                        [+] Ver
                    </button>
                </td>
            </tr>
            <tr id="context-{{ $log->id }}" class="hidden bg-gray-50">
                <td colspan="4" class="px-4 py-2">
                    <pre class="bg-gray-100 rounded p-3 text-xs overflow-auto max-h-64 whitespace-pre-wrap border border-gray-300">
                        {{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                    </pre>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-sm py-6 bg-white border border-t-0 rounded-b-md">
                    No hay registros de logs.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $logs->appends(request()->query())->links() }}
    </div>
@endsection

@push('scripts')
    <script>
        function toggleContext(id, button) {
            const row = document.getElementById(id);
            const isHidden = row.classList.contains('hidden');

            row.classList.toggle('hidden');

            //Update the button text
            if (isHidden) {
                button.innerHTML = '[-] Ocultar';
            } else {
                button.innerHTML = '[+] Ver';
            }
        }
    </script>
@endpush

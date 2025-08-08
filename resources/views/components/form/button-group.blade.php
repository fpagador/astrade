@props([
'submitText' => 'Guardar',
'cancelRoute' => url()->previous(),
'cancelText' => 'Cancelar',
])

<div class="flex space-x-4 mt-6">
    <button type="submit"
            class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 flex-1">
        {{ $submitText }}
    </button>

    <a href="{{ $cancelRoute }}"
       class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 flex-1 text-center">
        {{ $cancelText }}
    </a>
</div>

@props([
'submitText' => 'Guardar',
'cancelRoute' => null,
'cancelText' => 'Cancelar',
])

<div class="flex space-x-4 mt-6">
    <!-- Submit button-->
    <button type="submit"
            class="bg-indigo-900 text-white px-4 py-2 rounded hover:bg-indigo-800 flex-1">
        {{ $submitText }}
    </button>

    <!-- Cancel button -->
    <button type="button"
            onclick="window.location.href='{{ $cancelRoute ?? url()->previous() }}' || history.back()"
            class="bg-red-900 text-white px-4 py-2 rounded hover:bg-red-800 flex-1 text-center">
        {{ $cancelText }}
    </button>
</div>

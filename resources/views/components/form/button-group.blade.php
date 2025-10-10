@props([
'submitText' => 'Guardar',
'cancelRoute' => null,
'cancelText' => 'Cancelar',
])

<div class="flex space-x-4 mt-6">
    <!-- Submit button-->
    <button type="submit"
            class="button-success px-4 py-2 rounded flex-1">
        {{ $submitText }}
    </button>

    <!-- Cancel button -->
    <button type="button"
            onclick="window.location.href='{{ $cancelRoute ?? url()->previous() }}' || history.back()"
            class="px-4 py-2 rounded button-cancel flex-1 text-center">
        {{ $cancelText }}
    </button>
</div>

@props(['formId' => null, 'mode' => 'holiday', 'modalId' => null])

@php
    $modalId = $modalId ?? 'confirmModal_' . $formId; // ID único si no se pasa
    $title = $mode === 'vacation' ? 'Confirmar vacaciones' : 'Confirmar festivos';
    $itemLabel = $mode === 'vacation' ? 'vacaciones' : 'festivo';
@endphp

<div id="{{ $modalId }}" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6">
        <h3 class="text-xl font-semibold mb-4">{{ $title }}</h3>
        <p class="text-sm text-gray-600 mb-3">
            Has marcado los siguientes días como {{ $itemLabel }}:
        </p>
        <ul id="dateList" class="list-disc list-inside text-sm max-h-60 overflow-auto mb-6">
        </ul>
        <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 cancelBtn">
                Seguir editando
            </button>

            @if($formId)
                <button type="button"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 confirmBtn"
                        data-form-id="{{ $formId }}">
                    Confirmar y guardar
                </button>
            @endif
        </div>
    </div>
</div>

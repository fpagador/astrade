@props(['formId' => null, 'mode' => 'holiday', 'modalId' => null])

@php
    $modalId = $modalId ?? 'confirmModal_' . $formId;
    $title = match($mode) {
        'vacation' => 'Confirmar vacaciones y ausencias legales',
        'holiday' => 'Confirmar festivos',
        default => 'Confirmar selección'
    };
@endphp

<div id="{{ $modalId }}" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6">
        <h3 class="text-xl font-semibold mb-4">{{ $title }}</h3>

        @if($mode === 'vacation')
            <p class="text-sm text-gray-600 mb-2" id="vacationHeader">Has marcado los siguientes días como vacaciones:</p>
            <ul id="dateList" class="list-disc list-inside text-sm max-h-40 overflow-auto mb-4"></ul>

            <p class="text-sm text-gray-600 mb-2" id="legalHeader">Has marcado los siguientes días como ausencias legales:</p>
            <ul id="legalDateList" class="list-disc list-inside text-sm max-h-40 overflow-auto mb-4"></ul>
        @else
            <p class="text-sm text-gray-600 mb-3">Has marcado los siguientes días como festivos:</p>
            <ul id="dateList" class="list-disc list-inside text-sm max-h-60 overflow-auto mb-6"></ul>
        @endif

        <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 cancelBtn">
                Seguir editando
            </button>

            @if($formId)
                <button type="button"
                        class="px-4 py-2 button-success rounded confirmBtn"
                        data-form-id="{{ $formId }}">
                    Confirmar y guardar
                </button>
            @endif
        </div>
    </div>
</div>

@props(['title' => 'Confirmación','message' => null,'confirmLabel' => 'Aceptar','cancelLabel' => 'Cancelar','confirmAction' => null,'open' => 'open'])
<div
    x-show="{{ $open }}"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg">
        <h2 class="text-xl font-semibold mb-4">{{ $title }}</h2>

        {{-- Si pasas message como prop, lo usa. Si no, renderiza el slot --}}
        @if ($message)
            <p class="mb-6">{{ $message }}</p>
        @else
            <div class="mb-6">
                {{ $slot }}
            </div>
        @endif

        <div class="flex justify-end space-x-2">
            <button type="button" @click="{{ $open }} = false"
                    class="bg-gray-300 px-4 py-2 rounded">
                {{ $cancelLabel }}
            </button>
            <button type="button" @click="{{ $confirmAction }}"
                    class="bg-red-600 text-white px-4 py-2 rounded">
                {{ $confirmLabel }}
            </button>
        </div>
    </div>
</div>

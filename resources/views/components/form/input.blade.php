@props([
'label' => '',
'name',
'type' => 'text',
'value' => '',
'required' => false,
])

<div class="mb-4">
    @if ($label)
        <div class="flex items-center gap-1">
            <label for="{{ $name }}" class="block font-medium mb-1">
                {{ $label }}{{ $required ? ' *' : '' }}
            </label>
            @if ($attributes->get('tooltip'))
                <span
                    class="text-gray-400 cursor-pointer"
                    x-data
                    x-tooltip="{{ $attributes->get('tooltip') }}"
                >
            <i data-lucide="info" class="w-4 h-4"></i>
        </span>
            @endif
        </div>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($type !== 'file')
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
        @else
        class="block"
        @endif
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >

    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

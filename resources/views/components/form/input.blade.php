@props(['label' => '', 'name', 'type' => 'text', 'value' => '', 'required' => false, 'disabled' => false])

<div class="input-wrapper mb-4">
    @if ($label)
        <label for="{{ $name }}" class="block font-medium mb-1 flex items-center gap-1">
            {{ $label }}{{ $required ? ' *' : '' }}

            @if ($attributes->has('tooltip'))
                <x-tooltip-info
                    title="Información sobre {{ $label }}"
                    text="{{ $attributes->get('tooltip') }}"
                />
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($type !== 'file')
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 {{ $disabled ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '' }}"
        @else
        class="block"
        @endif
        {{ $required ? 'required' : '' }}
        @if($disabled) disabled @endif
        {{ $attributes }}
    >

    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

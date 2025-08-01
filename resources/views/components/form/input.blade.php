@props([
'label' => '',
'name',
'type' => 'text',
'value' => '',
'required' => false,
])

<div class="mb-4">
    @if ($label)
        <label for="{{ $name }}" class="block font-medium mb-1">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($type !== 'file') class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400" @else class="block" @endif
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
</div>

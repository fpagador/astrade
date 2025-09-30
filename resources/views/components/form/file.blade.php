@props([
'label' => '',
'name',
'accept' => null,
'required' => false,
'disabled' => false
])

<div class="mb-4">
    @if ($label)
        <label for="{{ $name }}" class="block font-medium mb-1">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif
    <input
        type="file"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $accept ? "accept=$accept" : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => 'w-full border border-gray-300 rounded px-3 py-2 ' . ($disabled ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '')
        ]) }}
        @if($disabled) disabled @endif
    />
    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

@props([
'label' => '',
'name',
'accept' => null,
'required' => false,
'readonly' => false
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
        {{ $attributes->merge(['class' => 'w-full']) }}
        @if($readonly) readonly @endif
    />
    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

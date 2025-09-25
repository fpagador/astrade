@props([
'name',
'label' => '',
'checked' => false,
'readonly' => false
])

<div class="mb-4 flex items-center space-x-2">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="1"
        {{ old($name, $checked) ? 'checked' : '' }}
        {{ $attributes->merge(['class' => 'rounded focus:ring-indigo-400'. ($readonly ? ' pointer-events-none' : '')]) }}
    />
    <label for="{{ $name }}" class="font-medium">
        {{ $label }}
    </label>

    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

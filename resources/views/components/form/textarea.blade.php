@props(['label' => '', 'name', 'required' => false, 'rows' => 4, 'disabled' => false])

<div class="mb-4">
    @if ($label)
        <label for="{{ $name }}" class="block font-medium mb-1">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 ' .
                       ($disabled ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : '')
        ]) }}
        {{ $required ? 'required' : '' }}
        @if($disabled) disabled @endif
    >{{ trim($slot) != '' ? $slot : old($name) }}</textarea>

    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

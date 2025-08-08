@props(['label' => '', 'name', 'required' => false, 'options' => [], 'selected' => null])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block font-medium mb-1">
            {{ $label }} @if($required)<span class="text-red-600">*</span>@endif
        </label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400']) }}
        @if($required) required @endif
    >
        @foreach ($options as $value => $option)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                {{ $option }}
            </option>
        @endforeach
    </select>

    @error($name)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

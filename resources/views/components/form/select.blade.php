@props([
'label' => '',
'name',
'required' => false,
'options' => [],
'selected' => null,
'placeholderOption' => null,
'readonly' => false
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block font-medium mb-1">
            {{ $label }} @if($required)<span class="text-red-600">*</span>@endif

            @if ($attributes->has('tooltip'))
                <x-tooltip-info
                    title="Información sobre {{ $label }}"
                    text="{{ $attributes->get('tooltip') }}"
                />
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400']) }}
        @if($required) required @endif
        @if($readonly) readonly @endif
    >
        {{-- Option vacía personalizada --}}
        @if($placeholderOption)
            <option value="">{{ $placeholderOption }}</option>
        @endif

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

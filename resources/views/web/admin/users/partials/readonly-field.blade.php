@props(['label', 'value'])

<div>
    <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $label }}</label>
    <input type="text" value="{{ $value }}"
           class="w-full bg-gray-100 border border-gray-300 text-gray-800 rounded-md px-3 py-2"
           disabled>
</div>

@props([
'label' => 'Imagen',
'baseId' => 'photo',
'valueBase64' => old($baseId . '_base64'),
'valueName' => old($baseId . '_name'),
'previewSize' => 'h-24 w-24',
'disabled' => false,
'currentPath' => null,
'storage' => true,
])

<!-- Hidden inputs -->
<input type="hidden" name="{{ $baseId }}_base64" id="{{ $baseId }}_base64" value="{{ $valueBase64 }}">
<input type="hidden" name="{{ $baseId }}_name" id="{{ $baseId }}_name" value="{{ $valueName }}">

<div
    x-data="imageSelector('{{ $baseId }}_base64', '{{ $baseId }}_name', '{{ $currentPath ? ($storage ? asset('storage/' . $currentPath) : $currentPath) : '' }}')"
    class="mb-4"
>
    <!-- Label -->
    <label class="block font-medium mb-1" for="{{ $baseId }}">{{ $label }}</label>

    <!-- Imagen mostrada -->
    <template x-if="imageToShow()">
        <div class="relative group {{ $previewSize }} mb-2">
            <img
                :src="imageToShow()"
                alt="Imagen actual"
                class="h-full w-full object-contain rounded cursor-pointer transition group-hover:brightness-110"
                @click="$dispatch('open-image', { src: imageToShow() })"
            />
            <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded opacity-0 group-hover:opacity-100 transition pointer-events-none">
                <i data-lucide='search' class='w-5 h-5 text-white'></i>
            </div>
        </div>
    </template>

    <!-- Botón + nombre archivo -->
    <div class="flex items-center space-x-2 mb-2">
        <label
            for="{{ $baseId }}"
            class="cursor-pointer flex-shrink-0 inline-flex items-center px-4 py-2 rounded button-success transition focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1 {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        >
            <span x-text="confirmedImageUrl ? 'Cambiar {{ strtolower($label) }}' : 'Seleccionar {{ strtolower($label) }}'"></span>
        </label>
        <span
            x-text="filename || '{{ $valueName ?? 'Ningún archivo seleccionado' }}'"
            class="text-gray-700 text-sm truncate block w-[70%]"
            :title="filename"
        ></span>
    </div>

    <!-- Input real -->
    <input
        type="file"
        name="{{ $baseId }}"
        id="{{ $baseId }}"
        x-ref="fileInput"
        class="hidden"
        accept="image/*"
        @change="previewImage($event)"
        {{ $disabled ? 'disabled' : '' }}
    />

    <!-- Modal de confirmación -->
    <x-admin.image-confirmation-modal
        @confirm.window="confirm()"
        @cancel.window="cancel()"
    />
</div>

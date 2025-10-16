@props([
'label' => '',
'name' => null,
'accept' => null,
'required' => false,
'disabled' => false,
'preview' => false,
])

<div
    x-data="{
    filename: '',
    updateFilename(event) {
      const file = event.target.files?.[0];
      this.filename = file ? file.name : '';
      // preview handling: if there's an img.preview inside this component, update it
      const img = $el.querySelector('img[data-preview]');
      if (img && file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          img.src = e.target.result;
          img.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
      } else if (img) {
        img.classList.add('hidden');
      }
    }
  }"
    class="mb-4"
>
    {{-- Label principal --}}
    @if ($label)
        <label class="block font-medium mb-1">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    {{-- Contenedor flexible, todo en una fila --}}
    <div class="flex items-center space-x-3 w-full overflow-hidden">
        {{-- Botón estilizado --}}
        <label
            for="{{ $name ?? '' }}"
            class="cursor-pointer flex-shrink-0 inline-flex items-center px-4 py-2 cursor-pointer px-4 py-2 rounded button-success transition focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1 transition {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            @click="$refs.input && $refs.input.click()"
            :disabled="{{ $disabled ? 'true' : 'false' }}"
        >
            Seleccionar archivo
        </label>

        {{-- Nombre del archivo (truncado si es largo) --}}
        <span
            x-text="filename || 'Ningún archivo seleccionado'"
            class="text-gray-600 text-sm truncate block w-[70%]"
        ></span>
    </div>

    {{-- Input oculto real --}}
    <input
        x-ref="input"
        type="file"
        @if($name) name="{{ $name }}" @endif
        {{ $accept ? "accept=$accept" : '' }}
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        class="hidden"
        @change="updateFilename"
        {{ $attributes }}
    />
    @if($name)
        @error($name)
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    @endif
</div>

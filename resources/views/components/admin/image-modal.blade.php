<div
    x-show="open"
    x-data="imageModal()"
    x-cloak
    style="background: rgba(0,0,0,0.6);"
    class="fixed inset-0 flex items-center justify-center z-50"
    @keydown.escape.window="close()"
>
    <div class="relative bg-white rounded shadow-lg max-w-3xl max-h-[80vh] p-4">
        <button
            @click="close()"
            class="absolute top-2 right-2 z-50 text-gray-700 hover:text-gray-900"
            aria-label="Cerrar imagen"
            style="padding: 0.6rem 1rem;"
        >
            ✕
        </button>
        <img
            :src="imgSrc"
            alt="Imagen ampliada"
            class="max-w-full max-h-[70vh] rounded"
        />
    </div>
</div>

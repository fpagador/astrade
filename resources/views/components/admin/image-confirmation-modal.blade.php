<div
    x-show="showConfirmModal"
    x-cloak
    style="background: rgba(0,0,0,0.6);"
    class="fixed inset-0 flex items-center justify-center z-50"
>
    <div class="bg-white p-4 rounded shadow-lg max-w-md max-h-[80vh]">
        <img :src="tempImageUrl" class="max-w-full max-h-[60vh] rounded mb-4" />
        <div class="flex justify-end space-x-2">
            <button
                @click="cancel()"
                class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                type="button"
            >
                Cancelar
            </button>
            <button
                @click="confirm()"
                class="px-4 py-2 bg-indigo-900 text-white rounded hover:bg-indigo-800"
                type="button"
            >
                Confirmar
            </button>
        </div>
    </div>
</div>

<div x-data="actionTaskModal()"
     x-show="open" x-cloak
     @open-action-modal.window="show($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-xl w-full p-6 space-y-4">
        <h2 class="text-xl font-semibold text-gray-800" x-text="title"></h2>
        <p class="text-gray-700" x-text="message"></p>
        <div class="flex gap-3 justify-end mt-4">
            <template x-for="(btn, index) in buttons" :key="index">
                <button type="button"
                        :class="btn.color"
                        class="text-white px-4 py-2 rounded font-semibold transition"
                        @click="handle(btn.action)">
                    <span x-text="btn.label"></span>
                </button>
            </template>
        </div>
    </div>
</div>

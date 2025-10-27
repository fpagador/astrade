@props(['user', 'allUsers' => []])
<div
    x-data="cloneModal(@js($allUsers))"
    @open-clone-modal.window="taskToClone = $event.detail; selectedUserId = taskToClone.user?.id; showCloneModal = true"
>
    <template x-if="showCloneModal">
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Clonar tarea</h2>

                <!-- Original user information -->
                <div class="mb-4 p-3 border rounded bg-gray-50">
                    <p class="text-gray-700">
                        <strong>Tarea original de:</strong>
                        <span x-text="`${taskToClone.user?.name ?? ''} ${taskToClone.user?.surname ?? ''}`"></span>
                    </p>
                    <p class="text-gray-600 text-sm mt-1" x-text="`Título: ${taskToClone.title ?? ''}`"></p>
                </div>

                <!-- Target user selector -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuario destino</label>
                    <select
                        id="clone-user-select"
                        x-model="selectedUserId"
                        x-ref="cloneUserSelect"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Seleccione un usuario...</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="`${user.name} ${user.surname}`"></option>
                        </template>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button
                        class="px-4 py-2 rounded button-cancel"
                        @click="showCloneModal = false"
                    >
                        Cancelar
                    </button>
                    <button
                        class="px-4 py-2 rounded button-success"
                        @click="$dispatch('clone-task', { taskId: taskToClone.id, userId: selectedUserId })"
                    >
                        Confirmar clonación
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

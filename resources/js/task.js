/*
|--------------------------------------------------------------------------
| View User Tasks -- tasks.blade.php
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.task-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});

/*
|--------------------------------------------------------------------------
| CREATE TASK -- create.blade.php
|--------------------------------------------------------------------------
*/
function formatDateToInput(dateString) {
    if (!dateString) return '';
    return dateString.split('T')[0];
}

function formatTimeToInput(timeString) {
    if (!timeString) return '';
    // Si viene en ISO, extraemos solo la hora y minutos
    // Ejemplo: "2025-08-12T15:46:00.000000Z" -> "15:46"
    const match = timeString.match(/T(\d{2}:\d{2})/);
    return match ? match[1] : timeString;
}

export function cloneTaskForm() {
    return {
        showClone: false,
        subtasks: [{ id: crypto.randomUUID(), title: '', description: '', note: '', pictogram_path: '' }],
        recurrent: false,
        submitListenerAdded: false,
        dragSrcIndex: null,

        init() {
            const el = this.$refs.subtasksContainer;
            if (!el) return;

            this.subtasks = this.subtasks.map(s => ({
                ...s,
                id: s.id || crypto.randomUUID()
            }));

            const select = document.querySelector("#task-cloner");
            if (!select.classList.contains('tomselected')) {
                new TomSelect(select, {
                    placeholder: "Buscar tarea...",
                    allowEmptyOption: false,
                    create: false,
                    maxOptions: 100,
                    searchField: ['text'],
                    onChange: (value) => {
                        if (value) this.fetchTask(value);
                    }
                });
            }
            const form = this.$el.querySelector('form');
            if (!form) return;

            if (this.submitListenerAdded) {
                return;
            }

            this.submitListenerAdded = true;

            const container = document.querySelector('#task-form-container');
            const conflictUrl = container?.dataset?.conflictCheckUrl || '';
            let checkingConflict = false;
            let conflictChecked = false;

            form.addEventListener('submit', async (e) => {
                if (conflictChecked) {
                    return;
                }
                if (checkingConflict) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                checkingConflict = true;
                const userId = form.querySelector('input[name="user_id"]').value;
                const scheduledDate = form.querySelector('input[name="scheduled_date"]').value;
                const scheduledTime = form.querySelector('input[name="scheduled_time"]').value;
                if (!scheduledDate || !scheduledTime) {
                    conflictChecked = true;
                    checkingConflict = false;
                    form.submit();
                    return;
                }
                try {
                    const url = conflictUrl.replace('{userId}', userId) + `?scheduled_date=${scheduledDate}&scheduled_time=${scheduledTime}`;
                    const response = await fetch(url);
                    const data = await response.json();
                    if (data.conflict) {
                        const proceed = confirm('Ya existe una tarea para este usuario a la misma hora. ¿Desea continuar?');
                        if (proceed) {
                            conflictChecked = true;
                            HTMLFormElement.prototype.submit.call(form);
                        } else {
                            checkingConflict = false;
                        }
                    } else {
                        conflictChecked = true;
                        HTMLFormElement.prototype.submit.call(form);
                    }
                } catch (error) {
                    console.error('Error comprobando conflicto:', error);
                    conflictChecked = true;
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        },
        addSubtask() {
            this.subtasks.push({
                id: crypto.randomUUID(),
                title: '',
                description: '',
                note: '',
                pictogram_path: ''
            });
        },

        removeSubtask(index) {
            this.subtasks.splice(index, 1);
        },
        dragStart(event, index) {
            this.dragSrcIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', index);
        },

        dragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        },
        drop(event, targetIndex) {
            event.preventDefault();
            const sourceIndex = this.dragSrcIndex;
            if (sourceIndex === null || sourceIndex === targetIndex) return;

            // Reordenamos el array subtasks
            const movedItem = this.subtasks.splice(sourceIndex, 1)[0];
            this.subtasks.splice(targetIndex, 0, movedItem);

            this.dragSrcIndex = null;
        },

        loadFromClone(task) {
            this.subtasks = JSON.parse(JSON.stringify(
                (task.subtasks || []).map(sub => ({
                    title: sub.title ?? '',
                    description: sub.description ?? '',
                    note: sub.note ?? '',
                    pictogram_path: sub.pictogram_path ?? ''
                }))
            ));
            if (this.subtasks.length === 0) {
                this.addSubtask();
            }
            this.recurrent = !!task.is_recurrent;
            document.querySelector('[name="title"]').value = task.title || '';
            document.querySelector('[name="description"]').value = task.description || '';
            document.querySelector('[name="scheduled_date"]').value = formatDateToInput(task.scheduled_date) || '';
            document.querySelector('[name="scheduled_time"]').value = formatTimeToInput(task.scheduled_time) || '';
            document.querySelector('[name="estimated_duration_minutes"]').value = task.estimated_duration_minutes || '';
            document.querySelector('[name="is_recurrent"]').checked = task.is_recurrent;
            document.querySelectorAll('input[name="days_of_week[]"]').forEach(el => el.checked = false);
            let days = task.days_of_week || [];
            if (typeof days === 'string') {
                try { days = JSON.parse(days); } catch (e) {}
            }
            days.forEach(day => {
                const el = document.querySelector(`input[name="days_of_week[]"][value="${day}"]`);
                if (el) el.checked = true;
            });
            document.querySelector('[name="recurrent_start_date"]').value = formatDateToInput(task.recurrent_start_date) || '';
            document.querySelector('[name="recurrent_end_date"]').value = formatDateToInput(task.recurrent_end_date) || '';
            if (task.pictogram_path) {
                const container = document.querySelector('#task-form-container');
                const assetBase = container?.dataset?.asset || '';
                const preview = document.querySelector('#pictogram-preview');
                if (preview) {
                    preview.src = `${assetBase}/${task.pictogram_path}`.replace(/\/+$/, '');
                    preview.classList.remove('hidden');
                    preview.addEventListener('click', () => {
                        window.dispatchEvent(new CustomEvent('open-image', { detail: preview.src }));
                    });
                }
            }
        },
        async fetchTask(taskId) {
            const container = document.querySelector('#task-form-container');
            const baseUrl = container?.dataset?.fetchUrl || '';
            try {
                const res = await fetch(`${baseUrl}/${taskId}/json`);
                const data = await res.json();
                if (!data || !data.task) return;
                this.loadFromClone(data.task);
            } catch (error) {
                console.error("Error al cargar la tarea:", error);
            }
        },
    };
}

/*
|--------------------------------------------------------------------------
| EDIT TASK -- edit.blade.php
|--------------------------------------------------------------------------
*/
export function editTaskForm(initialSubtasks = []) {
    return {
        subtasks: initialSubtasks.length ? initialSubtasks : [{ title: '', description: '', note: '', status: '', id: null }],
        dragIndex: null,

        addSubtask() {
            this.subtasks.push({ title: '', description: '', note: '',status: '', id: null });
        },
        removeSubtask(index) {
            this.subtasks.splice(index, 1);
        },
        dragStart(event, index) {
            this.dragIndex = index;
        },
        dragOver(event) {
            event.preventDefault();
        },
        drop(event, index) {
            const draggedItem = this.subtasks.splice(this.dragIndex, 1)[0];
            this.subtasks.splice(index, 0, draggedItem);
            this.dragIndex = null;
        },
    };
}

export function imageModal() {
    return {
        open: false,
        imgSrc: '',
        openModal(src) {
            this.imgSrc = src;
            this.open = true;
        },
        close() {
            this.open = false;
            this.imgSrc = '';
        },
        init() {
            window.addEventListener('open-image', event => {
                this.openModal(event.detail);
            });
        }
    }
}

/*
|--------------------------------------------------------------------------
| COLOR PICKER -- create/edit.blade.php
|--------------------------------------------------------------------------
*/
function initColorPicker() {
    const colorInput = document.getElementById('color-input');
    const swatches = document.querySelectorAll('.color-swatch');
    if (!colorInput || swatches.length === 0) return;

    const defaultColor = '#FFFFFF';

    swatches.forEach(swatch => {
        swatch.addEventListener('click', () => {
            const color = swatch.dataset.color;
            colorInput.value = color;
            colorInput.style.backgroundColor = color;

            if (color.toUpperCase() === defaultColor) {
                colorInput.style.border = '1px solid #ccc';
            } else {
                colorInput.style.border = 'none';
            }
        });
    });
}
document.addEventListener('DOMContentLoaded', initColorPicker);

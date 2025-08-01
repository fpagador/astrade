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

export function cloneTaskForm() {
    return {
        showClone: false,
        subtasks: [{ title: '', description: '', note: '', pictogram_path: '', order: 0, status: '' }],
        recurrent: false,
        init() {
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
        },
        removeSubtask(index) {
            this.subtasks.splice(index, 1);
        },
        addSubtask() {
            this.subtasks.push({ title: '', description: '', note: '', pictogram_path: '', order: 0, status: '' });
        },
        loadFromClone(task) {
            this.subtasks = JSON.parse(JSON.stringify(
                (task.subtasks || []).map(sub => ({
                    title: sub.title ?? '',
                    description: sub.description ?? '',
                    note: sub.note ?? '',
                    pictogram_path: sub.pictogram_path ?? '',
                    order: sub.order ?? 0,
                    status: sub.status ?? ''
                }))
            ));
            if (this.subtasks.length === 0) {
                this.addSubtask();
            }

            this.recurrent = !!task.is_recurrent;

            document.querySelector('[name="title"]').value = task.title || '';
            document.querySelector('[name="description"]').value = task.description || '';
            document.querySelector('[name="scheduled_date"]').value = formatDateToInput(task.scheduled_date) || '';
            document.querySelector('[name="scheduled_time"]').value = task.scheduled_time || '';
            document.querySelector('[name="estimated_duration_minutes"]').value = task.estimated_duration_minutes || '';
            document.querySelector('[name="order"]').value = task.order || '';
            document.querySelector('[name="status"]').value = task.status || '';
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
            const baseUrl = container?.dataset?.fetchUrl || '/admin/users/task';
            try {
                const res = await fetch(`${baseUrl}/${taskId}/json`);
                const data = await res.json();
                if (!data || !data.task) return;
                this.loadFromClone(data.task);
            } catch (error) {
                console.error("Error al cargar la tarea:", error);
            }
        }
    };
}

/*
|--------------------------------------------------------------------------
| EDIT TASK -- edit.blade.php
|--------------------------------------------------------------------------
*/
export function editTaskForm(initialSubtasks = []) {
    return {
        subtasks: initialSubtasks,
        addSubtask() {
            this.subtasks.push({ title: '', description: '', note: '', order: 0, status: '', pictogram_path: null });
        },
        removeSubtask(index) {
            this.subtasks.splice(index, 1);
        }
    }
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

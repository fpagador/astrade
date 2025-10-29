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
    // If it comes in ISO, we extract only the hour and minutes
    // Example: "2025-08-12T15:46:00.000000Z" -> "15:46"
    const match = timeString.match(/T(\d{2}:\d{2})/);
    return match ? match[1] : timeString;
}

export function cloneTaskForm(oldSubtasks = []) {
    return {
        showClone: false,
        taskPictogramPath: '',
        subtasks: oldSubtasks.length
            ? oldSubtasks.map(s => ({
                id: crypto.randomUUID(),
                title: s.title ?? '',
                description: s.description ?? '',
                note: s.note ?? '',
                pictogram_path: s.pictogram_path ?? ''
            }))
            : [{ id: crypto.randomUUID(), title: '', description: '', note: '', pictogram_path: '' }],
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

            this.$nextTick(() => {
                this.watchFields();
            });

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
            const nonWorkingUrl = container?.dataset?.nonWorkingCheckUrl || '';
            const nonWorkingRangeUrl = container?.dataset?.nonWorkingRangeCheckUrl || '';
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
                const isRecurrent = form.querySelector('input[name="is_recurrent"]').checked;
                const recurrentStart = form.querySelector('input[name="recurrent_start_date"]').value;
                const recurrentEnd = form.querySelector('input[name="recurrent_end_date"]').value;

                try {
                    // 1️⃣ Check non-working days
                    if (isRecurrent && recurrentStart && recurrentEnd) {
                        const response = await fetch(
                            nonWorkingRangeUrl.replace('{userId}', userId) + `?start_date=${recurrentStart}&end_date=${recurrentEnd}`
                        );
                        const data = await response.json();

                        if (data.nonWorkingDates && data.nonWorkingDates.length > 0) {
                            const proceed = await customConfirm(
                                'Algunas de las fechas en el rango recurrente seleccionado corresponden a días de vacaciones o ausencias legales. No se crearán tareas para estos días. ¿Desea continuar?'
                            );
                            if (!proceed) {
                                checkingConflict = false;
                                return;
                            }
                        }
                    } else {
                        const nonWorkingResponse = await fetch(
                            nonWorkingUrl.replace('{userId}', userId) + `?scheduled_date=${scheduledDate}`
                        );
                        const nonWorkingData = await nonWorkingResponse.json();
                        if (nonWorkingData.nonWorking) {
                            await customAlert('La fecha seleccionada corresponde a un día de vacaciones o de ausencia legal y, por lo tanto, no se pueden agregar tareas este día.');
                            checkingConflict = false;
                            return;
                        }
                    }

                    // 2️⃣ Check time conflict
                    const url = conflictUrl.replace('{userId}', userId) + `?scheduled_date=${scheduledDate}&scheduled_time=${scheduledTime}`;
                    const conflictResponse = await fetch(url);
                    const conflictData = await conflictResponse.json();
                    if (conflictData.conflict) {
                        const proceedConflict = await customConfirm('Ya existe una tarea para este usuario a la misma hora. ¿Desea continuar?');
                        if (!proceedConflict) {
                            checkingConflict = false;
                            return;
                        }
                    }

                    // 3️⃣ Submit
                    HTMLFormElement.prototype.submit.call(form);

                } catch (error) {
                    console.error('Error comprobando conflictos:', error);
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

            this.$nextTick(() => {
                const index = this.subtasks.length - 1;
                ['title', 'description', 'note'].forEach(field => {
                    const input = this.$el.querySelector(`[name="subtasks[${index}][${field}]"]`);
                    if (input) {
                        input.addEventListener('input', this.debounce(() => this.validateFormRealtime()));
                    }
                });
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
                    pictogram_path: sub.pictogram_path ?? '',
                    id: sub.id || crypto.randomUUID()
                }))
            ));

            this.$nextTick(() => {
                const container = this.$refs.subtasksContainer;
                this.subtasks.forEach((subtask, index) => {
                    const subEl = container.querySelectorAll('.subtask')[index];
                    if (!subEl) return;

                    // Asigna valor al input hidden
                    const hiddenInput = subEl.querySelector(`input[name="subtasks[${index}][pictogram_path]"]`);
                    if (hiddenInput) hiddenInput.value = subtask.pictogram_path || '';

                    // Genera preview solo si hay pictograma
                    if (subtask.pictogram_path) {
                        let img = subEl.querySelector('img[data-preview]');
                        if (!img) {
                            img = document.createElement('img');
                            img.setAttribute('data-preview', 'true');
                            img.classList.add('mt-2', 'w-16', 'h-16', 'object-cover', 'rounded', 'cursor-pointer');
                            const fileInput = subEl.querySelector('input[type="file"]');
                            if (fileInput) fileInput.after(img);
                        }
                        const assetBase = document.querySelector('#task-form-container')?.dataset.asset || '';
                        img.src = `${assetBase}/${subtask.pictogram_path}`;
                        img.classList.remove('hidden');

                        img.addEventListener('click', () => {
                            window.dispatchEvent(new CustomEvent('open-image', { detail: img.src }));
                        });

                        const fileSpan = subEl.querySelector('span[x-text]');
                        if (fileSpan) fileSpan.textContent = '';
                    }
                });
            });

            if (this.subtasks.length === 0) {
                this.addSubtask();
            }

            let days = task.days_of_week || [];
            if (typeof days === 'string') {
                try { days = JSON.parse(days); } catch (e) {}
            }
            days.forEach(day => {
                const el = document.querySelector(`input[name="days_of_week[]"][value="${day}"]`);
                if (el) el.checked = true;
            });

            this.recurrent = !!task.is_recurrent;

            document.dispatchEvent(new CustomEvent('task-loaded', {
                detail: { recurrent: this.recurrent, days: days }
            }));

            const recurrentSection = document.querySelector('[x-data*="recurrent"]');
            if (recurrentSection && recurrentSection.__x) {
                recurrentSection.__x.$data.toggleScheduledDate();
            }

            const scheduledInput = document.querySelector('[name="scheduled_date"]');
            if (scheduledInput) {
                if (scheduledInput._flatpickr) {
                    scheduledInput._flatpickr.clear();
                    scheduledInput.disabled = this.recurrent;
                    scheduledInput.required = !this.recurrent;
                    scheduledInput.classList.toggle('opacity-50', this.recurrent);
                    scheduledInput.classList.toggle('cursor-not-allowed', this.recurrent);
                    if (scheduledInput._flatpickr) scheduledInput._flatpickr.set('clickOpens', !this.recurrent);
                } else {
                    scheduledInput.value = '';
                }
            }

            document.querySelector('[name="title"]').value = task.title || '';
            document.querySelector('[name="description"]').value = task.description || '';
            document.querySelector('[name="scheduled_date"]').value = task.is_recurrent ? '' : formatDateToInput(task.scheduled_date) || '';
            document.querySelector('[name="scheduled_time"]').value = formatTimeToInput(task.scheduled_time) || '';
            document.querySelector('[name="estimated_duration_minutes"]').value = task.estimated_duration_minutes || '';
            document.querySelector('[name="is_recurrent"]').checked = task.is_recurrent;
            document.querySelectorAll('input[name="days_of_week[]"]').forEach(el => el.checked = false);
            document.dispatchEvent(new CustomEvent('task-loaded', {
                detail: { recurrent: this.recurrent, days: days }
            }));
            if (this.recurrent) {
                const start = document.querySelector('[name="recurrent_start_date"]');
                const end = document.querySelector('[name="recurrent_end_date"]');
                if (start?._flatpickr) start._flatpickr.clear();
                else start.value = '';
                if (end?._flatpickr) end._flatpickr.clear();
                else end.value = '';
            }

            if (task.pictogram_path) {
                this.taskPictogramPath = task.pictogram_path;
                const container = document.querySelector('#task-form-container');
                const assetBase = container?.dataset.asset || '';
                let preview = container.querySelector('img[data-preview]');

                if (!preview) {
                    preview = document.createElement('img');
                    preview.setAttribute('data-preview', 'true');
                    preview.classList.add('mt-2', 'w-20', 'h-20', 'object-cover', 'rounded', 'cursor-pointer');
                    container.querySelector('input[name="pictogram_path"]').after(preview);
                }

                preview.src = `${assetBase}/${task.pictogram_path}`;
                preview.classList.remove('hidden');

                const fileSpan = container.querySelector('span[x-text]');
                if (fileSpan) fileSpan.textContent = '';

                preview.addEventListener('click', () => {
                    window.dispatchEvent(new CustomEvent('open-image', { detail: preview.src }));
                });

                const inputHidden = container.querySelector('input[name="pictogram_path_hidden"]');
                if (!inputHidden) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'pictogram_path';
                    hidden.value = task.pictogram_path;
                    container.querySelector('input[name="pictogram_path"]').after(hidden);
                } else {
                    inputHidden.value = task.pictogram_path;
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

        validateFormRealtime() {
            const form = this.$el.querySelector('form');
            if (!form) return;

            const container = this.$el;
            const baseUrl = container?.dataset?.validateTaskForm || '';

            const formData = new FormData(form);
            fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
                .then(async res => {
                    form.querySelectorAll('.realtime-error').forEach(el => el.remove());

                    if (!res.ok) {
                        const data = await res.json();
                        if (data.errors) {
                            Object.keys(data.errors).forEach(fieldName => {
                                let inputName = fieldName.replace(/\.(\d+)/g, '[$1]');
                                inputName = inputName.replace(/\.([^\.\]]+)$/,'[$1]');

                                if(fieldName === 'days_of_week') {
                                    let container = form.querySelector('#days-of-week-error');
                                    if(container){
                                        container.textContent = data.errors[fieldName][0];
                                    }
                                    return;
                                }

                                const inputs = form.querySelectorAll(`[name="${inputName}"]`);
                                if(inputs.length > 0){
                                    inputs.forEach(input => {
                                        const wrapper = input.closest('.flatpickr-wrapper') || input.parentNode;

                                        const existing = input.parentNode.querySelector('.realtime-error');
                                        if (existing) existing.remove();

                                        const p = document.createElement('p');
                                        p.className = 'text-red-600 text-sm mt-1 realtime-error';
                                        p.textContent = data.errors[fieldName][0];
                                        wrapper.appendChild(p);
                                    });
                                } else {
                                    // fallback al contenedor general
                                    let container = form.querySelector('#general-errors');
                                    if(!container){
                                        container = document.createElement('div');
                                        container.id = 'general-errors';
                                        container.className = 'text-red-600 text-sm mb-2';
                                        form.prepend(container);
                                    } else {
                                        container.textContent = '';
                                    }
                                    container.textContent = data.errors[fieldName][0];
                                }
                            });
                        }
                    }
                })
                .catch(err => console.error('Error validando en tiempo real:', err));
        },

        watchFields() {
            if(this.listenersAdded) return;
            this.listenersAdded = true;

            const form = this.$el.querySelector('form');
            if (!form) return;

            const fields = [
                'title',
                'scheduled_date',
                'scheduled_time',
                'estimated_duration_minutes',
                'reminder_minutes',
                'recurrent_start_date',
                'recurrent_end_date',
                'days_of_week'
            ];

            fields.forEach(name => {
                const input = form.querySelector(`[name="${name}"]`);
                if(input){
                    input.addEventListener('input', this.debounce(() => this.validateFormRealtime()));
                }
            });

            // Subtasks iniciales
            this.subtasks.forEach((subtask, index) => {
                const titleInput = form.querySelector(`[name="subtasks[${index}][title]"]`);
                if(titleInput){
                    titleInput.addEventListener('input', this.debounce(() => this.validateFormRealtime()));
                }
            });
        },

        debounce(fn, delay = 300) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), delay);
            };
        }
    };
}

export function cloneModal(allUsers = []) {
    return {
        showCloneModal: false,
        taskToClone: {},
        selectedUserId: null,
        users: allUsers,
        init() {
            this.$watch('showCloneModal', (value) => {
                if (value) {
                    this.$nextTick(() => this.initCloneUserSelect());
                }
            });
        },
        initCloneUserSelect() {
            const select = this.$refs.cloneUserSelect;
            if (select && !select.classList.contains('tomselected')) {
                new TomSelect(select, {
                    placeholder: 'Buscar usuario...',
                    allowEmptyOption: true,
                    create: false,
                    maxOptions: 500,
                    searchField: ['text', 'value']
                });
            } else {
                console.warn('No se encontró el select o ya estaba inicializado');
            }
        }
    };
}

/*
|--------------------------------------------------------------------------
| CREATE/EDIT TASK -- create/edit.blade.php
|--------------------------------------------------------------------------
*/
export function editTaskForm(initialSubtasks = []) {
    return {
        subtasks: initialSubtasks.length
            ? initialSubtasks
            : [{ title: '', description: '', note: '', status: '', id: null }],
        dragIndex: null,
        addSubtask() {
            this.subtasks.push({ title: '', description: '', note: '', status: '', id: null });
        },
        removeSubtask(index) {
            this.subtasks.splice(index, 1);
        },
        dragStart(event, index) {
            if (!event.target.classList.contains('drag-handle')) {
                event.preventDefault();
                return;
            }

            this.dragIndex = index;

            const draggedElement = event.target.closest('.subtask');
            const clone = draggedElement.cloneNode(true);
            clone.style.position = 'absolute';
            clone.style.top = '-9999px';
            clone.style.left = '-9999px';
            document.body.appendChild(clone);

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setDragImage(clone, event.offsetX, event.offsetY);

            draggedElement.classList.add('opacity-50', 'shadow-lg');

            setTimeout(() => document.body.removeChild(clone), 0);
        },
        dragOver(event) {
            event.preventDefault();
        },
        drop(event, index) {
            const draggedItem = this.subtasks.splice(this.dragIndex, 1)[0];
            this.subtasks.splice(index, 0, draggedItem);
            this.dragIndex = null;

            document.querySelectorAll('.subtask').forEach(el => {
                el.classList.remove('opacity-50', 'shadow-lg');
            });
        },
        dragEnd(event) {
            document.querySelectorAll('.subtask').forEach(el => {
                el.classList.remove('opacity-50', 'shadow-lg');
            });
            this.dragIndex = null;
        }
    }
}

export function imageModal() {
    return {
        open: false,
        imgSrc: '',
        context: '',
        openModal(srcOrObj) {
            if (typeof srcOrObj === 'string') {
                this.imgSrc = srcOrObj;
            } else {
                this.imgSrc = srcOrObj.src;
                this.context = srcOrObj.type;
            }
            this.open = true;
        },
        close() {
            this.open = false;
            this.imgSrc = '';
            this.context = '';
        },
        init() {
            window.addEventListener('open-image', event => {
                this.openModal(event.detail);
            });
        }
    }
}

export function actionTaskModal() {
    return {
        open: false,
        title: '',
        message: '',
        buttons: [],
        taskId: null,
        userId: null,
        type: null,
        isRecurrent: false,
        callback: null,

        show({ taskId, userId, type, editUrl, deleteUrl, isRecurrent }) {
            this.taskId = taskId;
            this.userId = userId;
            this.type = type;
            this.isRecurrent = isRecurrent ?? false;

            if (type === 'edit') {
                this.title = 'Tarea recurrente';
                this.message = 'Está intentando editar una tarea recurrente. ¿Desea modificar solo esta tarea o toda la serie?';
                this.buttons = [
                    { label: 'Modificar solo esta tarea', action: 'single', color: 'px-4 py-2 rounded button-success' },
                    { label: 'Modificar la serie', action: 'series', color: 'px-4 py-2 rounded button-extra' },
                    { label: 'Cancelar', action: 'cancel', color: 'px-4 py-2 rounded button-cancel' },
                ];
                this.callback = (action) => {
                    if (action !== 'cancel') {
                        this.open = false;
                        const url = new URL(editUrl, window.location.origin);
                        url.searchParams.set('edit_series', action === 'series' ? 1 : 0);
                        window.location.href = url.toString();
                    }
                };
            }

            if (type === 'delete') {
                this.title = 'Eliminar tarea';
                if (this.isRecurrent) {
                    this.message = 'Está intentando eliminar una tarea recurrente. ¿Desea eliminar solo esta tarea o toda la serie?';
                    this.buttons = [
                        { label: 'Eliminar solo esta tarea', action: 'single', color: 'bg-red-900 hover:bg-red-800' },
                        { label: 'Eliminar toda la serie', action: 'series', color: 'px-4 py-2 rounded button-extra' },
                        { label: 'Cancelar', action: 'cancel', color: 'px-4 py-2 rounded button-cancel' },
                    ];
                } else {
                    this.message = '¿Está seguro que desea eliminar esta tarea del usuario?';
                    this.buttons = [
                        { label: 'Eliminar', action: 'single', color: 'bg-red-900 hover:bg-red-800' },
                        { label: 'Cancelar', action: 'cancel', color: 'px-4 py-2 bg-gray-500 rounded hover:bg-gray-400' },
                    ];
                }

                this.callback = (action) => {
                    if (action !== 'cancel') {
                        this.open = false;
                        fetch(deleteUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                _method: 'DELETE',
                                deleteSeries: action === 'series'
                            })
                        })
                            .then(res => res.json())
                            .then(data => window.location.href = data.redirect_url);
                    }
                };
            }

            this.open = true;
        },

        handle(action) {
            if (this.callback) this.callback(action);
            this.open = false;
        }
    }
}

// Color de input de tareas
function initColorPicker() {
    const colorInput = document.getElementById('color-input');
    const swatches = document.querySelectorAll('.color-swatch');
    if (!colorInput || swatches.length === 0) return;

    const defaultColor = '#FFFFFF';

    swatches.forEach(swatch => {
        swatch.addEventListener('click', () => {
            if (typeof disableColorPicker !== 'undefined' && disableColorPicker) return;

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

/* ----------------- UTILITIES ----------------- */
export function formatDateConsistent(date) {
    return date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');
}

export function showLoading(containerId) {
    const loadingHTML = `
        <div class="flex items-center justify-center p-8">
            <div class="flex items-center gap-3 text-gray-600">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                <span>Cargando tareas...</span>
            </div>
        </div>
    `;
    document.getElementById(containerId).innerHTML = loadingHTML;
}

export function handleAjaxError(error, container) {
    console.error('Error AJAX:', error);
    const errorMessage = `
        <div class="text-center text-red-600 bg-red-50 border border-red-200 rounded p-6 shadow">
            <div class="flex items-center justify-center mb-2">
                <i data-lucide="alert-circle" class="w-6 h-6 mr-2"></i>
                <span class="font-semibold">Error al cargar los datos</span>
            </div>
            <p class="text-sm">Por favor, recargue la página e inténtelo de nuevo.</p>
            <button onclick="location.reload()" class="mt-3 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                Recargar página
            </button>
        </div>
    `;
    document.getElementById(container).innerHTML = errorMessage;
    if (window.createIcons && window.lucideIcons) {
        window.createIcons({ icons: window.lucideIcons });
    }
}

/* ----------------- CALENDAR VIEW ----------------- */
export function calendarView() {
    const today = new Date();
    const minMonth = today.getMonth();
    const minYear = today.getFullYear() - 1;

    function formatDateLocal(date) {
        return date.getFullYear() + '-' +
            String(date.getMonth()+1).padStart(2,'0') + '-' +
            String(date.getDate()).padStart(2,'0');
    }

    return {
        viewMode:'weekly',
        currentDate: today,
        displayedDays: [],
        tasks: window.tasksByDate,
        specialDays: window.specialDays,
        currentMonth: today.getMonth(),
        currentYear: today.getFullYear(),
        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        yearsRange: Array.from({length:7}, (_, i) => today.getFullYear() - 1 + i),
        get currentCalendarDate() {
            return this.currentDate;
        },
        get canGoPrevMonth() {
            return this.currentYear > minYear || (this.currentYear === minYear && this.currentMonth > 0);
        },
        getNewTaskUrl() {
            const date = this.formatDateLocal(this.currentDate);
            const viewMode = this.viewMode;
            return `${window.newTaskBaseUrl}?date=${date}&viewMode=${viewMode}`;
        },
        formatDateLocal(date) {
            return date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2,'0') + '-' +
                String(date.getDate()).padStart(2,'0');
        },
        get isVacationDay() {
            const dateKey = this.formatDateLocal(this.currentDate);
            return this.specialDays[dateKey] === 'vacation';
        },
        openCloneTask(task) {
            window.dispatchEvent(new CustomEvent('open-clone-modal', { detail: task }));
        },
        getCloneTaskUrl(taskId, userId) {
            console.log(taskId);
            console.log(userId);
            const date = this.formatDateLocal(this.currentDate);
            const viewMode = this.viewMode;
            return window.cloneTaskBaseUrl.replace('__USER__', userId) + `?date=${date}&viewMode=${viewMode}&clone=${taskId}`;
        },
        updateViewModeInUrl() {
            const url = new URL(window.location);
            url.searchParams.set('viewMode', this.viewMode);
            window.history.replaceState({}, '', url);
        },
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const dateParam = urlParams.get('date');
            const viewModeParam = urlParams.get('viewMode');

            if (dateParam) {
                this.currentDate = new Date(dateParam);
                this.currentMonth = this.currentDate.getMonth();
                this.currentYear = this.currentDate.getFullYear();
            }

            if (viewModeParam) {
                this.viewMode = viewModeParam;
            }

            this.updateDisplayedDays(7);
            this.renderMiniCalendar();

            this.$watch('viewMode', value => {
                this.updateViewModeInUrl();

                if (value === 'daily') {
                    const formatted = this.formatDateLocal(this.currentDate);
                    document.dispatchEvent(new CustomEvent('date-changed', { detail: { date: formatted } }));
                }
            });

            document.addEventListener('clone-task', (e) => {
                const { taskId, userId } = e.detail;
                window.location = this.getCloneTaskUrl(taskId, userId);
            });

            document.addEventListener('daily-date-changed', (e) => {
                const parts = e.detail.date.split('-');
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                this.currentDate = new Date(year, month, day);
                this.updateDisplayedDays(7);
                this.renderMiniCalendar();
            });
        },
        updateDisplayedDays(count) {
            this.displayedDays=[];
            const startDay = new Date(this.currentDate);
            const dayOfWeek = startDay.getDay();
            const diff = (dayOfWeek===0?-6:1-dayOfWeek);
            startDay.setDate(startDay.getDate()+diff);

            for(let i=0;i<count;i++){
                const day=new Date(startDay);
                day.setDate(startDay.getDate()+i);

                const dayKey = formatDateLocal(day);
                this.displayedDays.push({
                    date: dayKey,
                    label: day.getDate(),
                    weekday: day.toLocaleDateString('es-ES',{weekday:'short'}),
                    isSelected: dayKey === formatDateLocal(this.currentDate),
                    isWeekend: day.getDay() === 0 || day.getDay() === 6
                });
            }
            document.getElementById('task-detail-container').innerHTML='';
            this.$nextTick(() => {
                if (window.createIcons && window.lucideIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        },
        prevMonth() {
            if (!this.canGoPrevMonth) return;
            this.currentMonth--;
            if (this.currentMonth < 0) {
                this.currentMonth = 11;
                this.currentYear--;
            }
            this.onMonthYearChange();
        },

        nextMonth() {
            this.currentMonth++;
            if (this.currentMonth > 11) {
                this.currentMonth = 0;
                this.currentYear++;
            }
            this.onMonthYearChange();
        },
        renderMiniCalendar(){
            const mini=document.getElementById('miniCalendar');
            if(!mini) return;
            mini.innerHTML='';
            ['L','M','X','J','V','S','D'].forEach(d=>{
                const header=document.createElement('div');
                header.textContent=d;
                header.className='text-center font-semibold text-gray-700 bg-gray-300 py-1 rounded';
                mini.appendChild(header);
            });

            const firstDay=new Date(this.currentYear,this.currentMonth,1);
            const lastDay=new Date(this.currentYear,this.currentMonth+1,0);
            const startWeekday=(firstDay.getDay()+6)%7;
            for(let i=0;i<startWeekday;i++) mini.appendChild(document.createElement('div'));
            for (let d = 1; d <= lastDay.getDate(); d++) {
                const dayDate = new Date(this.currentYear, this.currentMonth, d);
                const dayKey = formatDateLocal(dayDate);

                const btn = document.createElement('button');
                btn.type = 'button';

                // Base
                let classes = 'border p-2 rounded relative hover:bg-gray-200';

                // Selected day
                const currentDayKey = formatDateLocal(new Date(
                    this.currentDate.getFullYear(),
                    this.currentDate.getMonth(),
                    this.currentDate.getDate()
                ));

                if (dayKey === currentDayKey) {
                    classes += ' bg-blue-200';
                }

                const today = new Date();
                const todayKey = formatDateLocal(today);
                if (dayKey === todayKey) {
                    classes += ' border-2 border-blue-900';
                }

                // Holidays, vacations or legal absences
                if (this.specialDays[dayKey]) {
                    const type = this.specialDays[dayKey].toUpperCase();
                    const colorConfig = window.calendarColors[type];
                    if (colorConfig) {
                        classes += ` ${colorConfig.class}`;
                    }
                }

                btn.className = classes;
                btn.textContent = d;

                if (this.tasks[dayKey]?.length) {
                    const indicator = document.createElement('div');
                    indicator.className = 'absolute bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 rounded-full';
                    indicator.style.backgroundColor = '#3B82F6';
                    btn.appendChild(indicator);
                }

                btn.addEventListener('click', () => {
                    this.currentDate = dayDate;
                    this.updateDisplayedDays(7);
                    this.renderMiniCalendar();
                    if (this.viewMode === 'daily') {
                        const formatted = formatDateLocal(dayDate);
                        document.dispatchEvent(new CustomEvent('date-changed', { detail: { date: formatted } }));
                    }
                    document.getElementById('task-detail-container').innerHTML = '';
                });

                mini.appendChild(btn);
            }
            this.$nextTick(() => {
                if (window.createIcons && window.lucideIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        },
        onMonthYearChange(){
            this.currentDate = new Date(this.currentYear, this.currentMonth, 1);
            this.updateDisplayedDays(7);
            this.renderMiniCalendar();
        },
        async openTask(taskId, taskDate = null) {
            const dateParam = taskDate ?? (this.displayedDays.length ? this.displayedDays[0].date : formatDateLocal(new Date()));
            const url = window.taskDetailBaseUrl.replace('__ID__', taskId) + `?date=${dateParam}`;

            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                document.getElementById('task-detail-container').innerHTML = await response.text();

                if (window.createIcons && window.lucideIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }

                document
                    .getElementById('task-detail-container')
                    .scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                console.error(err);
                await customAlert('No se pudo cargar la tarea.');
            }
        },
        goPrevWeek() {
            this.currentDate.setDate(this.currentDate.getDate() - 7);
            this.updateDisplayedDays(7);

            // Sincronizar mini calendario
            const firstDayOfWeek = new Date(this.displayedDays[0].date);
            this.currentMonth = firstDayOfWeek.getMonth();
            this.currentYear = firstDayOfWeek.getFullYear();

            this.renderMiniCalendar();
        },

        goNextWeek() {
            this.currentDate.setDate(this.currentDate.getDate() + 7);
            this.updateDisplayedDays(7);

            // Sincronizar mini calendario
            const firstDayOfWeek = new Date(this.displayedDays[0].date);
            this.currentMonth = firstDayOfWeek.getMonth();
            this.currentYear = firstDayOfWeek.getFullYear();

            this.renderMiniCalendar();
        },

        goToday() {
            const today = new Date();
            this.currentDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();
            this.updateDisplayedDays(7);
            this.renderMiniCalendar();

            if (this.viewMode === 'daily') {
                const formatted = this.formatDateLocal(this.currentDate);
                document.dispatchEvent(new CustomEvent('date-changed', { detail: { date: formatted } }));
            }
        },
    }
}

/* ----------------- DAILY VIEW ----------------- */
export function dailyControls(initialDate = null) {
    return {
        selectedDate: initialDate ? new Date(initialDate) : new Date(),
        filters: {
            title: '',
            status: ''
        },
        taskStatuses: ['pending', 'completed'],
        initDaily() {
            this.loadTasks();

            // Listen to mini calendar event
            document.addEventListener('date-changed', e => {
                this.selectedDate = new Date(e.detail.date);
                this.loadTasks();
            });

            // Listen to changes from filters
            document.addEventListener('filters-changed', e => {
                this.filters = e.detail;
                this.loadTasks();
            });

            // Listen to changes from the mini calendar
            document.addEventListener('daily-date-changed', e => {
                const parts = e.detail.date.split('-');
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                this.selectedDate = new Date(year, month, day);
                this.loadTasks();
            });
        },
        formatDate(date) {
            return date.toLocaleDateString('es-ES', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        },
        prevDay() {
            this.selectedDate.setDate(this.selectedDate.getDate() - 1);
            this.loadTasks();
            this.syncMiniCalendar();
        },

        nextDay() {
            this.selectedDate.setDate(this.selectedDate.getDate() + 1);
            this.loadTasks();
            this.syncMiniCalendar();
        },
        today() {
            const now = new Date();
            this.selectedDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            this.loadTasks();
            this.syncMiniCalendar();
        },
        syncMiniCalendar() {
            const year = this.selectedDate.getFullYear();
            const month = this.selectedDate.getMonth();
            const day = this.selectedDate.getDate();
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;

            document.dispatchEvent(new CustomEvent('daily-date-changed', {
                detail: { date: dateStr }
            }));
        },
        applyFilters() {
            this.loadTasks();
        },
        loadTasks() {
            const formatted = this.selectedDate.toISOString().split('T')[0];
            const params = new URLSearchParams({
                date: formatted,
                title: this.filters.title,
                status: this.filters.status
            });

            const url = `${window.dailyTasksBaseUrl}?${params.toString()}`;

            showLoading('daily-tasks-container');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('daily-tasks-container');
                    container.innerHTML = html;

                    // Renderizamos iconos Lucide solo en este contenedor
                    if (window.createIcons && window.lucideIcons) {
                        window.createIcons({ icons: window.lucideIcons }, container);
                    }
                })
                .catch(error => {
                    handleAjaxError(error, 'daily-tasks-container');
                });
        }
    }
}

/* ----------------- IMPROVED TASK LOADING ----------------- */
export function enhancedLoadTasks(selectedDate, filters, userId) {
    showLoading('daily-tasks-container');
    const formatted = formatDateConsistent(selectedDate);
    const params = new URLSearchParams({
        date: formatted,
        title: filters.title || '',
        status: filters.status || ''
    });
    const baseUrl = window.dailyTasksBaseUrl;
    const url = `${baseUrl}?${params.toString()}`;
    return fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            return response.text();
        })
        .then(html => {
            const container = document.getElementById('daily-tasks-container');
            container.innerHTML = html;
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons }, container);
            }
        })
        .catch(error => handleAjaxError(error, 'daily-tasks-container'));
}




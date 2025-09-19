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
            const nonWorkingUrl = container?.dataset?.nonWorkingCheckUrl || '';
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
                    // === 1. Check if it is a non-working day ===
                    const nonWorkingResponse = await fetch(
                        nonWorkingUrl.replace('{userId}', userId) + `?scheduled_date=${scheduledDate}`
                    );
                    const nonWorkingData = await nonWorkingResponse.json();

                    if (nonWorkingData.nonWorking) {
                        const proceed = confirm('La fecha seleccionada corresponde a un día no laboral (festivo o ausencia legal). ' +
                            '¿Desea continuar?');
                        if (!proceed) {
                            checkingConflict = false;
                            return;
                        }

                        let flagInput = form.querySelector('input[name="is_non_working_day"]');
                        if (!flagInput) {
                            flagInput = document.createElement('input');
                            flagInput.type = 'hidden';
                            flagInput.name = 'is_non_working_day';
                            flagInput.value = '1';
                            form.appendChild(flagInput);
                        }
                    }

                    // === 2. Check for time conflict ===
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
        openModal({ src, type }) {
            this.imgSrc = src;
            this.context = type;
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
                    { label: 'Modificar solo esta tarea', action: 'single', color: 'bg-indigo-900 hover:bg-indigo-800' },
                    { label: 'Modificar la serie', action: 'series', color: 'bg-green-600 hover:bg-green-500' },
                    { label: 'Cancelar', action: 'cancel', color: 'bg-red-900 hover:bg-red-800' },
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
                        { label: 'Eliminar solo esta tarea', action: 'single', color: 'bg-red-600 hover:bg-red-500' },
                        { label: 'Eliminar toda la serie', action: 'series', color: 'bg-red-900 hover:bg-red-800' },
                        { label: 'Cancelar', action: 'cancel', color: 'bg-gray-500 hover:bg-gray-400' },
                    ];
                } else {
                    this.message = '¿Está seguro que desea eliminar esta tarea del usuario?';
                    this.buttons = [
                        { label: 'Eliminar', action: 'single', color: 'bg-red-900 hover:bg-red-800' },
                        { label: 'Cancelar', action: 'cancel', color: 'bg-gray-500 hover:bg-gray-400' },
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
        yearsRange: Array.from({length:6},(_,i)=>today.getFullYear() + i),
        get currentCalendarDate() {
            return this.currentDate;
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
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const dateParam = urlParams.get('date');
            const viewModeParam = urlParams.get('viewMode');

            if (dateParam) {
                this.currentDate = new Date(dateParam);
            }
            if (viewModeParam) {
                this.viewMode = viewModeParam;
            }

            this.updateDisplayedDays(7);
            this.renderMiniCalendar();

            this.$watch('viewMode', value => {
                if (value === 'daily') {
                    const formatted = this.formatDateLocal(this.currentDate);
                    document.dispatchEvent(new CustomEvent('date-changed', { detail: { date: formatted } }));
                }
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
        prevMonth(){ this.currentMonth--; if(this.currentMonth<0){this.currentMonth=11; this.currentYear--;} this.onMonthYearChange(); },
        nextMonth(){ this.currentMonth++; if(this.currentMonth>11){this.currentMonth=0; this.currentYear++;} this.onMonthYearChange(); },
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

                // Día seleccionado
                if (dayKey === formatDateLocal(this.currentDate)) {
                    classes += ' bg-blue-200';
                }

                // Fines de semana
                if (dayDate.getDay() === 0 || dayDate.getDay() === 6) {
                    classes += ' bg-gray-200';
                }

                // Festivos o vacaciones o ausencias legales
                if (this.specialDays[dayKey]) {
                    const type = this.specialDays[dayKey].toUpperCase();
                    const colorConfig = window.calendarColors[type];
                    if (colorConfig) {
                        classes += ` ${colorConfig.class}`;
                    }
                }

                btn.className = classes;
                btn.textContent = d;

                // Indicador de tareas
                if (this.tasks[dayKey]?.length) {
                    const indicator = document.createElement('div');
                    indicator.className = 'absolute bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 rounded-full';
                    indicator.style.backgroundColor = '#3B82F6';
                    btn.appendChild(indicator);
                }

                // Click del día
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
        openTask(taskId, taskDate = null){
            const dateParam = taskDate ?? (this.displayedDays.length ? this.displayedDays[0].date : formatDateLocal(new Date()));
            const url = window.taskDetailBaseUrl.replace('__ID__', taskId) + `?date=${dateParam}`;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('task-detail-container').innerHTML = html;
                    if (window.createIcons && window.lucideIcons) {
                        window.createIcons({ icons: window.lucideIcons });
                    }
                    document.getElementById('task-detail-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(()=>alert('No se pudo cargar la tarea'));
        }
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

            // Escuchar evento del mini calendario
            document.addEventListener('date-changed', e => {
                this.selectedDate = new Date(e.detail.date);
                this.loadTasks();
            });

            document.addEventListener('filters-changed', e => {
                this.filters = e.detail;
                this.loadTasks();
            });
        },
        formatDate(date) {
            return date.toLocaleDateString('es-ES', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        },
        prevDay() {
            this.selectedDate = new Date(this.selectedDate.setDate(this.selectedDate.getDate() - 1));
            this.loadTasks();
        },
        nextDay() {
            this.selectedDate = new Date(this.selectedDate.setDate(this.selectedDate.getDate() + 1));
            this.loadTasks();
        },
        today() {
            this.selectedDate = new Date();
            this.loadTasks();
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

/* ----------------- CARGA DE TAREAS MEJORADA ----------------- */
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




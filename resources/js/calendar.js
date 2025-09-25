export function initCalendars() {
    // Initialize calendars
    const calendars = document.querySelectorAll('[id^="calendarGrid"]');
    calendars.forEach(calendarGrid => {
        const monthSelect = document.getElementById('monthSelect');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const yearInput = document.getElementById('year');

        const vacationInput = calendarGrid.dataset.input
            ? document.querySelector(calendarGrid.dataset.input)
            : null;

        const legalInput = calendarGrid.dataset.legalInput
            ? document.querySelector(calendarGrid.dataset.legalInput)
            : null;

        const vacationDates = vacationInput?.value ? JSON.parse(vacationInput.value) : [];
        const legalDates = legalInput?.value ? JSON.parse(legalInput.value) : [];

        const mode = calendarGrid.dataset.mode || 'vacation';
        const holidayDates = calendarGrid.dataset.holidays ? JSON.parse(calendarGrid.dataset.holidays) : [];

        initCalendar({
            calendarGrid,
            monthSelect,
            btnPrev,
            btnNext,
            yearInput,
            vacationInput,
            legalInput,
            vacationDates,
            legalDates,
            holidayDates,
            mode,
        });
    });

    // Confirmation modal
    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.openModal;
            const modal = document.getElementById(modalId);
            if (!modal) return;

            const formId = modal.querySelector('.confirmBtn')?.dataset.formId;
            if (!formId) return;

            const hiddenInput = document.querySelector(`#${formId} #selectedDates`);
            const legalInput = document.querySelector(`#${formId} #selectedLegalAbsences`);
            const dateList = modal.querySelector('#dateList');
            const legalList = modal.querySelector('#legalDateList');

            let vacationDates = [];
            let legalDates = [];

            try { vacationDates = JSON.parse(hiddenInput?.value || '[]'); } catch(e){ console.error(e); }
            try { legalDates = JSON.parse(legalInput?.value || '[]'); } catch(e){ console.error(e); }

            // Si no hay ningún día seleccionado
            if (vacationDates.length === 0 && legalDates.length === 0) {
                if (confirm("No has seleccionado ningún día. Se guardará la plantilla sin días. ¿Deseas continuar?")) {
                    const form = document.getElementById(formId);
                    if (form) form.submit();
                }
                return; // no abrir modal
            }

            // Rellenar modal con fechas seleccionadas
            if (dateList) {
                dateList.innerHTML = '';
                vacationDates.forEach(d => {
                    const li = document.createElement('li');
                    const [year, month, day] = d.split('-');
                    li.textContent = `${day}/${month}/${year}`;
                    dateList.appendChild(li);
                });
            }

            if (legalList) {
                legalList.innerHTML = '';
                legalDates.forEach(d => {
                    const li = document.createElement('li');
                    const [year, month, day] = d.split('-');
                    li.textContent = `${day}/${month}/${year}`;
                    legalList.appendChild(li);
                });
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    // Cancel modal
    document.querySelectorAll('.cancelBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('div[id^="confirmModal"]');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });

    // Confirm and send form
    document.querySelectorAll('.confirmBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const formId = btn.dataset.formId;
            const form = document.getElementById(formId);
            if (form) form.submit();
        });
    });

    // Toggle checkboxes: only one active at a time
    const vacationCheckbox = document.getElementById('enableHolidayMode');
    const legalCheckbox = document.getElementById('enableLegalAbsenceMode');

    if (vacationCheckbox && legalCheckbox) {
        vacationCheckbox.addEventListener('change', () => {
            if (vacationCheckbox.checked) {
                legalCheckbox.checked = false;
                legalCheckbox.disabled = true;
            } else {
                legalCheckbox.disabled = false;
            }
        });

        legalCheckbox.addEventListener('change', () => {
            if (legalCheckbox.checked) {
                vacationCheckbox.checked = false;
                vacationCheckbox.disabled = true;
            } else {
                vacationCheckbox.disabled = false;
            }
        });
    }

}

function normalizeDates(dates = []) {
    return dates.map(d => d.split('T')[0]);
}

/**
 * Initialize an interactive calendar inside a container.
 * Allows selecting holidays or vacation days and navigating months and years.
 */
function initCalendar(options = {}) {
    const {
        calendarGrid,
        monthSelect,
        btnPrev,
        btnNext,
        yearInput,
        vacationInput,
        legalInput,
        vacationDates = [],
        legalDates = [],
        holidayDates = [],
        mode = 'vacation'
    } = options;

    if (!calendarGrid || !monthSelect) return;

    const pad = n => String(n).padStart(2, '0');
    const today = new Date();
    const selectedVacationDates = window.selectedVacationDatesForClone
        ? new Set(normalizeDates(Array.from(window.selectedVacationDatesForClone)))
        : new Set(vacationDates);
    const selectedLegalDates = new Set(legalDates);
    let currentYear = yearInput?.value ? parseInt(yearInput.value,10) : today.getFullYear();
    let currentMonth;

    if (mode === 'holiday') {
        currentMonth = 0;
    } else {
        currentMonth = today.getMonth();
    }

    const colorData = calendarGrid.dataset.colors ? JSON.parse(calendarGrid.dataset.colors) : {};

    const modeClasses = {
        holiday: colorData.HOLIDAY?.class,
        vacation: colorData.VACATION?.class,
        legal_absence: colorData.LEGAL_ABSENCE?.class,
        weekend: colorData.WEEKEND?.class,
        working: colorData.WORKING?.class
    };

    function render() {
        // Clear previous calendar cells
        calendarGrid.innerHTML = '';
        const weekdays = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        weekdays.forEach(day => {
            const cell = document.createElement('div');
            cell.className = 'border bg-gray-300 text-center font-semibold text-xs p-1';
            cell.textContent = day;
            calendarGrid.appendChild(cell);
        });

        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const daysInMonth = lastDay.getDate();

        let startWeekday = firstDay.getDay();
        startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

        for (let i = 0; i < startWeekday; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'border aspect-square bg-gray-50';
            calendarGrid.appendChild(emptyCell);
        }

        const filteredVacationDates = Array.from(selectedVacationDates).filter(date => date.startsWith(String(currentYear)));
        const filteredLegalDates = Array.from(selectedLegalDates).filter(date => date.startsWith(String(currentYear)));

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${pad(currentMonth + 1)}-${pad(d)}`;
            const dateObj = new Date(currentYear, currentMonth, d);
            let weekday = dateObj.getDay();
            weekday = weekday === 0 ? 6 : weekday - 1;
            const isWeekend = weekday === 5 || weekday === 6;
            const isVacation = filteredVacationDates.includes(dateStr);
            const isLegal = filteredLegalDates.includes(dateStr);
            const cell = document.createElement('button');
            cell.type = 'button';

            let bgClass = 'bg-gray-100';
            if (mode === 'holiday') {
                if (selectedVacationDates.has(dateStr)) {
                    bgClass = modeClasses.holiday;
                }
            } else {
                if (isVacation) {
                    bgClass = modeClasses.vacation;
                } else if (isLegal) {
                    bgClass = modeClasses.legal_absence;
                } else if (holidayDates.includes(dateStr)) {
                    bgClass = modeClasses.holiday;
                    if (mode === 'vacation') {
                        cell.disabled = true;
                        cell.classList.add('opacity-50','cursor-not-allowed');
                    }
                }
            }

            if (isWeekend) {
                bgClass = modeClasses.weekend;
            } else if (!isVacation && !isLegal && !holidayDates.includes(dateStr)) {
                bgClass = modeClasses.working;
            }

            cell.className = ['border aspect-square p-2 text-left transition hover:bg-gray-200', bgClass].join(' ');
            cell.dataset.date = dateStr;
            cell.innerHTML = `<div class="text-xs text-gray-600">${d}</div>`;

            cell.addEventListener('click', () => {
                const vacationMode = document.getElementById('enableHolidayMode')?.checked;
                const legalMode = document.getElementById('enableLegalAbsenceMode')?.checked;

                if (mode === 'holiday' && vacationMode) {
                    if (selectedVacationDates.has(dateStr)) selectedVacationDates.delete(dateStr);
                    else selectedVacationDates.add(dateStr);
                    if (vacationInput) {
                        vacationInput.value = JSON.stringify(Array.from(selectedVacationDates).sort());
                    }
                    render();
                }

                if (mode === 'vacation' && vacationMode) {
                    if (selectedVacationDates.has(dateStr)) selectedVacationDates.delete(dateStr);
                    else selectedVacationDates.add(dateStr);

                    if (vacationInput) {
                        const updated = Array.from(selectedVacationDates).filter(d => d.startsWith(String(currentYear)));
                        vacationInput.value = JSON.stringify(updated.sort());
                    }
                }

                if (mode === 'vacation' && legalMode) {
                    if (selectedLegalDates.has(dateStr)) selectedLegalDates.delete(dateStr);
                    else selectedLegalDates.add(dateStr);

                    if (legalInput) {
                        const updated = Array.from(selectedLegalDates).filter(d => d.startsWith(String(currentYear)));
                        legalInput.value = JSON.stringify(updated.sort());
                    }
                }

                render();
            });

            calendarGrid.appendChild(cell);
        }

        if (vacationInput) {
            const updatedDates = Array.from(selectedVacationDates).filter(date => date.startsWith(String(currentYear)));
            vacationInput.value = JSON.stringify(updatedDates.sort());
        }

        btnPrev.disabled = currentMonth === 0;
        btnNext.disabled = currentMonth === 11;
        monthSelect.value = currentMonth; // sync select with current month
    }

    btnPrev?.addEventListener('click', () => {
        if (currentMonth > 0) currentMonth--;
        render();
    });

    btnNext?.addEventListener('click', () => {
        if (currentMonth < 11) currentMonth++;
        render();
    });

    monthSelect?.addEventListener('change', () => {
        currentMonth = parseInt(monthSelect.value, 10);
        render();
    });

    const yearInputElem = yearInput || document.querySelector('input[name="year"]');
    function handleYearChange() {
        const newYear = parseInt(yearInputElem.value, 10) || today.getFullYear();
        currentYear = newYear;
        const datesOfYear = Array.from(selectedVacationDates)
            .map(d => ({
                year: parseInt(d.split('-')[0],10),
                month: parseInt(d.split('-')[1],10)-1
            }))
            .filter(d => d.year === newYear)
            .sort((a,b) => a.month - b.month);
        currentMonth = datesOfYear.length > 0 ? datesOfYear[0].month : 0;
        render();
    }
    yearInputElem?.addEventListener('input', handleYearChange);
    yearInputElem?.addEventListener('change', handleYearChange);

    render();
}

// --- Initialize clone select using TomSelect ---
export function initCloneSelect() {
    const container = document.getElementById('workCalendar-form-container');
    const cloneSelectEl = document.getElementById('clone_calendar_id');
    const holidaysInput = document.getElementById('selectedDates');
    const cloneUrlTemplate = container?.dataset.cloneUrl;

    if (!cloneSelectEl || !cloneUrlTemplate) return;

    // Initialize TomSelect in the clone select
    new TomSelect(cloneSelectEl, {
        placeholder: 'Elegir plantilla',
        create: false,
        onChange: async function(value) {
            if (!value) return;
            const url = cloneUrlTemplate.replace('__ID__', value);
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Error en la petición');
                const data = await res.json();

                //Fill fields
                holidaysInput.value = JSON.stringify(data.holidays);
                holidaysInput.dataset.dates = JSON.stringify(data.holidays);

                // Update calendar
                const calendarGrid = document.getElementById('calendarGrid');
                if (calendarGrid) {
                    calendarGrid.innerHTML = '';
                    calendarGrid.dataset.holidays = JSON.stringify(data.holidays);

                    window.selectedVacationDatesForClone = new Set(data.holidays);
                    const holidayCheckbox = document.getElementById('enableHolidayMode');
                    if (holidayCheckbox) holidayCheckbox.checked = true;

                    initCalendars();
                }

            } catch (err) {
                console.error('Error al clonar la plantilla:', err);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCloneSelect();
});

export function confirmDelete(form) {
    const userCount = parseInt(form.dataset.users);
    if (userCount > 0) {
        return confirm(
            'Existen usuarios actualmente asignados a este calendario laboral. Al borrar este calendario laboral, estos usuarios quedarán sin un calendario laboral asignado. ¿Desea proceder con el borrado?'
        );
    } else {
        return confirm('¿Está seguro que desea eliminar esta plantilla?');
    }
}

export function calendarForm() {
    return {
        status: window.calendarData.oldStatus || window.calendarData.templateStatus,
        warningOpen: false,
        confirmDaysModalOpen: false,
        dateList: window.calendarData.selectedDates || [],

        openConfirmDaysModal() {
            const selectedInput = document.getElementById('selectedDates');
            const fromInput = selectedInput ? JSON.parse(selectedInput.value || '[]') : [];
            const fromServer = window.calendarData.selectedDates || [];

            const combined = Array.from(new Set([...fromServer, ...fromInput]));

            if (combined.length > 0) {
                this.dateList = combined;
                this.confirmDaysModalOpen = true;
            } else {
                this.confirmAndCheckWarning();
            }
        },

        confirmAndCheckWarning() {
            this.confirmDaysModalOpen = false;
            if (this.status === window.calendarData.templateStatus &&
                window.calendarData.userCount > 0) {
                this.warningOpen = true;
            } else {
                this.submitFormToServer();
            }
        },

        confirmWarning() {
            this.warningOpen = false;
            this.submitFormToServer();
        },

        submitFormToServer() {
            document.getElementById('calendarTemplateForm').submit();
        }
    };
}

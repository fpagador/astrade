export function initCalendars() {
    // Initialize calendars
    const calendars = document.querySelectorAll('[id^="calendarGrid"]');
    calendars.forEach(calendarGrid => {
        const monthSelect = document.getElementById('monthSelect');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const yearInput = document.getElementById('year');
        const inputSelector = calendarGrid.dataset.input;
        const selectedDatesInput = inputSelector ? document.querySelector(inputSelector) : null;
        const initialDates = selectedDatesInput?.value ? JSON.parse(selectedDatesInput.value) : [];
        const mode = calendarGrid.dataset.mode || 'vacation';
        const holidayDates = calendarGrid.dataset.holidays ? JSON.parse(calendarGrid.dataset.holidays) : [];

        initCalendar({
            calendarGrid,
            monthSelect,
            btnPrev,
            btnNext,
            selectedDatesInput,
            initialDates,
            mode,
            yearInput,
            holidayDates
        });
    });

    // Confirmation modal
    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.openModal;
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const formId = modal.querySelector('.confirmBtn')?.dataset.formId;
            if (formId) {
                const hiddenInput = document.querySelector(`#${formId} #selectedDates`);
                const dateList = modal.querySelector('#dateList');
                if (hiddenInput && dateList) {
                    let dates = [];
                    try {
                        dates = JSON.parse(hiddenInput.value || '[]');
                    } catch(e) {
                        console.error(e);
                    }
                    dateList.innerHTML = '';
                    dates.forEach(d => {
                        const li = document.createElement('li');
                        const [year, month, day] = d.split('-');
                        li.textContent = `${day}/${month}/${year}`;
                        dateList.appendChild(li);
                    });
                }
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
        selectedDatesInput,
        initialDates = [],
        mode = 'holiday',
        yearInput,
        holidayDates = []
    } = options;

    if (!calendarGrid || !monthSelect) return;

    const pad = n => String(n).padStart(2, '0');
    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const today = new Date();
    const selectedDates = new Set(initialDates);
    let currentYear = yearInput?.value ? parseInt(yearInput.value,10) : today.getFullYear();
    let currentMonth = parseInt(monthSelect.value, 10) || today.getMonth();

    const modeClasses = {
        holiday: 'bg-yellow-200',
        vacation: 'bg-green-500 text-white',
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

        const filteredDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${pad(currentMonth + 1)}-${pad(d)}`;
            const dateObj = new Date(currentYear, currentMonth, d);
            let weekday = dateObj.getDay();
            weekday = weekday === 0 ? 6 : weekday - 1;
            const isWeekend = weekday === 5 || weekday === 6;
            const isSelected = filteredDates.includes(dateStr);
            const cell = document.createElement('button');
            cell.type = 'button';

            let bgClass = 'bg-gray-100';
            if (isSelected) {
                bgClass = modeClasses[mode] || 'bg-yellow-200';
            } else if (holidayDates.includes(dateStr)) {
                bgClass = 'bg-yellow-200'; // highlight holiday in red
                if (mode === 'vacation') {
                    cell.disabled = true;
                    cell.classList.add('opacity-50','cursor-not-allowed');
                }
            } else if (isWeekend) {
                bgClass = 'bg-gray-200';
            }

            cell.className = ['border aspect-square p-2 text-left transition hover:bg-gray-300', bgClass].join(' ');
            cell.dataset.date = dateStr;
            cell.innerHTML = `<div class="text-xs text-gray-600">${d}</div>`;

            cell.addEventListener('click', () => {
                const checkbox = document.getElementById('enableHolidayMode');
                if ((mode === 'holiday' && checkbox?.checked) || (mode === 'vacation' && checkbox?.checked)) {
                    if (selectedDates.has(dateStr)) selectedDates.delete(dateStr);
                    else selectedDates.add(dateStr);
                    if (selectedDatesInput) {
                        const updatedDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));
                        selectedDatesInput.value = JSON.stringify(updatedDates.sort());
                    }
                    render();
                }
            });

            calendarGrid.appendChild(cell);
        }

        if (selectedDatesInput) {
            const updatedDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));
            selectedDatesInput.value = JSON.stringify(updatedDates.sort());
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
        const datesOfYear = Array.from(selectedDates)
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

export function initCalendars() {
    // Initialize calendars
    const calendars = document.querySelectorAll('[id^="calendarGrid"]');
    calendars.forEach(calendarGrid => {
        const monthLabel = document.getElementById('monthLabel');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const yearInput = document.getElementById('yearInput');
        const inputSelector = calendarGrid.dataset.input;
        const selectedDatesInput = inputSelector ? document.querySelector(inputSelector) : null;
        const initialDates = selectedDatesInput?.value ? JSON.parse(selectedDatesInput.value) : [];
        const mode = calendarGrid.dataset.mode || 'vacation';

        initCalendar({
            calendarGrid,
            monthLabel,
            btnPrev,
            btnNext,
            selectedDatesInput,
            initialDates,
            mode,
            yearInput
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
 *
 * @param {Object} options - Configuration object for the calendar
 * @param {HTMLElement} options.calendarGrid - Container for calendar cells
 * @param {HTMLElement} options.monthLabel - Element displaying current month and year
 * @param {HTMLElement} options.btnPrev - Button to navigate to previous month
 * @param {HTMLElement} options.btnNext - Button to navigate to next month
 * @param {HTMLInputElement} [options.selectedDatesInput] - Hidden input storing selected dates
 * @param {string[]} [options.initialDates=[]] - Array of initial selected dates (YYYY-MM-DD)
 * @param {'holiday'|'vacation'} [options.mode='holiday'] - Mode: 'holiday' or 'vacation'
 * @param {HTMLInputElement} [options.yearInput] - Input element for selecting the year
 */
function initCalendar(options = {}) {
    // Destructure configuration options
    const {
        calendarGrid,
        monthLabel,
        btnPrev,
        btnNext,
        selectedDatesInput,
        initialDates = [],
        mode = 'holiday', // 'holiday' or 'vacation'
        yearInput
    } = options;

    // If essential elements are missing, exit function
    if (!calendarGrid || !monthLabel) return;

    // Helper: pad single-digit numbers with leading zero
    const pad = n => String(n).padStart(2, '0');

    // Array of month names for display
    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    const today = new Date(); // Current date
    const selectedDates = new Set(initialDates); // Store selected dates in a Set for fast lookup

    // === Initialize current year and month ===
    let currentYear;
    let currentMonth;

    // Determine the initial year:
    // 1. From the year input field
    // 2. From the first initial date
    // 3. Fallback to current year
    if (yearInput && yearInput.value) {
        currentYear = parseInt(yearInput.value, 10);
    } else if (initialDates.length > 0) {
        currentYear = parseInt(initialDates[0].split('-')[0], 10);
    } else {
        currentYear = today.getFullYear();
    }

    // Determine initial month:
    // Find the first month with a selected date in the current year
    const datesOfYear = initialDates
        .map(d => ({
            year: parseInt(d.split('-')[0], 10),
            month: parseInt(d.split('-')[1], 10) - 1 // JS months: 0-11
        }))
        .filter(d => d.year === currentYear) // Keep only dates for current year
        .sort((a,b) => a.month - b.month); // Sort ascending by month

    currentMonth = datesOfYear.length > 0 ? datesOfYear[0].month : today.getMonth();

    // CSS classes for different selection modes
    const modeClasses = {
        holiday: 'bg-yellow-200',
        vacation: 'bg-green-500 text-white',
    };

    /**
     * Render the calendar grid for the current year and month
     */
    function render() {
        // Update month/year label
        monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;

        // Clear previous calendar cells
        calendarGrid.innerHTML = '';

        // Calculate first and last day of the current month
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const daysInMonth = lastDay.getDate();

        // Adjust weekday so Monday = 0, Sunday = 6
        let startWeekday = firstDay.getDay();
        startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

        // Add empty cells before the first day to align weekdays
        for (let i = 0; i < startWeekday; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'border aspect-square bg-gray-50';
            calendarGrid.appendChild(emptyCell);
        }

        // Filter selected dates for the current year
        const filteredDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));

        // Generate a cell for each day of the month
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${pad(currentMonth + 1)}-${pad(d)}`;
            const dateObj = new Date(currentYear, currentMonth, d);

            // Get weekday (0=Monday, 6=Sunday)
            let weekday = dateObj.getDay();
            weekday = weekday === 0 ? 6 : weekday - 1;

            const isWeekend = weekday === 5 || weekday === 6; // Saturday or Sunday
            const isSelected = filteredDates.includes(dateStr);

            const cell = document.createElement('button');
            cell.type = 'button';

            // Determine background color based on selection or weekend
            let bgClass = 'bg-gray-100';
            if (isSelected) bgClass = modeClasses[mode] || 'bg-yellow-200';
            else if (isWeekend) bgClass = 'bg-gray-200';

            cell.className = ['border aspect-square p-2 text-left transition hover:bg-gray-300', bgClass].join(' ');
            cell.dataset.date = dateStr;

            // Display day number inside the cell
            cell.innerHTML = `<div class="text-xs text-gray-600">${d}</div>`;

            // Click handler: toggle selection
            cell.addEventListener('click', () => {
                const checkbox = document.getElementById('enableHolidayMode');
                if ((mode === 'holiday' && checkbox?.checked) || (mode === 'vacation' && checkbox?.checked)) {
                    if (selectedDates.has(dateStr)) selectedDates.delete(dateStr);
                    else selectedDates.add(dateStr);

                    // Update hidden input with selected dates
                    if (selectedDatesInput) {
                        const updatedDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));
                        selectedDatesInput.value = JSON.stringify(updatedDates.sort());
                    }
                    render(); // Re-render calendar
                }
            });

            calendarGrid.appendChild(cell);
        }

        // Update hidden input at the end of rendering
        if (selectedDatesInput) {
            const updatedDates = Array.from(selectedDates).filter(date => date.startsWith(String(currentYear)));
            selectedDatesInput.value = JSON.stringify(updatedDates.sort());
        }

        // Disable navigation buttons at edges
        btnPrev.disabled = currentMonth === 0;
        btnNext.disabled = currentMonth === 11;
    }

    // Event listeners for month navigation buttons
    btnPrev?.addEventListener('click', () => {
        if (currentMonth > 0) currentMonth--;
        render();
    });

    btnNext?.addEventListener('click', () => {
        if (currentMonth < 11) currentMonth++;
        render();
    });

    // Handle year input changes
    const yearInputElem = yearInput || document.querySelector('input[name="year"]');

    function handleYearChange() {
        const newYear = parseInt(yearInputElem.value, 10) || today.getFullYear();
        currentYear = newYear;

        // Find the first month with selected dates in the new year
        const datesOfYear = Array.from(selectedDates)
            .map(d => ({
                year: parseInt(d.split('-')[0], 10),
                month: parseInt(d.split('-')[1], 10) - 1
            }))
            .filter(d => d.year === newYear)
            .sort((a,b) => a.month - b.month);

        currentMonth = datesOfYear.length > 0 ? datesOfYear[0].month : 0; // Default to January if none
        render();
    }

    yearInputElem?.addEventListener('input', handleYearChange);
    yearInputElem?.addEventListener('change', handleYearChange);

    // Initial render
    render();
}

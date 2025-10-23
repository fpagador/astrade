import './bootstrap';
import Alpine from 'alpinejs';

import { createIcons, icons } from 'lucide';

import { initCalendars, confirmDelete, calendarForm, initCloneSelect, validateCalendarTemplateForm } from './calendar';
import { cloneTaskForm, editTaskForm, imageModal, calendarView, actionTaskModal, dailyControls, enhancedLoadTasks} from './task';
import { initCompaniesPhones } from './company';
import { imageSelector } from './imageSelector';
import { userSelector } from './users';
import { initUsersTasksChart, initTasksProportionChart, initTaskPerformanceHistoryChart, initEmployeesByCompanyChart } from './dashboard';
import { customConfirm, customAlert } from './confirm.js';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";

window.Alpine = Alpine;
window.createIcons = createIcons;
window.lucideIcons = icons;
window.cloneTaskForm = cloneTaskForm;
window.editTaskForm = editTaskForm;
window.imageModal = imageModal;

window.calendarView = calendarView;
window.dailyControls = dailyControls;
window.enhancedLoadTasks = enhancedLoadTasks;
window.actionTaskModal = actionTaskModal;

window.imageSelector = imageSelector;
window.initCalendars = initCalendars;
window.calendarForm = calendarForm;
window.validateCalendarTemplateForm = validateCalendarTemplateForm;
window.confirmDelete = confirmDelete;
window.userSelector = userSelector;

window.customConfirm = customConfirm;
window.customAlert = customAlert;

window.initUsersTasksChart = initUsersTasksChart;
window.initTasksProportionChart = initTasksProportionChart;
window.initEmployeesByCompanyChart = initEmployeesByCompanyChart;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;
    if (page === 'admin-companies-create' || page === 'admin-companies-edit') {
        initCompaniesPhones();
    }

    initCalendars();
    createIcons({ icons });

    initCloneSelect();
    initUsersTasksChart();
    initTasksProportionChart();
    initEmployeesByCompanyChart();
    initTaskPerformanceHistoryChart();

    // === Translation of required fields tooltips ===
    const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredFields.forEach(field => {
        field.addEventListener('invalid', function(event) {
            this.setCustomValidity('Por favor completa este campo');
        });
        field.addEventListener('input', function() {
            this.setCustomValidity('');
        });
    });

    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('relative', 'group');

        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);

        const tooltip = document.createElement('div');
        tooltip.className = 'absolute left-0 -top-6 bg-red-600 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none';
        tooltip.textContent = 'Por favor completa este campo';

        wrapper.appendChild(tooltip);
    });

    const confirmForms = document.querySelectorAll('form[data-confirm-delete], form.delete-form');
    confirmForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            let message = '¿Está seguro que desea continuar?';
            if (form.dataset.confirmDelete) {
                message = form.dataset.confirmDelete;
            } else if (form.dataset.message) {
                message = form.dataset.message;
            }

            const confirmed = await customConfirm(message);
            if (confirmed) {
                form.submit();
            }
        });
    });

    flatpickr("input[data-flatpickr]", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        locale: Spanish,
        firstDayOfWeek: 1
    });

});

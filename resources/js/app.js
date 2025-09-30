import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { initCalendars, confirmDelete, calendarForm  } from './calendar';
import { cloneTaskForm, editTaskForm, imageModal, calendarView, actionTaskModal, dailyControls, enhancedLoadTasks} from './task';
import { initCompaniesPhones } from './company';
import { imageSelector } from './imageSelector';
import { userSelector } from './users';


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
window.confirmDelete = confirmDelete;
window.userSelector = userSelector;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;
    if (page === 'admin-companies-create' || page === 'admin-companies-edit') {
        initCompaniesPhones();
    }

    initCalendars();
    createIcons({ icons });

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
});

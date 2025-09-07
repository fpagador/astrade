import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { initCalendars } from './calendar';
import { cloneTaskForm, editTaskForm, imageModal } from './task';
import './users';
import { initCompaniesPhones } from './company';
import { imageSelector } from './imageSelector';

window.Alpine = Alpine;
window.createIcons = createIcons;
window.lucideIcons = icons;
window.cloneTaskForm = cloneTaskForm;
window.editTaskForm = editTaskForm;
window.imageModal = imageModal;
window.imageSelector = imageSelector;
window.initCalendars = initCalendars;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;
    if (page === 'admin-companies-create' || page === 'admin-companies-edit') {
        initCompaniesPhones();
    }

    initCalendars();

    createIcons({ icons });
});

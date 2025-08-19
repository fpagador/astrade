import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { initCalendars } from './calendar';
import { cloneTaskForm, editTaskForm, imageModal } from './task';
import './users';
import { initCompaniesPhones } from './company';

window.Alpine = Alpine;
window.cloneTaskForm = cloneTaskForm;
window.editTaskForm = editTaskForm;
window.imageModal = imageModal;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const page = document.body.dataset.page;
    if (page === 'admin-companies-create' || page === 'admin-companies-edit') {
        initCompaniesPhones();
    }

    initCalendars();

    createIcons({ icons });
});

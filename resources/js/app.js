import './bootstrap';

import { createIcons, icons } from 'lucide';
createIcons({ icons });

import Alpine from 'alpinejs';

// Add a js file to the task view
import {
    cloneTaskForm,
    editTaskForm,
    imageModal
} from './task';

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
});




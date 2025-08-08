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
window.Alpine = Alpine;

window.cloneTaskForm = cloneTaskForm;
window.editTaskForm = editTaskForm;
window.imageModal = imageModal;

Alpine.start();




import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
    safelist: [
        // Calendar Colors
        'bg-green-500',
        'bg-orange-400',
        'bg-yellow-200',
        'bg-gray-300',
        'bg-gray-100',
        'bg-green-900',
        'bg-red-700',
        'bg-gray-500',
        'bg-red-900',

        // Company Colors
        'bg-indigo-100 text-indigo-900',
        'bg-green-100 text-green-900',
        'bg-red-100 text-yellow-900',
        'bg-pink-100 text-pink-900',
        'bg-purple-100 text-purple-900'
    ],
};

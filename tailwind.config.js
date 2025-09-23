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
            colors: {
                green: {
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
                red: {
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d',
                },
            },
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
        'bg-indigo-900',
        'hover:bg-indigo-800',
        'hover:bg-gray-400',
        'hover:bg-red-600',
        'hover:bg-green-800',
        'hover:bg-red-800',
        // Company Colors
        'bg-indigo-100',
        'text-indigo-900',
        'bg-green-100',
        'text-green-900',
        'bg-red-100',
        'text-yellow-900',
        'bg-pink-100',
        'text-pink-900',
        'bg-purple-100',
        'text-purple-900'
    ]
};

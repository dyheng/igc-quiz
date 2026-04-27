import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f5f7fb',
                    100: '#e9eef7',
                    200: '#cdd8ec',
                    300: '#9eb1d8',
                    400: '#6c87bf',
                    500: '#4a67a6',
                    600: '#374f88',
                    700: '#2d3f6c',
                    800: '#26345a',
                    900: '#1f2a48',
                    950: '#141a2e',
                },
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 4px 12px -2px rgb(15 23 42 / 0.05)',
            },
            borderRadius: {
                xl: '0.875rem',
                '2xl': '1.125rem',
            },
        },
    },
    plugins: [forms],
};

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
                // Designed for legibility: tells 0/O and 1/l/I apart (references, e-mails).
                sans: ['"Atkinson Hyperlegible"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // LaraDesk petrol blue; 700 is the main brand colour.
                brand: {
                    50: '#ECF6F8',
                    100: '#D3EBEF',
                    200: '#A7D6DE',
                    300: '#6FB9C6',
                    400: '#3A98A9',
                    500: '#1B7A8C',
                    600: '#116A7C',
                    700: '#0E5E6F',
                    800: '#0A4652',
                    900: '#08353E',
                },
                ink: '#1D2A33',
                paper: '#F3F6F7',
            },
        },
    },

    plugins: [forms],
};

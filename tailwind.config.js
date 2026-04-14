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
                sans: ['Carbon', 'ui-sans-serif', 'system-ui', ...defaultTheme.fontFamily.sans],
            },
            // Carbon ships as Regular only; keep all utilities at400 to avoid faux-bold
            fontWeight: {
                thin: '400',
                extralight: '400',
                light: '400',
                normal: '400',
                medium: '400',
                semibold: '400',
                bold: '400',
                extrabold: '400',
                black: '400',
            },
        },
    },

    plugins: [forms],
};

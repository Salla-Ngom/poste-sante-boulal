import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // === Largeurs élargies pour grands écrans (24"+) ===
            // Avant : max-w-7xl = 1280px (trop étroit sur grand écran)
            // Après : max-w-7xl = 1440px (mieux adapté aux écrans modernes)
            maxWidth: {
                '7xl': 'none',          // 1440px (au lieu de 1280px par défaut)
                'screen-2xl': '100rem',  // 1600px (pour écrans 24"+)
                'screen-3xl': '112rem',  // 1792px (pour écrans 27"+)
            },
        },
    },

    plugins: [forms],
};

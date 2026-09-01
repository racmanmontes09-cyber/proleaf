import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                leaf: {
                    50: '#F0FDF4',
                    100: '#D8F3DC',
                    200: '#B7E4C7',
                    300: '#74C69D',
                    400: '#52B788',
                    500: '#40916C',
                    600: '#2D6A4F',
                    700: '#1B4332',
                    800: '#081C15',
                    900: '#040F0C',
                },
                primary: '#2D6A4F',
                secondary: '#40916C',
                accent: '#95D5B2',
                'leaf-bg': '#F8FAF8',
                'leaf-text': '#1B4332',
            },
            boxShadow: {
                'soft-sm': '0 2px 8px -2px rgba(27, 67, 50, 0.05)',
                'soft-md': '0 4px 16px -4px rgba(27, 67, 50, 0.08)',
                'soft-lg': '0 8px 24px -6px rgba(27, 67, 50, 0.12)',
                'glass': '0 8px 32px 0 rgba(27, 67, 50, 0.06)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
                '4xl': '2rem',
            }
        },
    },

    plugins: [forms],
};


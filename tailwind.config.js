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
            screens: {
                // Punto de quiebre del layout con sidebar (spec: colapsa bajo 900px)
                nav: '900px',
            },
            fontFamily: {
                sans: ['"Source Sans 3"', ...defaultTheme.fontFamily.sans],
                display: ['"Work Sans"', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                base: ['15px', '1.5'],
            },
            colors: {
                // Sidebar / marca
                navy: {
                    DEFAULT: '#163A6B',
                    active: '#1E4D8C',
                },
                // Fondos de la aplicación
                app: {
                    bg: '#F5F7FA',
                    login: '#EAF1FA',
                },
                // Texto
                ink: {
                    DEFAULT: '#1C2733',
                    muted: '#5B6B7C',
                    label: '#3A4856',
                },
                // Bordes (usar como border-line / border-line-input)
                line: {
                    DEFAULT: '#DDE3EA',
                    input: '#CBD4DE',
                },
                // Color primario de acciones (botones/links)
                primary: {
                    DEFAULT: '#1E4D8C',
                    hover: '#163A6B',
                },
                // Estados semánticos
                success: {
                    bg: '#E7F3ED',
                    text: '#1F5E43',
                    DEFAULT: '#2E7D5B',
                },
                warning: {
                    bg: '#FDF4E3',
                    text: '#7A4E0E',
                },
                danger: {
                    bg: '#FBECEC',
                    text: '#8E2F2F',
                    hover: '#A63A3A',
                },
                info: {
                    bg: '#EAF1FA',
                    text: '#1E4D8C',
                },
                neutral: {
                    bg: '#EEF1F4',
                    text: '#4A5866',
                },
            },
        },
    },

    plugins: [forms],
};

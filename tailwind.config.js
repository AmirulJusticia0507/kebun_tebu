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
            colors: {
                primary: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                    950: '#022c22',
                },
                emerald: {
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669',
                },
                amber: {
                    400: '#fbbf24',
                    500: '#f59e0b',
                },
                rose: {
                    500: '#f43f5e',
                    600: '#e11d48',
                }
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                display: ['Outfit', '"Plus Jakarta Sans"', 'sans-serif'],
            },
            boxShadow: {
                'glass': '0 8px 32px 0 rgba(0, 0, 0, 0.08)',
                'glass-lg': '0 12px 40px 0 rgba(0, 0, 0, 0.12)',
                'glow-emerald': '0 0 20px rgba(16, 185, 129, 0.35)',
                'glow-amber': '0 0 20px rgba(245, 158, 11, 0.35)',
                'glow-rose': '0 0 20px rgba(244, 63, 94, 0.35)',
            },
            backgroundImage: {
                'mesh-gradient': 'radial-gradient(at 40% 20%, rgba(16, 185, 129, 0.15) 0px, transparent 50%), radial-gradient(at 80% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%), radial-gradient(at 0% 50%, rgba(168, 85, 247, 0.1) 0px, transparent 50%)',
                'hero-pattern': 'radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.08) 0%, transparent 60%)',
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
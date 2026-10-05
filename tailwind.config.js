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
            // Sengaja TIDAK memakai font dari Google Fonts (beda dengan versi
            // PHP lama yang memuat "Inter" dari fonts.googleapis.com di setiap
            // kunjungan halaman). Font sistem bawaan browser dimuat instan
            // tanpa request jaringan tambahan sama sekali - salah satu alasan
            // utama tampilan baru ini terasa lebih cepat.
            fontFamily: {
                sans: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
            },
            colors: {
                // Warna utama aplikasi: biru tua yang profesional, dipakai
                // untuk tombol utama, tautan aktif, dan sidebar admin.
                brand: {
                    50: '#eef4ff',
                    100: '#dbe6fe',
                    200: '#bfd3fe',
                    300: '#93b4fd',
                    400: '#6090fa',
                    500: '#3b6ef6',
                    600: '#254feb',
                    700: '#1d3dd8',
                    800: '#1e34af',
                    900: '#1e318a',
                    950: '#161f54',
                },
            },
        },
    },

    plugins: [forms],
};

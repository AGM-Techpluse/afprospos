import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Patrick Hand', {
                    weights: [400],
                }),
                // UI/UX design system doc §4: "Inter" for admin + customer
                // body/UI text, "Glass Antiqua" for customer
                // display/headings. Glass Antiqua only ships weight 400 on
                // Google/Bunny Fonts — every font-customer-display usage
                // paired with font-bold/font-extrabold relies on the
                // browser's synthetic bold, since no real bold cut exists.
                bunny('Inter', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('Glass Antiqua', {
                    weights: [400],
                }),
            ],
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

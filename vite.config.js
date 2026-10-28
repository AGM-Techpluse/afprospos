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
                // UI/UX design system doc §4: "Inter" (admin + customer body/UI
                // text) and "Plus Jakarta Sans" (customer display/headings).
                // tokens.css already referenced these two names, but neither
                // was ever actually self-hosted — every admin and customer
                // page was silently falling back to the browser's system-ui
                // font the whole time.
                bunny('Inter', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('Plus Jakarta Sans', {
                    weights: [400, 600, 700, 800],
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

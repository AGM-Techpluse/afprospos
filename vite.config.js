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
                // body/UI text, "Space Grotesk" for customer display/headings
                // (tops out at weight 700 on Google/Bunny Fonts — every
                // font-customer-display usage uses font-bold, not
                // font-extrabold, to match).
                bunny('Inter', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('Space Grotesk', {
                    weights: [400, 500, 600, 700],
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

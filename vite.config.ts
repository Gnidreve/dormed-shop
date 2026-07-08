import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const isSvelteCheck = process.argv.some((argument) =>
    argument.includes('svelte-check'),
);

if (isSvelteCheck) {
    process.env.LARAVEL_BYPASS_ENV_CHECK ??= '1';
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        svelte(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        watch: {
            // Vite/chokidar only ignores node_modules/.git by default - the
            // PHP vendor tree (tens of thousands of files), storage (logs/
            // cache) and local tool dirs pushed past the OS inotify watch
            // limit (ENOSPC: System limit for number of file watchers
            // reached) since none of this is ever meant to trigger a
            // frontend rebuild anyway.
            ignored: [
                '**/vendor/**',
                '**/storage/**',
                '**/.codex/**',
                '**/.claude/**',
            ],
        },
    },
});

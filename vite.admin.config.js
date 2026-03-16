import {defineConfig} from 'vite';
import laravel, {refreshPaths} from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            hotFile: 'public/admin.hot',
            buildDirectory: 'build/admin',
            input: ['resources/js/filament/admin/app.js', 'resources/css/filament/admin/app.css'],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
                'app/Filament/**',
                'app/Providers/**',
                'app/Http/Livewire/**',
            ]
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

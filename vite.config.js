import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    base: '/poste-sante-boulal/public/build/',

    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        VitePWA({
            registerType: 'autoUpdate',
            manifest: false, // on utilise public/manifest.webmanifest directement
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        // Ne jamais mettre en cache les requêtes Inertia (X-Inertia header)
                        urlPattern: ({ request }) =>
                            request.mode === 'navigate' &&
                            !request.headers.get('X-Inertia'),
                        handler: 'NetworkOnly',
                    },
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp|woff2)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'assets-cache',
                            expiration: { maxEntries: 60, maxAgeSeconds: 2592000 },
                        },
                    },
                ],
            },
        }),
    ],
});

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = { ...loadEnv(mode, process.cwd(), ''), ...process.env };
    const serverPort = env.VITE_DEV_SERVER_PORT
        ? Number(env.VITE_DEV_SERVER_PORT)
        : undefined;
    const hmrClientPort = env.VITE_HMR_CLIENT_PORT
        ? Number(env.VITE_HMR_CLIENT_PORT)
        : undefined;
    const corsOrigin = env.VITE_CORS_ORIGIN || env.APP_URL;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            inertia(),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            wayfinder({
                formVariants: true,
            }),
        ],
        server: {
            host: env.VITE_DEV_SERVER_HOST || '0.0.0.0',
            port: serverPort,
            strictPort: serverPort !== undefined,
            origin: env.VITE_DEV_SERVER_URL || undefined,
            cors: corsOrigin ? { origin: corsOrigin } : undefined,
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
                port: Number(env.VITE_HMR_PORT || 9000),
                clientPort: hmrClientPort,
            },
        },
    };
});

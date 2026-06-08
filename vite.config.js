import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path'

const hmrHost = process.env.VITE_HMR_HOST || 'vite.frost-relay.ws.cloudagent.mintopia.net';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '~tabler': path.resolve(__dirname, 'node_modules/@tabler/core'),
            '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
        }
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        origin: `https://${hmrHost}`,
        hmr: {
            host: hmrHost,
            protocol: 'wss',
            clientPort: 443,
        },
    },
});

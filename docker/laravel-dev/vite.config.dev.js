// Harness override for the Laravel app's Vite config (docker/laravel-dev assets).
//
// Vite must *bind* 0.0.0.0 inside the container so the published 5173 works, but
// laravel-vite-plugin writes that raw address into public/hot, making Blade emit
// http://0.0.0.0:5173/... asset URLs that no browser can reach. Advertising HMR
// on `localhost` fixes the URLs the page emits while keeping the 0.0.0.0 bind.
import { defineConfig } from 'vite';
import appConfig from './vite.config.js';

const base = typeof appConfig === 'function'
    ? appConfig({ command: 'serve', mode: 'development', isSsrBuild: false, isPreview: false })
    : appConfig;

export default defineConfig({
    ...base,
    plugins: base.plugins,
    server: {
        ...(base.server ?? {}),
        host: '0.0.0.0',
        hmr: {
            ...((base.server ?? {}).hmr ?? {}),
            host: 'localhost',
        },
    },
});
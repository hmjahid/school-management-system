/* Eskoofy theme — service worker.
 * Network-first for navigation with an offline fallback, stale-while-revalidate
 * for page resources, cache-first for same-origin static assets.
 *
 * Only OK (2xx) responses are cached; redirects and error responses are never
 * stored or served from cache, so a stale/redirected entry can never hijack a
 * navigation.
 */

const CACHE_NAME = 'eskoofy-wp-theme-v3';
const OFFLINE_URL = '/offline';

function isCacheable(response) {
    return response && response.ok && !response.redirected;
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => Promise.all([
                cache.add('/'),
                cache.add(OFFLINE_URL),
            ]))
            .catch(() => self.skipWaiting())
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (!sameOrigin) return;

    if (url.pathname === '/manifest.json' || url.pathname === '/sw.js') {
        event.respondWith(fetch(request));
        return;
    }

    if (request.mode === 'navigate') {
        // Never cache authenticated/admin surfaces.
        if (
            url.pathname.startsWith('/dashboard') ||
            url.pathname.startsWith('/wp-admin') ||
            url.pathname.startsWith('/login') ||
            url.pathname.startsWith('/logout') ||
            url.pathname.startsWith('/api')
        ) {
            event.respondWith(fetch(request));
            return;
        }
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (isCacheable(response)) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() =>
                    caches.match(request)
                        .then((cached) => (cached && isCacheable(cached) ? cached : null))
                        .then((cached) => cached || caches.match('/'))
                        .then((root) => (root && isCacheable(root) ? root : caches.match(OFFLINE_URL)))
                )
        );
        return;
    }

    if (sameOrigin && (url.pathname.startsWith('/wp-content/themes/') || url.pathname.startsWith('/wp-includes/'))) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    if (isCacheable(response)) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                });
                return cached || network;
            })
        );
    }
});
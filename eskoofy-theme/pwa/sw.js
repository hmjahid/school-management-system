/* Eskoofy theme — service worker.
 * Network-first for navigation with an offline fallback, stale-while-revalidate
 * for page resources, cache-first for same-origin static assets. */

const CACHE_NAME = 'eskoofy-theme-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(['/', OFFLINE_URL]))
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
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    return response;
                })
                .catch(() =>
                    caches.match(request)
                        .then((cached) => cached || caches.match('/'))
                        .then((root) => root || caches.match(OFFLINE_URL))
                )
        );
        return;
    }

    if (sameOrigin && (url.pathname.startsWith('/wp-content/themes/') || url.pathname.startsWith('/wp-includes/'))) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    if (response && response.ok) {
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
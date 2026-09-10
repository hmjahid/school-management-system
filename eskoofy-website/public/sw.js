/*
 * Eskoofy service worker — public marketing site only.
 *
 * Caching model:
 *  - PRECACHE: the public shell (marketing pages + PWA assets). Only public GET
 *    pages; /account, /admin, /api and /checkout are NEVER cached.
 *  - Network-first for navigations, with precache then /offline.html as fallback.
 *  - Stale-while-revalidate for assets (same-origin + the Tailwind CDN) so
 *    offline pages keep their layout.
 *
 * Bump the CACHE constant on every deploy that changes the cache shape.
 */
'use strict';

const CACHE = 'eskofy-pwa-v1';
const PRECACHE = [
    '/',
    '/blog',
    '/pricing',
    '/features',
    '/about',
    '/contact',
    '/manifest.json',
    '/favicon.svg',
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/maskable-512.png',
    '/icons/apple-touch-icon.png',
    'https://cdn.tailwindcss.com',
];

const EXCLUDED_PREFIXES = ['/api/', '/account', '/admin', '/checkout'];

function isExcluded(url) {
    return EXCLUDED_PREFIXES.some(function (prefix) {
        return url.pathname.indexOf(prefix) === 0;
    });
}

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) {
            return cache.addAll(PRECACHE);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key.indexOf('eskofy-pwa-') === 0 && key !== CACHE;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    var request = event.request;
    if (request.method !== 'GET') return;

    var url = new URL(request.url);
    if (url.origin === location.origin && isExcluded(url)) return;

    if (request.mode === 'navigate') {
        // Network-first for pages so content is fresh; fall back to precache,
        // then to the offline page.
        event.respondWith(
            fetch(request).then(function (response) {
                var copy = response.clone();
                caches.open(CACHE).then(function (cache) { cache.put(request, copy); });
                return response;
            }).catch(function () {
                return caches.match(request).then(function (cached) {
                    return cached || caches.match('/offline.html');
                });
            })
        );
        return;
    }

    // Assets: serve from cache, refresh in the background.
    event.respondWith(
        caches.match(request).then(function (cached) {
            var network = fetch(request).then(function (response) {
                if (response && response.status === 200) {
                    var copy = response.clone();
                    caches.open(CACHE).then(function (cache) { cache.put(request, copy); });
                }
                return response;
            }).catch(function () {
                return cached;
            });
            return cached || network;
        })
    );
});
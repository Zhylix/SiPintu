const CACHE_NAME = 'sipintu-pwa-v3';
const OFFLINE_URL = '/offline.html';

// 1. Install Event: Skip waiting immediately to activate fast
self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                OFFLINE_URL,
                '/manifest.webmanifest',
                '/manifest.json',
                '/favicon.ico',
                '/icons/icon-192x192.png',
                '/icons/icon-512x512.png'
            ]).catch((err) => {
                console.warn('[PWA SW] Cache notice:', err);
            });
        })
    );
});

// 2. Activate Event: Claim all clients immediately and clear obsolete cache
self.addEventListener('activate', (event) => {
    event.waitUntil(
        Promise.all([
            caches.keys().then((keys) => {
                return Promise.all(
                    keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
                );
            }),
            self.clients.claim()
        ])
    );
});

// 3. Fetch Event: Required by Chromium for PWA installability heuristic
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Skip cross-origin, OAuth, Livewire, and API requests
    if (url.origin !== self.location.origin) {
        return;
    }
    if (url.pathname.startsWith('/oauth') || url.pathname.startsWith('/livewire') || url.pathname.startsWith('/api')) {
        return;
    }

    // HTML Navigation requests: Network first with graceful offline fallback
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const cachedOffline = await cache.match(OFFLINE_URL);
                return cachedOffline || new Response('Anda sedang offline.', {
                    headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                });
            })
        );
        return;
    }

    // Static assets: Cache first, fallback to network
    if (
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.woff2')
    ) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {});
            })
        );
        return;
    }

    event.respondWith(fetch(event.request));
});

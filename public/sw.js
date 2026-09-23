const CACHE_NAME = 'sipintu-pwa-v6';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

// 1. Install Event: Pre-cache offline page and essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA] Pre-cache non-critical notice:', err);
            });
        })
    );
    self.skipWaiting();
});

// 2. Activate Event: Clean up old caches and claim all clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event: Network-first with offline fallback & safe Response handling
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Skip backend API, OAuth, Livewire, and Vite development/HMR paths
    if (
        url.pathname.startsWith('/oauth') ||
        url.pathname.startsWith('/livewire') ||
        url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/@') ||
        url.pathname.startsWith('/node_modules') ||
        url.port === '5173'
    ) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                return response;
            })
            .catch(async () => {
                try {
                    // Try to retrieve from cache
                    const cachedResponse = await caches.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // If it is a page navigation request, return offline.html
                    if (event.request.mode === 'navigate') {
                        const offlineFallback = await caches.match(OFFLINE_URL);
                        if (offlineFallback) {
                            return offlineFallback;
                        }
                    }
                } catch (e) {
                    console.warn('[PWA] Cache match notice:', e);
                }

                // If not in cache and network failed, provide a clean fallback Response
                // to prevent Chromium "TypeError: Failed to convert value to 'Response'"
                return new Response('Layanan sedang tidak tersedia (offline)', {
                    status: 503,
                    statusText: 'Service Unavailable',
                    headers: new Headers({
                        'Content-Type': 'text/plain; charset=utf-8'
                    })
                });
            })
    );
});

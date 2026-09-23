const CACHE_NAME = 'sipintu-pwa-v5';

// 1. Install Event: Fast activation without blocking network pre-cache
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// 2. Activate Event: Claim all clients immediately
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

// 3. Fetch Event: Fulfills Chromium PWA requirement
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Skip cross-origin or backend mutation paths
    if (url.origin !== self.location.origin) {
        return;
    }
    if (url.pathname.startsWith('/oauth') || url.pathname.startsWith('/livewire') || url.pathname.startsWith('/api')) {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

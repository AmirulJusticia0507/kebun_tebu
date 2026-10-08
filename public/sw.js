import { clientsClaim, skipWaiting } from 'workbox-skip-waiting';
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst, StaleWhileRevalidate, NetworkFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { precache } from 'workboxPrecaching';

// Claim clients immediately
skipWaiting();
clientsCall();

// Precache all assets registered by Vite
precache(self.__WB_MANIFEST);

// ======================
// 1. Google Fonts Cache (CacheFirst)
// ======================
registerRoute(
    /^https:\/\/fonts\.googleapis\.com\/.*/i,
    new CacheFirst({
        cacheName: 'google-fonts-cache',
        expiration: {
            maxEntries: 10,
            maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
        },
        cacheableResponse: {
            statuses: [0, 200],
        },
    })
);

// ======================
// 2. Google Gstatic Fonts (CacheFirst)
// ======================
registerRoute(
    /^https:\/\/fonts\.gstatic\.com\/.*/i,
    new CacheFirst({
        cacheName: 'gstatic-fonts-cache',
        expiration: {
            maxEntries: 10,
            maxAgeSeconds: 60 * 60 * 24 * 365,
        },
        cacheableResponse: {
            statuses: [0, 200],
        },
    })
);

// ======================
// 3. OpenStreetMap Tiles (CacheFirst - static tiles)
// ======================
registerRoute(
    /^https:\/\/.*\.tile\.openstreetmap\.org\/.*/i,
    new CacheFirst({
        cacheName: 'osm-tiles-cache',
        expiration: {
            maxEntries: 100,
            maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
        },
        cacheableResponse: {
            statuses: [0, 200],
        },
    })
);

// ======================
// 4. API Responses (NetworkFirst)
// ======================
registerRoute(
    /^http:\/\/localhost:8000\/api/,
    new NetworkFirst({
        cacheName: 'api-cache',
        expiration: {
            maxEntries: 50,
            maxAgeSeconds: 60 * 60, // 1 hour
        },
        networkTimeoutSeconds: 3,
    })
);

// ======================
// 5. Image Optimization (Cache First for thumbnails)
// ======================
registerRoute(
    /\/storage\/app\/public\/reports\/.*\.(png|jpg|jpeg|webp)$/,
    new CacheFirst({
        cacheName: 'report-images-cache',
        expiration: {
            maxEntries: 50,
            maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
        },
        cacheableResponse: {
            statuses: [0, 200],
        },
    })
);

// ======================
// 6. App Shell (Network First for HTML/JSON)
// ======================
registerRoute(
    ({request}) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: 'app-shell',
        expiration: {
            maxEntries: 10,
            maxAgeSeconds: 60 * 60 * 24, // 1 day
        },
    })
);

// ======================
// 7. Offline Home Page
// ======================
const offlineHandler = new CacheFirst({
    cacheName: 'offline-page',
    expiration: {
        maxEntries: 1,
        maxAgeSeconds: 86400, // 1 day
    },
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() =>
                caches.match('/offline.html')
            )
        );
        return;
    }
    
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request);
        }).catch(() => caches.match('/offline.html'))
    );
});

// Message handler for syncing drafts from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'sync-drafts') {
        // Handle sync request from client
        event.waitUntil(syncDraftsFromClient());
    }
});

async function syncDraftsFromClient() {
    // This would be implemented to sync drafts from IndexedDB
    // to the server when the service worker starts syncing
    console.log('Syncing drafts from service worker');
}
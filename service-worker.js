/**
 * PizzaG - Service Worker
 * Handles caching for offline functionality
 */

const CACHE_NAME = 'pizzag-v2';
const STATIC_CACHE = 'pizzag-static-v2';
const DYNAMIC_CACHE = 'pizzag-dynamic-v2';

// Files to cache immediately on install
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/manifest.json',
    '/logo.jpeg',
    '/menu-a.jpg',
    '/menu-b.jpg',
    // Add your product images here
    '/products/Regular Pizza.png'
];

// External resources to cache
const EXTERNAL_ASSETS = [
    'https://cdn.tailwindcss.com',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

/**
 * Install Event
 * Cache static assets when service worker is installed
 */
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[Service Worker] Caching static assets');
                // Cache static assets - don't fail if some assets are missing
                return Promise.allSettled(
                    STATIC_ASSETS.map(asset =>
                        cache.add(asset).catch(err => {
                            console.warn(`[Service Worker] Failed to cache: ${asset}`, err);
                        })
                    )
                );
            })
            .then(() => {
                // Force this service worker to become active
                return self.skipWaiting();
            })
    );
});

/**
 * Activate Event
 * Clean up old caches when new service worker activates
 */
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            // Delete any cache that doesn't match current version
                            return name.startsWith('pizzag-') &&
                                   name !== STATIC_CACHE &&
                                   name !== DYNAMIC_CACHE;
                        })
                        .map((name) => {
                            console.log('[Service Worker] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                // Take control of all pages immediately
                return self.clients.claim();
            })
    );
});

/**
 * Fetch Event
 * Serve cached content when offline, cache new requests
 * Strategy: Cache First, then Network (for static assets)
 * Strategy: Network First, then Cache (for dynamic content)
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http(s) requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Skip WhatsApp and tel links
    if (url.href.includes('wa.me') || url.href.includes('tel:')) {
        return;
    }

    // For same-origin requests, use Network First strategy (fresh content always)
    if (url.origin === location.origin) {
        event.respondWith(networkFirst(request));
    } else {
        // For external requests (CDN, etc.), use Stale While Revalidate
        event.respondWith(staleWhileRevalidate(request));
    }
});

/**
 * Cache First Strategy
 * Check cache first, if not found, fetch from network and cache
 */
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);

        // Only cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[Service Worker] Fetch failed:', error);

        // Return a fallback response for navigation requests
        if (request.mode === 'navigate') {
            const cache = await caches.open(STATIC_CACHE);
            return cache.match('/index.html');
        }

        // Return a simple error response for other requests
        return new Response('Offline - Content not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'text/plain'
            })
        });
    }
}

/**
 * Network First Strategy
 * Try network first, fall back to cache if offline
 */
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        if (request.mode === 'navigate') {
            const cache = await caches.open(STATIC_CACHE);
            return cache.match('/index.html');
        }

        return new Response('Offline - Content not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({ 'Content-Type': 'text/plain' })
        });
    }
}

/**
 * Stale While Revalidate Strategy
 * Return cached version immediately, update cache in background
 */
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);

    // Fetch fresh version in background
    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch((error) => {
            console.warn('[Service Worker] Background fetch failed:', error);
            return cachedResponse;
        });

    // Return cached version immediately, or wait for network
    return cachedResponse || fetchPromise;
}

/**
 * Handle messages from the main thread
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

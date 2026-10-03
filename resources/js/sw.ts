/// <reference lib="webworker" />
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { ExpirationPlugin } from 'workbox-expiration';
import {
    cleanupOutdatedCaches,
    matchPrecache,
    precacheAndRoute,
} from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import {
    CacheFirst,
    NetworkOnly,
    StaleWhileRevalidate,
} from 'workbox-strategies';

declare const self: ServiceWorkerGlobalScope;

/**
 * PaTH's service worker.
 *
 * - The app shell (built JS, CSS, fonts, icons) and the /offline page are precached.
 * - Page loads always go to the network, so personal pages are never stored. When there is no
 *   signal, the /offline page opens instead and shows the trips saved on the phone.
 * - Map tiles and listing photos are cached as they are seen, so saved trip areas work offline.
 */

void self.skipWaiting();
self.addEventListener('activate', (event) =>
    event.waitUntil(self.clients.claim()),
);

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

const offlinePage = '/offline';

registerRoute(
    ({ request }) => request.mode === 'navigate',
    async (options) => {
        try {
            return await new NetworkOnly().handle(options);
        } catch {
            return (await matchPrecache(offlinePage)) ?? Response.error();
        }
    },
);

registerRoute(
    ({ url }) =>
        url.hostname.endsWith('tile.openstreetmap.org') ||
        url.pathname.match(/\/\d+\/\d+\/\d+\.(png|jpg|webp)$/) !== null,
    new CacheFirst({
        cacheName: 'map-tiles',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({
                maxEntries: 1500,
                maxAgeSeconds: 30 * 24 * 60 * 60,
            }),
        ],
    }),
);

registerRoute(
    ({ url, request }) =>
        url.origin === self.location.origin &&
        url.pathname.startsWith('/storage/') &&
        request.destination === 'image',
    new CacheFirst({
        cacheName: 'photos',
        plugins: [
            new CacheableResponsePlugin({ statuses: [200] }),
            new ExpirationPlugin({
                maxEntries: 300,
                maxAgeSeconds: 30 * 24 * 60 * 60,
            }),
        ],
    }),
);

registerRoute(
    ({ url }) =>
        url.origin === self.location.origin &&
        url.pathname.startsWith('/icons/'),
    new StaleWhileRevalidate({ cacheName: 'icons' }),
);

/* Sent One — service worker (installability + offline shell) */
const CACHE = 'sent-one-v1';

self.addEventListener('install', (event) => {
	self.skipWaiting();
});

self.addEventListener('activate', (event) => {
	event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
	if (event.request.method !== 'GET') {
		return;
	}
	event.respondWith(
		fetch(event.request).catch(() => caches.match(event.request))
	);
});

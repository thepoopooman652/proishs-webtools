// A unique name for our app's cache
const CACHE_NAME = 'audiolab-neo-cache-v1';

// The list of files we want to cache for offline use
const urlsToCache = [
  '/',
  'index.html',
  'https://fonts.googleapis.com/icon?family=Material+Icons',
  'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto+Mono:wght@300;400;500&display=swap',
  'https://cdn.jsdelivr.net/npm/lamejs@1.2.1/lame.min.js'
];

// 'self' refers to the service worker itself
// This code runs when the service worker is first installed
self.addEventListener('install', event => {
  // We wait until the installation is complete
  event.waitUntil(
    // Open the cache by name
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        // Add all the files from our list to the cache
        return cache.addAll(urlsToCache);
      })
  );
});

// This code runs every time the browser tries to fetch a file (like the HTML, an image, or a script)
self.addEventListener('fetch', event => {
  event.respondWith(
    // Check if the requested file is already in our cache
    caches.match(event.request)
      .then(response => {
        // If we found the file in the cache, return it
        if (response) {
          return response;
        }
        // If the file is not in the cache, fetch it from the network
        return fetch(event.request);
      }
    )
  );
});

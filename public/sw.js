const CACHE_VERSION = 'waafibook-v1';
const STATIC_CACHE  = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const IMAGE_CACHE   = `${CACHE_VERSION}-images`;

// App shell — always available offline
const PRECACHE_URLS = [
  '/offline',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

// ── Install: pre-cache app shell ──────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

// ── Activate: purge old caches ────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k.startsWith('waafibook-') && ![STATIC_CACHE, DYNAMIC_CACHE, IMAGE_CACHE].includes(k))
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: route-based caching strategies ────────────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Only handle same-origin GET requests
  if (request.method !== 'GET' || url.origin !== location.origin) return;

  // Skip admin/super-admin routes from SW caching (always need fresh auth)
  if (url.pathname.startsWith('/super_admin')) return;

  // Images → Stale-While-Revalidate
  if (request.destination === 'image') {
    event.respondWith(staleWhileRevalidate(request, IMAGE_CACHE));
    return;
  }

  // Static assets (CSS, JS, fonts from /build/) → Cache-first
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/css/')) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // Navigation + app pages → Network-first, fall back to cache then offline
  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(networkFirstWithOfflineFallback(request));
    return;
  }

  // Everything else → Network-first with dynamic cache
  event.respondWith(networkFirst(request, DYNAMIC_CACHE));
});

// ── Background Sync: replay offline POS sales ─────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-pos-sales') {
    event.waitUntil(replayOfflineSales());
  }
});

// ── Strategy helpers ──────────────────────────────────────────────────────

async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('Network error', { status: 408 });
  }
}

async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || new Response('Network error', { status: 408 });
  }
}

async function networkFirstWithOfflineFallback(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    return caches.match('/offline');
  }
}

async function staleWhileRevalidate(request, cacheName) {
  const cache  = await caches.open(cacheName);
  const cached = await cache.match(request);
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => {});
  return cached || fetchPromise;
}

// ── Replay queued offline POS sales ──────────────────────────────────────
async function replayOfflineSales() {
  const db = await openPosDb();
  const tx = db.transaction('offline_sales', 'readwrite');
  const store = tx.objectStore('offline_sales');
  const sales = await getAllFromStore(store);

  for (const sale of sales) {
    try {
      const res = await fetch('/sales/invoice/store', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(sale.data),
      });
      if (res.ok) {
        const delTx = db.transaction('offline_sales', 'readwrite');
        delTx.objectStore('offline_sales').delete(sale.id);
      }
    } catch { /* will retry on next sync */ }
  }
}

function openPosDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('waafibook-pos', 1);
    req.onupgradeneeded = e => e.target.result.createObjectStore('offline_sales', { keyPath: 'id', autoIncrement: true });
    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = e => reject(e.target.error);
  });
}

function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const req = store.getAll();
    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = e => reject(e.target.error);
  });
}

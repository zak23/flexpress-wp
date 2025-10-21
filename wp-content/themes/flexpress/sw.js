
// FlexPress Service Worker
// - Never cache HTML
// - Cache static assets only (cache-first)
// - Bypass all admin routes completely

const CACHE_NAME = "flexpress-v2";
const ASSET_EXT = /(\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?|ttf|eot)(\?.*)?$)/i;

self.addEventListener("install", (event) => {
  // Skip waiting so updated SW takes control ASAP
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  // Clean up old caches if needed and take control of clients
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => {
      if (key !== CACHE_NAME) {
        return caches.delete(key);
      }
    }))).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Only handle GET requests
  if (req.method !== "GET") {
    return;
  }

  // Never intercept admin or login routes
  if (url.pathname.startsWith("/wp-admin/") || url.pathname === "/wp-login.php") {
    return;
  }

  // Cache-first for static assets only
  if (ASSET_EXT.test(url.pathname)) {
    event.respondWith(
      caches.open(CACHE_NAME).then(async (cache) => {
        const cached = await cache.match(req);
        if (cached) return cached;
        const res = await fetch(req);
        // Only cache successful opaque/basic responses
        if (res && res.status === 200 && (res.type === "basic" || res.type === "opaque")) {
          cache.put(req, res.clone());
        }
        return res;
      })
    );
    return;
  }

  // For everything else (HTML, REST, etc.), do network-first and do not cache
  event.respondWith(fetch(req));
});

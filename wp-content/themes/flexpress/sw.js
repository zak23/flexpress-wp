const CACHE_NAME = "flexpress-v2"; // bump to clear old caches
const urlsToCache = [
  "/",
  "/wp-content/themes/flexpress/assets/css/main.css",
  "/wp-content/themes/flexpress/assets/js/main.js",
];

self.addEventListener("install", function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", function (event) {
  const req = event.request;
  const url = new URL(req.url);

  // Never serve cached HTML pages to avoid login state staleness
  const isHTML =
    req.mode === "navigate" ||
    (req.headers.get("accept") || "").includes("text/html");

  if (isHTML) {
    event.respondWith(fetch(req));
    return;
  }

  // Cache-first for static assets only
  event.respondWith(
    caches.match(req).then(function (response) {
      return response || fetch(req);
    })
  );
});

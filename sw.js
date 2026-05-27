const CACHE_NAME = "ima-v1";

const urlsToCache = [
  "./",
  "./index.html",
  "./ima.html",
  "./offline.html",
  "./IMA.png"
];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

self.addEventListener("fetch", event => {

  event.respondWith(

    fetch(event.request)
      .then(response => {

        const responseClone = response.clone();

        caches.open(CACHE_NAME)
          .then(cache => {
            cache.put(event.request, responseClone);
          });

        return response;

      })
      .catch(() => {

        return caches.match(event.request)
          .then(response => {

            return response || caches.match("./offline.html");

          });

      })

  );

});

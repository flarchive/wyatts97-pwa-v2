// Same IndexedDB layout as forum bundle (idb `keyval-store` / `keyval`); no importScripts so path matches any extension id.

function openKeyvalDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('keyval-store', 1);
    req.onerror = () => reject(req.error);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains('keyval')) {
        db.createObjectStore('keyval');
      }
    };
    req.onsuccess = () => resolve(req.result);
  });
}

const dbPromise = openKeyvalDB();

const idbKeyval = {
  async get(key) {
    const db = await dbPromise;
    return new Promise((resolve, reject) => {
      const tx = db.transaction('keyval', 'readonly');
      const r = tx.objectStore('keyval').get(key);
      r.onsuccess = () => resolve(r.result);
      r.onerror = () => reject(r.error);
    });
  },
  async set(key, val) {
    const db = await dbPromise;
    return new Promise((resolve, reject) => {
      const tx = db.transaction('keyval', 'readwrite');
      const r = tx.objectStore('keyval').put(val, key);
      r.onsuccess = () => resolve(r.result);
      r.onerror = () => reject(r.error);
    });
  },
  async delete(key) {
    const db = await dbPromise;
    return new Promise((resolve, reject) => {
      const tx = db.transaction('keyval', 'readwrite');
      const r = tx.objectStore('keyval').delete(key);
      r.onsuccess = () => resolve();
      r.onerror = () => reject(r.error);
    });
  },
  async clear() {
    const db = await dbPromise;
    return new Promise((resolve, reject) => {
      const tx = db.transaction('keyval', 'readwrite');
      const r = tx.objectStore('keyval').clear();
      r.onsuccess = () => resolve();
      r.onerror = () => reject(r.error);
    });
  },
  async keys() {
    const db = await dbPromise;
    return new Promise((resolve, reject) => {
      const tx = db.transaction('keyval', 'readonly');
      const r = tx.objectStore('keyval').getAllKeys();
      r.onsuccess = () => resolve(r.result);
      r.onerror = () => reject(r.error);
    });
  },
};

const CACHE = 'pwa-page';

const forumPayload = {};

const offlineFallbackPage = 'offline';

self.addEventListener('install', function (event) {
  console.log('[PWA] Install event processing...');

  const cacheOffline = caches.open(CACHE).then(function (cache) {
    console.log('[PWA] Cached offline page during install.');
    return cache.add(offlineFallbackPage);
  });

  const receiveInfo = (async () => {
    try {
      const payload = await idbKeyval.get('flarum.forumPayload');
      if (payload && typeof payload === 'object') {
        Object.assign(forumPayload, payload);
      }
    } catch (e) {
      console.warn('[PWA] Could not read forum payload from IndexedDB', e);
    }
  })();

  event.waitUntil(Promise.all([cacheOffline, receiveInfo]));
});

self.addEventListener('fetch', function (event) {
  event.respondWith(
    caches
      .match(event.request)
      .then((res) => {
        if (event.request.method !== 'GET' || (forumPayload.debug && forumPayload.clockworkEnabled) || !res) {
          return fetch(event.request);
        }

        return res;
      })
      .catch((error) => {
        if (event.request.destination !== 'document' || event.request.mode !== 'navigate') {
          throw error;
        }

        return caches.open(CACHE).then(function (cache) {
          return cache.match(offlineFallbackPage);
        });
      })
  );
});

self.addEventListener('refreshOffline', function () {
  const offlinePageRequest = new Request(offlineFallbackPage);

  return fetch(offlineFallbackPage).then(function (response) {
    return caches.open(CACHE).then(function (cache) {
      console.log('[PWA] Offline page updated from refreshOffline event: ' + response.url);
      return cache.put(offlinePageRequest, response);
    });
  });
});

self.addEventListener('push', function (event) {
  function isJSON(str) {
    try {
      return JSON.parse(str) && !!str;
    } catch (e) {
      return false;
    }
  }

  if (isJSON(event.data.text())) {
    console.log(event.data.json());
    const options = {
      body: event.data.json().content,
      icon: event.data.json().icon,
      badge: event.data.json().badge,
      data: {
        link: event.data.json().link,
      },
    };

    const promiseChain = self.registration.showNotification(event.data.json().title, options);

    event.waitUntil(promiseChain);
  } else {
    console.log('This push event has no data.');
  }
});

self.addEventListener('notificationclick', function (event) {
  const clickedNotification = event.notification;
  clickedNotification.close();

  if (event.notification.data && event.notification.data.link) {
    const promiseChain = clients.openWindow(event.notification.data.link);
    event.waitUntil(promiseChain);
  }
});

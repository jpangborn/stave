// Stave service worker.
// Handles incoming Web Push notifications and click-to-focus behavior.
// A no-op fetch listener is included so iOS counts this as an active worker.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // Intentional no-op so iOS treats this as a "real" service worker.
});

self.addEventListener('push', (event) => {
    let data;
    try {
        data = event.data?.json() ?? {};
    } catch {
        data = { title: 'Stave', body: event.data?.text() ?? '' };
    }

    const title = data.title || 'Stave';
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/icon-192.png',
        tag: data.tag,
        data: { url: data.url || '/dashboard' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const defaultPath = '/dashboard';
    let targetUrl = defaultPath;
    try {
        const parsed = new URL(event.notification.data?.url ?? defaultPath, self.location.origin);
        if (parsed.origin === self.location.origin) {
            targetUrl = `${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
    } catch {
        targetUrl = defaultPath;
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
            return null;
        })
    );
});

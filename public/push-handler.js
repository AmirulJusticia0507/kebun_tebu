self.addEventListener('push', (event) => {
    const data = event.data?.json() || {};
    event.waitUntil(self.registration.showNotification(data.title || 'Kebun Tebu', {
        body: data.body || '',
        icon: '/icon.png',
        badge: '/icon.png',
        data: { url: data.url || '/reports' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data?.url || '/reports'));
});

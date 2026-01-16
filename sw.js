// Service Worker สำหรับ ResQTech Notification System
// Network-Only Strategy - ABSOLUTELY NO CACHING
const CACHE_NAME = 'resqtech-nocache-v4.0.0-final';

// ติดตั้ง: ไม่ทำอะไร (ไม่ต้อง Cache อะไรทั้งนั้น)
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    // Force activation immediately
    event.waitUntil(self.skipWaiting());
});

// เปิดใช้งาน: ลบ Cache ทุกอย่างที่เคยมี
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating & Cleaning...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    console.log('Service Worker: Deleting cache', cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event Removed: We want purely Network Only behavior.
// Adding an empty fetch listener creates unnecessary overhead (No-op fetch handler).


// Push Notifications
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'มีการแจ้งเตือนใหม่',
        icon: 'icons/icon.svg',
        badge: 'icons/icon.svg',
        vibrate: [100, 50, 100]
    };
    event.waitUntil(
        self.registration.showNotification('ResQTech System', options)
    );
});

// Notification Click
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow('./'));
});

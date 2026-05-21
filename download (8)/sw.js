// sw.js - Full Background Notification Support
const CACHE_NAME = 'social-app-v2';
const API_URL = '/api/notifications.php';

// Install event - register background sync
self.addEventListener('install', function(event) {
    console.log('[SW] Installing...');
    event.waitUntil(self.skipWaiting());
});

// Activate event - claim control and set up background sync
self.addEventListener('activate', function(event) {
    console.log('[SW] Activating...');
    event.waitUntil(clients.claim());
    
    // Register for background sync
    event.waitUntil(registerBackgroundSync());
    
    // Initial notification check
    event.waitUntil(checkForNewNotifications());
    
    // Set up periodic check in background
    setupBackgroundCheck();
});

// Register background sync
async function registerBackgroundSync() {
    try {
        const registration = await self.registration;
        if ('sync' in registration) {
            await registration.sync.register('sync-notifications');
            console.log('[SW] Background sync registered');
        }
    } catch (error) {
        console.error('[SW] Background sync registration failed:', error);
    }
}

// Handle background sync events
self.addEventListener('sync', function(event) {
    console.log('[SW] Background sync triggered:', event.tag);
    
    if (event.tag === 'sync-notifications') {
        event.waitUntil(checkForNewNotifications());
    }
});

// Set up periodic background check (every 15 minutes)
function setupBackgroundCheck() {
    // Check every 15 minutes even when browser is in background
    setInterval(() => {
        console.log('[SW] Background periodic check at:', new Date().toLocaleTimeString());
        checkForNewNotifications();
    }, 15 * 60 * 1000); // 15 minutes
}

// Listen for messages from pages
self.addEventListener('message', function(event) {
    console.log('[SW] Message received:', event.data);
    
    if (event.data && event.data.type === 'CHECK_NOTIFICATIONS') {
        event.waitUntil(checkForNewNotifications());
    } else if (event.data && event.data.type === 'FORCE_CHECK') {
        event.waitUntil(forceCheckNotifications());
    }
});

// Force check with no rate limiting
async function forceCheckNotifications() {
    console.log('[SW] Force checking notifications...');
    await checkForNewNotifications(true);
}

// Main notification check function
async function checkForNewNotifications(force = false) {
    console.log('[SW] Checking for new notifications...', force ? '(forced)' : '');
    
    try {
        // Get last check time to avoid excessive checks
        const lastCheckTime = await getLastNotificationCheck();
        const now = Date.now();
        
        // Don't check more than once every 30 seconds unless forced
        if (!force && (now - lastCheckTime) < 30000) {
            console.log('[SW] Skipping check - too soon');
            return;
        }
        
        const baseUrl = self.location.origin;
        const response = await fetch(`${baseUrl}${API_URL}`, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            cache: 'no-store'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[SW] API Response:', data);
        
        if (data.success) {
            // Store previous counts
            const prevFriendRequests = await getStoredCount('friend_requests');
            const prevMessages = await getStoredCount('unread_messages');
            
            // Check for friend requests
            if (data.counts.friend_requests > 0) {
                if (data.counts.friend_requests > prevFriendRequests) {
                    const newRequests = data.counts.friend_requests - prevFriendRequests;
                    const latestRequest = data.notifications.friend_requests[0];
                    
                    await showFriendRequestNotification(newRequests, latestRequest);
                    await storeCount('friend_requests', data.counts.friend_requests);
                }
            } else if (prevFriendRequests > 0) {
                // Reset count if no requests
                await storeCount('friend_requests', 0);
            }
            
            // Check for unread messages
            if (data.counts.unread_messages > 0) {
                if (data.counts.unread_messages > prevMessages) {
                    const newMessages = data.counts.unread_messages - prevMessages;
                    const latestMessage = data.notifications.unread_messages[0];
                    
                    await showMessageNotification(newMessages, latestMessage);
                    await storeCount('unread_messages', data.counts.unread_messages);
                }
            } else if (prevMessages > 0) {
                // Reset count if no messages
                await storeCount('unread_messages', 0);
            }
            
            // Update last check time
            await setLastNotificationCheck(now);
        }
    } catch (error) {
        console.error('[SW] Error checking notifications:', error);
    }
}

// Show friend request notification
async function showFriendRequestNotification(count, latestRequest) {
    const options = {
        body: count === 1 
            ? `${latestRequest.full_name} sent you a friend request`
            : `You have ${count} new friend request${count > 1 ? 's' : ''}`,
        icon: '/favicon.ico',
        badge: '/badge-icon.png',
        vibrate: [200, 100, 200],
        timestamp: Date.now(),
        data: {
            url: '/friends.php?tab=requests',
            type: 'friend_request',
            count: count
        },
        tag: `friend-request-${Date.now()}`,
        renotify: true,
        requireInteraction: true,
        silent: false,
        actions: [
            {
                action: 'view',
                title: 'View Requests'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };
    
    await self.registration.showNotification('👥 New Friend Request', options);
}

// Show message notification
async function showMessageNotification(count, latestMessage) {
    let body = '';
    if (count === 1) {
        body = `${latestMessage.sender_name}: ${latestMessage.message_preview}`;
    } else {
        body = `You have ${count} new message${count > 1 ? 's' : ''}`;
    }
    
    const options = {
        body: body,
        icon: '/favicon.ico',
        badge: '/badge-icon.png',
        vibrate: [200, 100, 200],
        timestamp: Date.now(),
        data: {
            url: `/messages.php?conversation_id=${latestMessage.conversation_id}`,
            type: 'message',
            sender_id: latestMessage.sender_id,
            count: count
        },
        tag: `message-${latestMessage.sender_id}-${Date.now()}`,
        renotify: true,
        requireInteraction: true,
        silent: false,
        actions: [
            {
                action: 'reply',
                title: 'Reply'
            },
            {
                action: 'view',
                title: 'View All'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };
    
    await self.registration.showNotification('💬 New Message', options);
}

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
    console.log('[SW] Notification clicked:', event.notification);
    event.notification.close();
    
    const action = event.action;
    const notificationData = event.notification.data;
    let urlToOpen = notificationData?.url || '/';
    
    if (action === 'reply' && notificationData?.type === 'message') {
        urlToOpen = `/messages.php?conversation_id=${notificationData.conversation_id}&reply=true`;
    } else if (action === 'view' && notificationData?.type === 'friend_request') {
        urlToOpen = '/friends.php?tab=requests';
    } else if (action === 'dismiss') {
        return;
    }
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            // Check for existing window/tab
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                const clientUrl = new URL(client.url);
                const targetUrl = new URL(urlToOpen, self.location.origin);
                
                if (clientUrl.pathname === targetUrl.pathname && 'focus' in client) {
                    return client.focus();
                }
            }
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// IndexedDB Storage Functions
function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('NotificationDB', 2);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            const oldVersion = event.oldVersion;
            
            if (!db.objectStoreNames.contains('counts')) {
                db.createObjectStore('counts');
            }
            if (!db.objectStoreNames.contains('metadata')) {
                db.createObjectStore('metadata');
            }
            if (!db.objectStoreNames.contains('notifications')) {
                db.createObjectStore('notifications', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

async function getStoredCount(key) {
    try {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(['counts'], 'readonly');
            const store = transaction.objectStore('counts');
            const request = store.get(key);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result || 0);
            transaction.oncomplete = () => db.close();
        });
    } catch (error) {
        console.error('[SW] Error getting stored count:', error);
        return 0;
    }
}

async function storeCount(key, value) {
    try {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(['counts'], 'readwrite');
            const store = transaction.objectStore('counts');
            const request = store.put(value, key);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve();
            transaction.oncomplete = () => db.close();
        });
    } catch (error) {
        console.error('[SW] Error storing count:', error);
    }
}

async function getLastNotificationCheck() {
    try {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(['metadata'], 'readonly');
            const store = transaction.objectStore('metadata');
            const request = store.get('lastCheck');
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result || 0);
            transaction.oncomplete = () => db.close();
        });
    } catch (error) {
        console.error('[SW] Error getting last check:', error);
        return 0;
    }
}

async function setLastNotificationCheck(timestamp) {
    try {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(['metadata'], 'readwrite');
            const store = transaction.objectStore('metadata');
            const request = store.put(timestamp, 'lastCheck');
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve();
            transaction.oncomplete = () => db.close();
        });
    } catch (error) {
        console.error('[SW] Error setting last check:', error);
    }
}

// Keep service worker alive
self.addEventListener('fetch', function(event) {
    // Don't cache API calls
    if (event.request.url.includes('/api/')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                
                const fetchRequest = event.request.clone();
                
                return fetch(fetchRequest).then(function(response) {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                });
            })
    );
});

// Keep service worker alive with periodic pings
setInterval(() => {
    console.log('[SW] Heartbeat - Service Worker alive');
}, 60000); // Log every minute

// Check for notifications every minute even in background
setInterval(() => {
    checkForNewNotifications();
}, 60000); // 1 minute
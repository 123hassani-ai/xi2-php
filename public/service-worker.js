/**
 * زیتو (Xi2) - Service Worker
 * PWA و مدیریت cache آفلاین
 */

const CACHE_NAME = 'xi2-v1.0.3';
const STATIC_CACHE = 'xi2-static-v1.3';
const DYNAMIC_CACHE = 'xi2-dynamic-v1.3';

// فایل‌های استاتیک برای کش - لیست کوتاه‌تر برای تست
const STATIC_FILES = [
    './',
    './index.html',
    './manifest.json'
];

// فایل‌هایی که نباید کش شوند
const EXCLUDED_URLS = [
    '../src/api/',
    '/admin/',
    'chrome-extension://'
];

// نصب Service Worker
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker در حال نصب...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            console.log('📦 کش کردن فایل‌های استاتیک...');
            return cache.addAll(STATIC_FILES);
        }).then(() => {
            console.log('✅ Service Worker نصب شد');
            return self.skipWaiting();
        }).catch((error) => {
            console.error('❌ خطا در نصب Service Worker:', error);
        })
    );
});

// فعال‌سازی Service Worker
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker فعال شد');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // حذف کش‌های قدیمی
                    if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                        console.log('🗑️ حذف کش قدیمی:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// رهگیری درخواست‌ها
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // رد کردن درخواست‌های خاص
    if (shouldExcludeFromCache(url)) {
        return;
    }
    
    // استراتژی کش برای انواع مختلف فایل
    if (request.method === 'GET') {
        if (isStaticAsset(url)) {
            // Cache First برای assets استاتیک
            event.respondWith(cacheFirstStrategy(request));
        } else if (isAPIRequest(url)) {
            // Network First برای API calls
            event.respondWith(networkFirstStrategy(request));
        } else {
            // Stale While Revalidate برای صفحات HTML
            event.respondWith(staleWhileRevalidateStrategy(request));
        }
    }
});

// بررسی اینکه آیا URL باید از کش خارج شود
function shouldExcludeFromCache(url) {
    return EXCLUDED_URLS.some(excludedUrl => url.pathname.includes(excludedUrl)) ||
           url.protocol === 'chrome-extension:';
}

// بررسی اینکه آیا درخواست برای asset استاتیک است
function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => url.pathname.includes(ext));
}

// بررسی اینکه آیا درخواست API است
function isAPIRequest(url) {
    return url.pathname.includes('/src/api/');
}

// استراتژی Cache First
async function cacheFirstStrategy(request) {
    try {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.error('Cache First خطا:', error);
        return getOfflineFallback(request);
    }
}

// استراتژی Network First
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.error('Network First خطا:', error);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return getAPIOfflineFallback();
    }
}

// استراتژی Stale While Revalidate
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    // ارسال درخواست شبکه در پس‌زمینه
    const networkResponse = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => null);
    
    // بازگشت کش یا شبکه (هر کدام که زودتر آماده شود)
    return cachedResponse || networkResponse || getOfflineFallback(request);
}

// صفحه آفلاین
function getOfflineFallback(request) {
    if (request.destination === 'document') {
        return caches.match('/offline.html') || new Response(`
            <!DOCTYPE html>
            <html lang="fa" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>آفلاین - زیتو</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                        background: linear-gradient(135deg, #6366F1 0%, #EC4899 100%);
                        color: white;
                        height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                    }
                    .offline-container {
                        max-width: 400px;
                        padding: 2rem;
                    }
                    .offline-icon {
                        font-size: 4rem;
                        margin-bottom: 1rem;
                    }
                    h1 { margin-bottom: 1rem; }
                    p { opacity: 0.9; line-height: 1.6; margin-bottom: 2rem; }
                    .retry-btn {
                        background: rgba(255,255,255,0.2);
                        color: white;
                        border: 1px solid rgba(255,255,255,0.3);
                        padding: 0.75rem 1.5rem;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        font-size: 1rem;
                    }
                    .retry-btn:hover {
                        background: rgba(255,255,255,0.3);
                    }
                </style>
            </head>
            <body>
                <div class="offline-container">
                    <div class="offline-icon">📡</div>
                    <h1>شما آفلاین هستید</h1>
                    <p>اتصال اینترنت شما قطع است. لطفاً اتصال خود را بررسی کنید و دوباره تلاش کنید.</p>
                    <button class="retry-btn" onclick="window.location.reload()">تلاش مجدد</button>
                </div>
            </body>
            </html>
        `, {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
        });
    }
    
    if (request.destination === 'image') {
        return caches.match('/src/assets/images/offline.png') || new Response(`
            <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" fill="#f3f4f6"/>
                <text x="100" y="100" text-anchor="middle" dominant-baseline="middle" 
                      font-family="Arial, sans-serif" font-size="16" fill="#6b7280">
                    تصویر در دسترس نیست
                </text>
            </svg>
        `, {
            headers: { 'Content-Type': 'image/svg+xml' }
        });
    }
    
    return new Response('محتوا در حالت آفلاین در دسترس نیست', {
        status: 408,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
    });
}

// پاسخ آفلاین برای API
function getAPIOfflineFallback() {
    return new Response(JSON.stringify({
        success: false,
        message: 'شما در حالت آفلاین هستید',
        offline: true
    }), {
        headers: { 'Content-Type': 'application/json' }
    });
}

// مدیریت پیام‌ها از main thread
self.addEventListener('message', (event) => {
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'CACHE_URLS':
            cacheUrls(payload.urls);
            break;
            
        case 'CLEAR_CACHE':
            clearCache(payload.cacheName);
            break;
    }
});

// کش کردن URL های جدید
async function cacheUrls(urls) {
    try {
        const cache = await caches.open(DYNAMIC_CACHE);
        await cache.addAll(urls);
        console.log('✅ URL های جدید کش شدند:', urls);
    } catch (error) {
        console.error('❌ خطا در کش کردن URL ها:', error);
    }
}

// پاک کردن کش
async function clearCache(cacheName = null) {
    try {
        if (cacheName) {
            await caches.delete(cacheName);
            console.log('🗑️ کش پاک شد:', cacheName);
        } else {
            const cacheNames = await caches.keys();
            await Promise.all(cacheNames.map(name => caches.delete(name)));
            console.log('🗑️ تمام کش‌ها پاک شدند');
        }
    } catch (error) {
        console.error('❌ خطا در پاک کردن کش:', error);
    }
}

// مدیریت sync در پس‌زمینه
self.addEventListener('sync', (event) => {
    console.log('🔄 Background Sync:', event.tag);
    
    if (event.tag === 'upload-images') {
        event.waitUntil(syncPendingUploads());
    }
});

// آپلود pending images
async function syncPendingUploads() {
    try {
        // دریافت آپلود های pending از IndexedDB
        const pendingUploads = await getPendingUploads();
        
        for (const upload of pendingUploads) {
            try {
                await uploadImage(upload);
                await removePendingUpload(upload.id);
                console.log('✅ آپلود موفق:', upload.filename);
            } catch (error) {
                console.error('❌ خطا در آپلود:', upload.filename, error);
            }
        }
    } catch (error) {
        console.error('❌ خطا در sync آپلود:', error);
    }
}

// دریافت آپلود های pending (mock function)
async function getPendingUploads() {
    // پیاده‌سازی IndexedDB برای ذخیره آپلود های pending
    return [];
}

// آپلود تصویر (mock function)
async function uploadImage(uploadData) {
    // پیاده‌سازی آپلود واقعی
    return fetch('/src/api/upload/upload.php', {
        method: 'POST',
        body: uploadData.formData
    });
}

// حذف آپلود pending (mock function)
async function removePendingUpload(id) {
    // پیاده‌سازی حذف از IndexedDB
}

// نوتیفیکیشن push
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const { title, body, icon, badge, actions } = data;
    
    const options = {
        body: body,
        icon: icon || '/src/assets/images/icon-192.png',
        badge: badge || '/src/assets/images/badge.png',
        vibrate: [200, 100, 200],
        data: data,
        actions: actions || [],
        requireInteraction: true,
        lang: 'fa',
        dir: 'rtl'
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// کلیک روی نوتیفیکیشن
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const { action, data } = event;
    
    let url = '/';
    
    if (action === 'open_upload') {
        url = '/#upload';
    } else if (action === 'open_gallery') {
        url = '/gallery.html';
    } else if (data && data.url) {
        url = data.url;
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // اگر پنجره باز است، focus کن
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // در غیر این صورت پنجره جدید باز کن
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Service Worker آماده است - حالت silent

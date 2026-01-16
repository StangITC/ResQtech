/**
 * ResQTech System - Theme Management
 * Neo-Brutalism Design System
 */

// NUCLEAR CACHE CLEAR - Run immediately
if ('caches' in window) {
    console.log('☢️ NUCLEAR CACHE CLEAR ACTIVATED');
    caches.keys().then(function (names) {
        for (let name of names) {
            console.log('Deleting cache:', name);
            caches.delete(name);
        }
    });
}

// Update all theme toggle buttons on the page
function updateThemeButtons(isDark) {
    const icon = isDark ? '☀️' : '🌙';

    // Update all possible theme toggle elements
    document.querySelectorAll('.theme-toggle, .nb-toggle[onclick*="toggleTheme"], .theme-toggle-btn, button[onclick*="toggleTheme"]').forEach(btn => {
        // Only update if it's a theme toggle button (check for moon/sun icons)
        const text = btn.textContent.trim();
        if (text === '🌙' || text === '☀️') {
            btn.textContent = icon;
        }
    });
}

// Toggle theme
function toggleTheme() {
    const body = document.body;

    if (body.getAttribute('data-theme') === 'dark') {
        body.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        updateThemeButtons(false);
    } else {
        body.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        updateThemeButtons(true);
    }
}

// Load saved theme
function loadTheme() {
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        updateThemeButtons(true);
    } else {
        updateThemeButtons(false);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', loadTheme);

// PWA Service Worker Registration - Force Fresh Network-Only Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            // Unregister old workers first
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (let registration of registrations) {
                console.log('Unregistering old SW:', registration);
                await registration.unregister();
            }

            // Register new worker with absolute path logic to be safe
            // Get the base URL of the app (e.g., http://localhost/ResQtech/)
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            const swUrl = basePath + 'sw.js?v=' + Date.now();
            
            console.log('Registering SW at:', swUrl);

            const registration = await navigator.serviceWorker.register(swUrl);
            console.log('SW registered successfully:', registration);
            
            // Force update
            await registration.update();
            
        } catch (error) {
            console.error('SW registration failed:', error);
        }
    });
}

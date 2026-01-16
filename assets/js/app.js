/**
 * ResQTech System - Main Application JavaScript
 */

// Clock functionality
function updateClock() {
    const now = new Date();
    const options = {
        timeZone: 'Asia/Bangkok',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };

    const thaiTime = now.toLocaleString('th-TH', options);
    const [date, time] = thaiTime.split(' ');

    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.innerHTML = `🕐 ${time} | 📅 ${date}`;
    }
}

// ESP32 Status Checker with SSE (Server-Sent Events)
let lastEventTime = null;
let eventSource = null;
let reconnectTimer = null;
let sseErrorCount = 0;
const MAX_SSE_ERRORS = 3;

// Polling function (Moved to top-level for fallback access)
function scheduleESPCheck(delay = 1000) {
    // Use global variable to prevent multiple timers
    if (window.pollingTimer) clearTimeout(window.pollingTimer);
    
    window.pollingTimer = setTimeout(() => {
        checkESP32Status().then(() => {
            // Success: Poll again in 1s (Faster update for War Room)
            scheduleESPCheck(1000);
        }).catch(() => {
            // Error: Poll again in 5s (Backoff)
            console.warn('Polling failed, retrying in 5s...');
            scheduleESPCheck(5000);
        });
    }, delay);
}

function initSSE() {
    if (eventSource) {
        eventSource.close();
    }

    // Connect to SSE stream
    eventSource = new EventSource('api/stream.php');

    eventSource.onopen = function() {
        console.log('SSE Connected');
        sseErrorCount = 0; // Reset error count on successful connection
    };

    eventSource.onmessage = function(event) {
        // Ping event (keep-alive)
    };

    eventSource.addEventListener('heartbeat', function(e) {
        try {
            const data = JSON.parse(e.data);
            updateStatusUI(data);
        } catch (err) {
            console.error('Heartbeat parse error:', err);
        }
    });

    eventSource.addEventListener('emergency', function(e) {
        try {
            const data = JSON.parse(e.data);
            updateEmergencyUI(data);
        } catch (err) {
            console.error('Emergency parse error:', err);
        }
    });

    eventSource.onerror = function() {
        sseErrorCount++;
        console.log(`SSE Error/Disconnected (${sseErrorCount}/${MAX_SSE_ERRORS})`);
        
        eventSource.close();
        updateStatusUI({ is_connected: false });
        
        if (sseErrorCount >= MAX_SSE_ERRORS) {
            console.warn('SSE unstable. Switching to Polling mode.');
            scheduleESPCheck(); // Switch to polling
            return;
        }

        // Reconnect logic
        if (reconnectTimer) clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(initSSE, 3000);
    };
}

function updateStatusUI(data) {
    const indicator = document.getElementById('esp32Indicator'); // .status-display-card or .status-badge
    const statusText = document.getElementById('esp32StatusText'); // .status-value-large or span
    const lastEventEl = document.getElementById('lastEvent'); // .status-meta
    const statusIcon = document.getElementById('statusIcon'); // .status-icon-large

    if (!indicator || !statusText) return;

    // Detect if we are in Dashboard (Badge) mode or Index (Card) mode
    const isBadgeMode = indicator.classList.contains('status-badge');

    if (data.is_connected) {
        // ONLINE
        if (isBadgeMode) {
            indicator.classList.remove('offline');
            indicator.classList.add('online');
            statusText.textContent = 'Online';
            
            const dot = indicator.querySelector('.status-dot');
            if (dot) {
                dot.classList.remove('offline');
                dot.classList.add('online');
            }
        } else {
            indicator.className = 'status-display-card online';
            statusText.textContent = 'ONLINE';
            statusText.style.color = 'var(--resq-lime)';
            
            if (statusIcon) {
                // Wifi Icon - Scaled to 48px
                statusIcon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                        <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                        <line x1="12" y1="20" x2="12.01" y2="20"></line>
                    </svg>`;
            }
        }
        
        if (lastEventEl) {
            lastEventEl.style.display = 'block';
            if (!lastEventEl.textContent.includes('EMERGENCY')) {
                lastEventEl.textContent = `Last heartbeat: ${data.seconds_ago}s ago`;
                lastEventEl.style.color = 'var(--text-secondary)';
            }
        }
        
        // Update Devices List (If function exists - for Dashboard)
        if (typeof updateDevicesUI === 'function' && data.devices_list) {
            updateDevicesUI(data.devices_list);
        }
    } else {
        // OFFLINE
        if (isBadgeMode) {
            indicator.classList.remove('online');
            indicator.classList.add('offline');
            statusText.textContent = 'Offline';

            const dot = indicator.querySelector('.status-dot');
            if (dot) {
                dot.classList.remove('online');
                dot.classList.add('offline');
            }
        } else {
            indicator.className = 'status-display-card offline';
            statusText.textContent = 'OFFLINE';
            statusText.style.color = 'var(--resq-red)';
            
            if (statusIcon) {
                // Wifi Off Icon - Scaled to 48px
                statusIcon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                        <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                        <line x1="12" y1="20" x2="12.01" y2="20"></line>
                    </svg>`;
            }
        }
        
        if (lastEventEl) {
            lastEventEl.textContent = 'No signal detected';
            lastEventEl.style.color = 'var(--resq-red)';
            lastEventEl.style.display = 'block';
        }
        
        // Update Devices List even if main status is disconnected
        if (typeof updateDevicesUI === 'function' && data.devices_list) {
            updateDevicesUI(data.devices_list);
        }
    }
}

function updateEmergencyUI(data) {
    const indicator = document.getElementById('esp32Indicator');
    const statusText = document.getElementById('esp32StatusText');
    const lastEventEl = document.getElementById('lastEvent');
    const statusIcon = document.getElementById('statusIcon');

    if (!indicator || !statusText) return;

    if (data.is_recent) {
        indicator.className = 'status-display-card offline';
        statusText.textContent = '🚨 EMERGENCY!';
        statusText.style.color = 'var(--resq-red)';
        
        if (statusIcon) {
             // Siren/Bell Icon - Scaled to 48px
             statusIcon.innerHTML = `
                 <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                     <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                     <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                     <line x1="2" y1="8" x2="22" y2="8"></line>
                 </svg>`;
        }

        if (lastEventEl) {
            lastEventEl.textContent = `Event at: ${data.last_event} (${data.seconds_ago}s ago)`;
            lastEventEl.style.color = 'var(--resq-red)';
            lastEventEl.style.fontWeight = 'bold';
        }

        if (lastEventTime !== data.last_event) {
            playEmergencyAlert();
            showEmergencyNotification();
            lastEventTime = data.last_event;
        }
    }
}

// Fallback polling for browsers without SSE support
function checkESP32Status() {
    return fetch('api/check-status.php?t=' + Date.now())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Logic similar to SSE handlers but manually called
            updateStatusUI(data);
            if (data.last_event) {
                updateEmergencyUI({
                    is_recent: data.is_recent,
                    last_event: data.last_event,
                    seconds_ago: data.seconds_ago
                });
            }
        })
        .catch(error => {
            console.error('Polling error:', error);
            updateStatusUI({ is_connected: false });
            throw error; // Propagate error for scheduleESPCheck to handle
        });
}

function playEmergencyAlert() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);

        setTimeout(() => {
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            osc2.frequency.value = 1000;
            osc2.type = 'sine';
            gain2.gain.setValueAtTime(0.3, audioContext.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            osc2.start();
            osc2.stop(audioContext.currentTime + 0.5);
        }, 600);
    } catch (e) {
        console.error('Audio error:', e);
    }
}

function showEmergencyNotification() {
    // Create Wrapper for Centering
    const wrapper = document.createElement('div');
    wrapper.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        pointer-events: none; /* Let clicks pass through if not on the box */
    `;

    // Create Notification Box
    const notification = document.createElement('div');
    notification.innerHTML = '🚨 EMERGENCY BUTTON PRESSED!';
    notification.style.cssText = `
        background: var(--resq-red, #ff0055);
        color: white;
        padding: 30px 50px;
        border: 4px solid #000;
        box-shadow: 12px 12px 0px #000;
        font-size: 2em;
        font-weight: 800;
        animation: shake 0.5s infinite;
        font-family: 'Space Grotesk', sans-serif;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-align: center;
        pointer-events: auto;
        min-width: 400px;
    `;

    // Inject Keyframes for Shake if not exists
    if (!document.getElementById('shake-keyframes')) {
        const style = document.createElement('style');
        style.id = 'shake-keyframes';
        style.innerHTML = `
            @keyframes shake { 
                0%, 100% { transform: rotate(0deg); } 
                25% { transform: rotate(-3deg); } 
                75% { transform: rotate(3deg); } 
            }
        `;
        document.head.appendChild(style);
    }

    wrapper.appendChild(notification);
    document.body.appendChild(wrapper);

    // Remove after 5 seconds
    setTimeout(() => wrapper.remove(), 5000);
}

// Notification form handler
function initNotificationForm() {
    const form = document.getElementById('notificationForm');
    const sendButton = document.getElementById('sendButton');
    const statusEl = document.getElementById('status');
    const messageEl = document.getElementById('notificationMessage');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const messageValue = (messageEl?.value || '').trim();
        if (!messageValue) {
            statusEl.textContent = '❌ กรุณากรอกข้อความที่ต้องการส่ง';
            statusEl.className = '';
            statusEl.classList.add('status-error');
            return;
        }
        if (messageValue.length > 1000) {
            statusEl.textContent = '❌ ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)';
            statusEl.className = '';
            statusEl.classList.add('status-error');
            return;
        }

        statusEl.textContent = 'กำลังส่ง...';
        statusEl.className = '';
        sendButton.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch('api/send-notification.php', {
                method: 'POST',
                body: formData
            });

            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                // If JSON parse fails, log the raw text response
                const text = await response.text().catch(() => 'No response body');
                console.error('JSON Parse Error:', jsonError);
                console.error('Raw Response:', text);
                throw new Error('Invalid server response');
            }

            if (result.status === 'success') {
                statusEl.textContent = '✅ ส่งการแจ้งเตือนสำเร็จ!';
                statusEl.classList.add('status-success');
                if (messageEl) messageEl.value = '';
            } else {
                statusEl.textContent = '❌ ' + (result.message || 'เกิดข้อผิดพลาด');
                statusEl.classList.add('status-error');
            }
        } catch (error) {
            statusEl.textContent = '❌ ไม่สามารถเชื่อมต่อกับ Server ได้';
            statusEl.classList.add('status-error');
            console.error('Fetch error:', error);
        } finally {
            setTimeout(() => {
                sendButton.disabled = false;
                setTimeout(() => {
                    statusEl.textContent = '';
                    statusEl.className = '';
                }, 3000);
            }, 2000);
        }
    });
}

// Force update/unregister old Service Worker
async function updateServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (const registration of registrations) {
                // Force update
                await registration.update();
            }
            // Clear all caches
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames.map(cacheName => {
                    // Aggressively delete everything that is not the current version
                    if (cacheName !== 'resqtech-nocache-v3.0.0') {
                        console.log('Clearing old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        } catch (e) {
            console.log('SW update error:', e);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Force update Service Worker
    updateServiceWorker();

    // Start clock
    updateClock();
    setInterval(updateClock, 1000);

    // Start ESP32 status checker
    // Note: SSE is disabled due to instability on Windows/Laragon environment (net::ERR_ABORTED)
    // We force Polling mode for better stability.
    const USE_SSE = false; 

    if (USE_SSE && window.EventSource) {
        initSSE();
    } else {
        // Fallback to polling immediately if SSE not supported or disabled
        scheduleESPCheck();
    }

    // Initialize notification form
    initNotificationForm();
});

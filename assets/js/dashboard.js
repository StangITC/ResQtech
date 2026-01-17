/**
 * Dashboard UI Logic - ResQTech System
 * Handles real-time updates for the dashboard interface
 */

function updateDevicesUI(devices) {
    const container = document.getElementById('devicesList');
    if (!container) return;

    const I18N = window.I18N || {};

    if (!devices || devices.length === 0) {
        container.innerHTML = `
            <div class="device-item offline" style="grid-column: 1/-1; text-align: center;">
                <div class="device-name">${I18N.no_devices_detected || 'No devices detected'}</div>
                <div class="device-time">${I18N.waiting_signals || 'Waiting for signals...'}</div>
            </div>`;
        return;
    }

    // Sort devices: Online first, then by last seen
    devices.sort((a, b) => {
        if (a.is_online === b.is_online) {
            return a.seconds_ago - b.seconds_ago;
        }
        return a.is_online ? -1 : 1;
    });

    let html = '';
    devices.forEach(dev => {
        const isOnline = dev.is_online;
        const statusClass = isOnline ? 'online' : 'offline';
        const statusText = isOnline ? (I18N.online || 'ONLINE') : (I18N.offline || 'OFFLINE');
        
        // Format location if missing
        const location = dev.location || (I18N.unknown_location || 'Unknown Location');
        const deviceId = dev.id || (I18N.unknown_id || 'Unknown ID');

        const unitS = I18N.unit_s || 's';
        const lastSeen = I18N.last_seen || 'Last seen';
        const ago = I18N.ago || 'ago';

        html += `
            <div class="device-item ${statusClass}">
                <div class="device-header">
                    <span class="device-name">${deviceId}</span>
                    <span class="device-status ${statusClass}">
                        <span class="status-dot ${statusClass}"></span>
                        ${statusText}
                    </span>
                </div>
                <div class="device-location">📍 ${location}</div>
                <div class="device-time">🕒 ${lastSeen}: ${dev.seconds_ago}${unitS} ${ago}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

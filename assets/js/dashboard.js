/**
 * Dashboard UI Logic - ResQTech System
 * Handles real-time updates for the dashboard interface
 */

function updateDevicesUI(devices) {
    const container = document.getElementById('devicesList');
    if (!container) return;

    if (!devices || devices.length === 0) {
        container.innerHTML = `
            <div class="device-item offline" style="grid-column: 1/-1; text-align: center;">
                <div class="device-name">No devices detected</div>
                <div class="device-time">Waiting for signals...</div>
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
        const statusText = isOnline ? 'ONLINE' : 'OFFLINE';
        
        // Format location if missing
        const location = dev.location || 'Unknown Location';
        const deviceId = dev.id || 'Unknown ID';

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
                <div class="device-time">🕒 Last seen: ${dev.seconds_ago}s ago</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

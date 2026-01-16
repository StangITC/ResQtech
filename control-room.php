<?php
/**
 * Control Room Display - ResQTech System
 * Neo-Brutalism Design - Optimized for Large Display (War Room)
 */

require_once __DIR__ . '/includes/init.php';
requireLogin();

function getDisplayStats(): array
{
    $emergencyLogs = readLogFile(EMERGENCY_LOG_FILE, 1000);
    $now = time();
    $today = date('Y-m-d');

    $stats = [
        'total_events' => count($emergencyLogs),
        'today_events' => 0,
        'last_event_ago' => null,
        'is_online' => false
    ];

    foreach ($emergencyLogs as $log) {
        $timestamp = strtotime($log['timestamp']);
        if (date('Y-m-d', $timestamp) === $today) {
            $stats['today_events']++;
        }
        if (!$stats['last_event_ago']) {
            $stats['last_event_ago'] = $now - $timestamp;
        }
    }

    if (file_exists(HEARTBEAT_LOG_FILE)) {
        $heartbeats = file(HEARTBEAT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($heartbeats)) {
            $lastHb = end($heartbeats);
            if (preg_match('/\[(.*?)\]/', $lastHb, $matches)) {
                $stats['is_online'] = ($now - strtotime($matches[1])) <= 30;
            }
        }
    }

    return $stats;
}

$stats = getDisplayStats();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Room - ResQTech War Room</title>
    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        /* ============================
           ULTIMATE WAR ROOM DISPLAY
           ============================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-color: #0f0f12;
            --card-bg: #1a1a20;
            --text-main: #ffffff;
            --text-muted: #888899;

            --neon-green: #00ff9d;
            --neon-red: #ff0055;
            --neon-yellow: #ffcc00;
            --neon-blue: #00ccff;

            --grid-gap: 20px;
        }

        body {
            font-family: 'Space Grotesk', 'Noto Sans Thai', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .top-bar {
            height: 80px;
            background: var(--card-bg);
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            flex-shrink: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: var(--neon-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.5rem;
            color: white;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 20px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .system-status.offline {
            background: rgba(255, 0, 85, 0.1);
            border-color: var(--neon-red);
            color: var(--neon-red);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background: currentColor;
            border-radius: 50%;
            box-shadow: 0 0 10px currentColor;
            animation: pulse 2s infinite;
        }

        .clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        /* Main Grid Layout */
        .war-room-grid {
            flex: 1;
            padding: var(--grid-gap);
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: var(--grid-gap);
            overflow: hidden;
        }

        /* Device Wall (Left) */
        .device-wall {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            grid-auto-rows: 180px;
            gap: var(--grid-gap);
            overflow-y: auto;
            padding-right: 10px;
        }

        .device-unit {
            background: var(--card-bg);
            border: 1px solid #333;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.2s;
        }

        .device-unit:hover {
            transform: translateY(-5px);
            border-color: #555;
            background: #222;
        }

        .device-unit.online {
            border-left: 5px solid var(--neon-green);
        }

        .device-unit.offline {
            border-left: 5px solid var(--neon-red);
            opacity: 0.7;
        }

        .unit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .unit-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .unit-status {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 8px;
            background: #000;
            border-radius: 4px;
        }

        .online .unit-status {
            color: var(--neon-green);
        }

        .offline .unit-status {
            color: var(--neon-red);
        }

        .unit-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unit-location {
            font-size: 0.9rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .unit-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: #555;
            text-align: right;
            margin-top: auto;
        }

        /* Sidebar (Right) */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: var(--grid-gap);
        }

        .stat-box {
            background: var(--card-bg);
            padding: 20px;
            border-bottom: 4px solid #333;
            text-align: center;
        }

        .stat-box.red {
            border-color: var(--neon-red);
        }

        .stat-box.blue {
            border-color: var(--neon-blue);
        }

        .stat-num {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
        }

        .event-log-panel {
            flex: 1;
            background: var(--card-bg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #333;
        }

        .log-header {
            padding: 15px;
            background: #222;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
        }

        .log-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .log-item {
            padding: 15px;
            border-bottom: 1px solid #2a2a2a;
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 0.9rem;
        }

        .log-time {
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-muted);
            font-size: 0.8rem;
            min-width: 60px;
        }

        .log-msg {
            color: #ddd;
        }

        /* EMERGENCY OVERLAY */
        .emergency-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 0, 0, 0.95);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .emergency-overlay.active {
            opacity: 1;
            pointer-events: auto;
            animation: flashBg 1s infinite;
        }

        @keyframes flashBg {

            0%,
            100% {
                background: rgba(255, 0, 0, 0.95);
            }

            50% {
                background: rgba(180, 0, 0, 0.95);
            }
        }

        .alert-icon {
            font-size: 10rem;
            margin-bottom: 20px;
            animation: shake 0.5s infinite;
        }

        .alert-title {
            font-size: 5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 10px;
            margin-bottom: 20px;
        }

        .alert-location {
            font-size: 3rem;
            background: black;
            padding: 10px 40px;
            border: 4px solid white;
            margin-bottom: 20px;
        }

        .alert-time {
            font-size: 1.5rem;
            opacity: 0.8;
            font-family: 'JetBrains Mono', monospace;
        }

        .dismiss-btn {
            margin-top: 50px;
            padding: 15px 40px;
            background: white;
            color: red;
            font-size: 1.5rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
        }

        .fullscreen-btn {
            background: transparent;
            border: 1px solid #333;
            color: #666;
            padding: 5px 10px;
            cursor: pointer;
            margin-left: 10px;
        }

        /* Navigation Pills */
        .nav-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border: 1px solid #444;
            border-radius: 4px;
            color: #ccc;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .nav-pill:hover {
            background: rgba(0, 255, 157, 0.2);
            border-color: var(--neon-green);
            color: var(--neon-green);
        }
        .nav-pill.danger:hover {
            background: rgba(255, 0, 85, 0.2);
            border-color: var(--neon-red);
            color: var(--neon-red);
        }

        @keyframes pulse {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(0.8);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(-5deg);
            }

            75% {
                transform: rotate(5deg);
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #111;
        }

        ::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Emergency Overlay -->
    <div id="emergencyOverlay" class="emergency-overlay">
        <div class="alert-icon">🚨</div>
        <div class="alert-title">EMERGENCY ALERT</div>
        <div class="alert-location" id="alertLocation">UNKNOWN LOCATION</div>
        <div class="alert-time" id="alertTime">--:--:--</div>
        <button class="dismiss-btn" onclick="dismissEmergency()">ACKNOWLEDGE</button>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="brand">
            <div class="brand-logo">R</div>
            <div class="brand-name">RESQTECH <span style="color:var(--neon-red)">WAR ROOM</span></div>
        </div>
        <div style="display:flex; align-items:center; gap: 10px;">
            <!-- Navigation Pills -->
            <nav class="war-room-nav" style="display: flex; gap: 8px;">
                <a href="index.php" class="nav-pill" title="Home">🏠</a>
                <a href="dashboard.php" class="nav-pill" title="Dashboard">📊</a>
                <a href="perf-dashboard.php" class="nav-pill" title="Latency">⏱️</a>
                <a href="status-dashboard.php" class="nav-pill" title="Status">📡</a>
                <a href="history-dashboard.php" class="nav-pill" title="History">🧾</a>
                <a href="diagnostics-dashboard.php" class="nav-pill" title="Diagnostics">🧪</a>
                <a href="live-dashboard.php" class="nav-pill" title="Live">🟢</a>
                <a href="logout.php" class="nav-pill danger" title="Logout">🚪</a>
            </nav>
            <div class="system-status" id="systemStatus">
                <div class="status-dot"></div>
                <span id="systemStatusText">SYSTEM ONLINE</span>
            </div>
            <button class="fullscreen-btn" onclick="toggleFullscreen()">⛶</button>
        </div>
        <div class="clock" id="clock">00:00:00</div>
    </div>

    <!-- Main Grid -->
    <div class="war-room-grid">
        <!-- Device Wall -->
        <div class="device-wall" id="deviceWall">
            <div style="color: #666; padding: 20px;">Initializing Device Wall...</div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="stat-box red">
                <div class="stat-label">Total Events</div>
                <div class="stat-num" id="totalCount"><?= $stats['total_events'] ?></div>
            </div>
            <div class="stat-box blue">
                <div class="stat-label">Online Devices</div>
                <div class="stat-num" id="onlineDeviceCount">0</div>
            </div>

            <div class="event-log-panel">
                <div class="log-header">
                    <span>Recent Activity</span>
                    <span style="color:var(--neon-green)">LIVE</span>
                </div>
                <div class="log-list" id="logList">
                    <div style="padding:15px; color:#666;">Loading logs...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let audioCtx = null;
        let isEmergencyActive = false;

        // --- Utilities ---
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('th-TH', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        }

        function formatTimeAgo(seconds) {
            if (!seconds) return '--';
            if (seconds < 60) return seconds + 's';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm';
            return Math.floor(seconds / 3600) + 'h';
        }

        function playSound(type) {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                if (type === 'emergency') {
                    // Siren effect
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                    osc.frequency.linearRampToValueAtTime(440, audioCtx.currentTime + 0.5);
                    gain.gain.setValueAtTime(0.5, audioCtx.currentTime);
                    gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 0.5);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.5);
                } else {
                    // Ping effect
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(800, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.1);
                }
            } catch (e) { console.error(e); }
        }

        // --- Core Functions ---
        function toggleFullscreen() {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen();
            else document.exitFullscreen();
        }

        function dismissEmergency() {
            document.getElementById('emergencyOverlay').classList.remove('active');
            isEmergencyActive = false;
        }

        function triggerEmergency(data) {
            if (isEmergencyActive) return; // Already active

            isEmergencyActive = true;
            const overlay = document.getElementById('emergencyOverlay');

            // Extract device info safely
            const deviceId = data.emergency_device?.id || 'UNKNOWN';
            const location = data.emergency_device?.location || 'UNKNOWN LOCATION';

            document.getElementById('alertLocation').textContent = `${deviceId} @ ${location}`;
            document.getElementById('alertTime').textContent = new Date().toLocaleTimeString('th-TH');

            overlay.classList.add('active');
            playSound('emergency');

            // Loop sound every 2s while active
            const interval = setInterval(() => {
                if (!isEmergencyActive) clearInterval(interval);
                else playSound('emergency');
            }, 2000);
        }

        function renderDeviceWall(devices) {
            const container = document.getElementById('deviceWall');
            if (!devices || devices.length === 0) {
                container.innerHTML = '<div style="color:#666; padding:20px;">No devices found</div>';
                document.getElementById('onlineDeviceCount').textContent = '0';
                return;
            }

            // Sort: Online first
            devices.sort((a, b) => b.is_online - a.is_online);

            let onlineCount = 0;
            const html = devices.map(dev => {
                if (dev.is_online) onlineCount++;
                const statusClass = dev.is_online ? 'online' : 'offline';
                const statusText = dev.is_online ? 'ONLINE' : 'OFFLINE';
                const icon = dev.is_online ? '📡' : '❌';

                return `
                    <div class="device-unit ${statusClass}">
                        <div class="unit-header">
                            <div class="unit-icon">${icon}</div>
                            <div class="unit-status">${statusText}</div>
                        </div>
                        <div>
                            <div class="unit-name">${dev.id}</div>
                            <div class="unit-location">📍 ${dev.location || 'Unknown'}</div>
                        </div>
                        <div class="unit-time">
                            Seen: ${formatTimeAgo(dev.seconds_ago)} ago
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
            document.getElementById('onlineDeviceCount').textContent = onlineCount;
        }

        function renderLogs(logs) {
            const list = document.getElementById('logList');
            if (!logs || logs.length === 0) {
                list.innerHTML = '<div style="padding:15px; color:#666;">No recent activity</div>';
                return;
            }

            list.innerHTML = logs.slice(0, 10).map(log => `
                <div class="log-item">
                    <div class="log-time">${log.timestamp.split(' ')[1]}</div>
                    <div class="log-msg">${log.message}</div>
                </div>
            `).join('');
        }

        // --- Data Fetching ---
        function fetchData() {
            // Check Status (Devices & Alert)
            fetch('api/check-status.php?t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    // Update System Status Header
                    const sysStatus = document.getElementById('systemStatus');
                    const sysText = document.getElementById('systemStatusText');

                    if (data.is_connected) {
                        sysStatus.classList.remove('offline');
                        sysText.textContent = 'SYSTEM ONLINE';
                    } else {
                        sysStatus.classList.add('offline');
                        sysText.textContent = 'SYSTEM OFFLINE';
                    }

                    // Check for Emergency
                    if (data.is_recent) {
                        triggerEmergency(data);
                    }

                    // Render Devices
                    if (data.devices_list) {
                        renderDeviceWall(data.devices_list);
                    }
                })
                .catch(e => console.error("Status fetch error", e));

            // Check Dashboard Stats & Logs
            fetch('api/dashboard.php?t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    document.getElementById('totalCount').textContent = data.total_events;
                    if (data.recent_events) {
                        renderLogs(data.recent_events);
                    }
                })
                .catch(e => console.error("Dashboard fetch error", e));
        }

        // --- Initialization ---
        setInterval(updateClock, 1000);
        updateClock();

        fetchData();
        setInterval(fetchData, 1000); // Poll every 1 second for faster response

        // Enable Audio Context on first interaction
        document.body.addEventListener('click', () => {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }, { once: true });
    </script>
</body>

</html>
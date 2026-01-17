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
<html lang="<?php echo getCurrentLang(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('control_room_title'); ?> - <?php echo t('control_room_subtitle'); ?></title>
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
            width: 42px;
            height: 42px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .unit-icon svg {
            width: 36px;
            height: 36px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
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
        
        .unit-location svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            opacity: 0.9;
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
            width: 160px;
            height: 160px;
            margin-bottom: 20px;
            animation: shake 0.5s infinite;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert-icon svg {
            width: 100%;
            height: 100%;
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
            color: #fff;
            width: 36px;
            height: 36px;
            cursor: pointer;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
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
            color: #fff;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-pill svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        .nav-pill[aria-current="page"] {
            background: rgba(0, 204, 255, 0.15);
            border-color: var(--neon-blue);
            color: var(--neon-blue);
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
        <div class="alert-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
                <path d="M10.3 3.7 2.9 17.2a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"></path>
            </svg>
        </div>
        <div class="alert-title"><?php echo t('control_room_emergency_alert'); ?></div>
        <div class="alert-location" id="alertLocation"><?php echo t('control_room_unknown_location'); ?></div>
        <div class="alert-time" id="alertTime">--:--:--</div>
        <button class="dismiss-btn" onclick="dismissEmergency()"><?php echo t('control_room_acknowledge'); ?></button>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="brand">
            <div class="brand-logo">R</div>
            <div class="brand-name">RESQTECH <span style="color:var(--neon-red)"><?php echo t('control_room_war_room'); ?></span></div>
        </div>
        <div style="display:flex; align-items:center; gap: 10px;">
            <!-- Navigation Pills -->
            <nav class="war-room-nav" style="display: flex; gap: 8px;">
                <a href="index.php" class="nav-pill" title="<?php echo t('nav_home'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10.5V20h14v-9.5"></path></svg>
                </a>
                <a href="dashboard.php" class="nav-pill" title="<?php echo t('nav_dashboard'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4z"></path><path d="M13 4h7v7h-7z"></path><path d="M4 13h7v7H4z"></path><path d="M13 13h7v7h-7z"></path></svg>
                </a>
                <a href="perf-dashboard.php" class="nav-pill" title="<?php echo t('nav_latency'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 2"></path><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18Z"></path></svg>
                </a>
                <a href="status-dashboard.php" class="nav-pill" title="<?php echo t('nav_status'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20v-6"></path><path d="M8 20v-3"></path><path d="M16 20v-9"></path><path d="M4 20v-1"></path><path d="M20 20v-12"></path></svg>
                </a>
                <a href="history-dashboard.php" class="nav-pill" title="<?php echo t('nav_history'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l3 3v15H8z"></path><path d="M16 3v3h3"></path><path d="M10 11h8"></path><path d="M10 15h8"></path></svg>
                </a>
                <a href="diagnostics-dashboard.php" class="nav-pill" title="<?php echo t('nav_diagnostics'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 2v6l-5 9a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 21 17l-5-9V2"></path><path d="M8.5 14h7"></path></svg>
                </a>
                <a href="live-dashboard.php" class="nav-pill" title="<?php echo t('nav_live'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12h.01"></path><path d="M7.5 16.5a6 6 0 0 1 0-9"></path><path d="M16.5 7.5a6 6 0 0 1 0 9"></path><path d="M5.2 18.8a9 9 0 0 1 0-13.6"></path><path d="M18.8 5.2a9 9 0 0 1 0 13.6"></path></svg>
                </a>
                <a href="logout.php" class="nav-pill danger" title="<?php echo t('nav_logout'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l-1 0a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1"></path><path d="M15 7l5 5-5 5"></path><path d="M20 12H10"></path></svg>
                </a>
            </nav>
            <div class="system-status" id="systemStatus">
                <div class="status-dot"></div>
                <span id="systemStatusText"><?php echo t('control_room_system_online'); ?></span>
            </div>
            <button class="fullscreen-btn" onclick="toggleFullscreen()" title="<?php echo t('control_room_fullscreen'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 3H3v5"></path>
                    <path d="M16 3h5v5"></path>
                    <path d="M8 21H3v-5"></path>
                    <path d="M16 21h5v-5"></path>
                </svg>
            </button>
        </div>
        <div class="clock" id="clock">00:00:00</div>
    </div>

    <!-- Main Grid -->
    <div class="war-room-grid">
        <!-- Device Wall -->
        <div class="device-wall" id="deviceWall">
            <div style="color: #666; padding: 20px;"><?php echo t('control_room_initializing'); ?></div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="stat-box red">
                <div class="stat-label"><?php echo t('control_room_total_events'); ?></div>
                <div class="stat-num" id="totalCount"><?= $stats['total_events'] ?></div>
            </div>
            <div class="stat-box blue">
                <div class="stat-label"><?php echo t('control_room_online_devices'); ?></div>
                <div class="stat-num" id="onlineDeviceCount">0</div>
            </div>

            <div class="event-log-panel">
                <div class="log-header">
                    <span><?php echo t('control_room_recent_activity'); ?></span>
                    <span style="color:var(--neon-green)"><?php echo t('common_live'); ?></span>
                </div>
                <div class="log-list" id="logList">
                    <div style="padding:15px; color:#666;"><?php echo t('control_room_loading_logs'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const APP_LANG = <?php echo json_encode(getCurrentLang(), JSON_UNESCAPED_UNICODE); ?>;
        const I18N = <?php echo json_encode([
            'system_online' => t('control_room_system_online'),
            'system_offline' => t('control_room_system_offline'),
            'unknown_location' => t('control_room_unknown_location'),
            'unknown_device' => t('control_room_unknown_device'),
            'no_devices' => t('control_room_no_devices'),
            'no_recent_activity' => t('control_room_no_recent_activity'),
            'online' => t('status_online'),
            'offline' => t('status_offline'),
            'seen' => t('control_room_seen'),
            'ago' => t('control_room_ago'),
            'unit_s' => t('control_room_unit_s'),
            'unit_m' => t('control_room_unit_m'),
            'unit_h' => t('control_room_unit_h')
        ], JSON_UNESCAPED_UNICODE); ?>;

        let audioCtx = null;
        let isEmergencyActive = false;

        // --- Utilities ---
        function updateClock() {
            const now = new Date();
            const locale = APP_LANG === 'th' ? 'th-TH' : 'en-US';
            document.getElementById('clock').textContent = now.toLocaleTimeString(locale, {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        }

        function formatTimeAgo(seconds) {
            if (!seconds) return '--';
            if (seconds < 60) return seconds + I18N.unit_s;
            if (seconds < 3600) return Math.floor(seconds / 60) + I18N.unit_m;
            return Math.floor(seconds / 3600) + I18N.unit_h;
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
            const deviceId = data.emergency_device?.id || I18N.unknown_device;
            const location = data.emergency_device?.location || I18N.unknown_location;

            document.getElementById('alertLocation').textContent = `${deviceId} @ ${location}`;
            document.getElementById('alertTime').textContent = new Date().toLocaleTimeString(APP_LANG === 'th' ? 'th-TH' : 'en-US');

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
                container.innerHTML = '<div style="color:#666; padding:20px;">' + I18N.no_devices + '</div>';
                document.getElementById('onlineDeviceCount').textContent = '0';
                return;
            }

            // Sort: Online first
            devices.sort((a, b) => b.is_online - a.is_online);

            let onlineCount = 0;
            const html = devices.map(dev => {
                if (dev.is_online) onlineCount++;
                const statusClass = dev.is_online ? 'online' : 'offline';
                const statusText = dev.is_online ? I18N.online : I18N.offline;
                const icon = dev.is_online
                    ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h.01"></path><path d="M8.5 16.5a5 5 0 0 1 0-7"></path><path d="M15.5 9.5a5 5 0 0 1 0 7"></path><path d="M6.2 18.8a8 8 0 0 1 0-11.6"></path><path d="M17.8 7.2a8 8 0 0 1 0 11.6"></path></svg>'
                    : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="M6 6l12 12"></path></svg>';
                const pin = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11Z"></path><path d="M12 10.5h.01"></path></svg>';

                return `
                    <div class="device-unit ${statusClass}">
                        <div class="unit-header">
                            <div class="unit-icon">${icon}</div>
                            <div class="unit-status">${statusText}</div>
                        </div>
                        <div>
                            <div class="unit-name">${dev.id}</div>
                            <div class="unit-location">${pin} ${dev.location || I18N.unknown_location}</div>
                        </div>
                        <div class="unit-time">
                            ${I18N.seen}: ${formatTimeAgo(dev.seconds_ago)} ${I18N.ago}
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
                list.innerHTML = '<div style="padding:15px; color:#666;">' + I18N.no_recent_activity + '</div>';
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
                        sysText.textContent = I18N.system_online;
                    } else {
                        sysStatus.classList.add('offline');
                        sysText.textContent = I18N.system_offline;
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

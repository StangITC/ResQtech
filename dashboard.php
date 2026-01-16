<?php
/**
 * Dashboard Page - ResQTech System
 * Neo-Brutalism Design
 */

require_once __DIR__ . '/includes/init.php';

// ต้องเข้าสู่ระบบก่อน
requireLogin();

/**
 * Get emergency logs
 */
function getEmergencyLogs(int $limit = 100): array {
    return readLogFile(EMERGENCY_LOG_FILE, $limit);
}

/**
 * Get heartbeat logs
 */
function getHeartbeatLogs(int $limit = 50): array {
    if (!file_exists(HEARTBEAT_LOG_FILE)) {
        return [];
    }
    
    $logs = file(HEARTBEAT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_reverse(array_slice($logs, -$limit));
}

/**
 * Calculate statistics
 */
function getStatistics(): array {
    $emergencyLogs = getEmergencyLogs(1000);
    $heartbeatLogs = getHeartbeatLogs(1000);
    
    $now = time();
    $today = date('Y-m-d');
    
    $stats = [
        'total_events' => count($emergencyLogs),
        'today_events' => 0,
        'last_24h_events' => 0,
        'last_7d_events' => 0,
        'total_heartbeats' => count($heartbeatLogs),
        'uptime_percentage' => 0,
        'last_event' => null,
        'last_heartbeat' => null,
        'is_online' => false
    ];
    
    foreach ($emergencyLogs as $log) {
        $timestamp = strtotime($log['timestamp']);
        $logDate = date('Y-m-d', $timestamp);
        
        if ($logDate === $today) {
            $stats['today_events']++;
        }
        
        if (($now - $timestamp) <= 86400) {
            $stats['last_24h_events']++;
        }
        
        if (($now - $timestamp) <= 604800) {
            $stats['last_7d_events']++;
        }
        
        if (!$stats['last_event']) {
            $stats['last_event'] = $log['timestamp'];
        }
    }
    
    if (!empty($heartbeatLogs)) {
        $lastHeartbeat = reset($heartbeatLogs);
        if (preg_match('/\[(.*?)\]/', $lastHeartbeat, $matches)) {
            $stats['last_heartbeat'] = $matches[1];
            $hbTime = strtotime($matches[1]);
            $stats['is_online'] = ($now - $hbTime) <= 30;
            
            $windowSeconds = 3600;
            $minutesSet = [];
            foreach ($heartbeatLogs as $line) {
                if (preg_match('/\[(.*?)\]/', $line, $m)) {
                    $t = strtotime($m[1]);
                    if (($now - $t) <= $windowSeconds) {
                        $minuteKey = date('YmdHi', $t);
                        $minutesSet[$minuteKey] = true;
                    }
                }
            }
            $presentMinutes = count($minutesSet);
            $stats['uptime_percentage'] = round(($presentMinutes / 60) * 100, 2);
        }
    }
    
    return $stats;
}

$stats = getStatistics();
$recentLogs = getEmergencyLogs(10);

// คำนวณข้อมูลรายวันสำหรับกราฟ
$dailyStats = [];
$allLogsForStats = getEmergencyLogs(1000); // อ่านไฟล์ครั้งเดียวนอกลูป

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = 0;
    foreach ($allLogsForStats as $log) {
        $logDate = date('Y-m-d', strtotime($log['timestamp']));
        if ($logDate === $date) {
            $count++;
        }
    }
    $dailyStats[$date] = $count;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="refresh" content="30">
    <meta name="theme-color" content="#0066ff">
    <title>Dashboard - ResQTech System</title>
    
    <link rel="manifest" href="manifest.json?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <style>
        /* Dashboard Specific Styles */
        body {
            display: block;
            min-height: 100vh;
            padding: 0;
        }
        
        /* Marquee Banner */
        .marquee-banner {
            background: var(--nb-black);
            color: var(--resq-yellow);
            padding: 10px 0;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            overflow: hidden;
            border-bottom: 3px solid var(--resq-yellow);
        }
        
        .marquee-content {
            display: flex;
            white-space: nowrap;
            animation: marquee 25s linear infinite;
        }
        
        .marquee-item {
            margin-right: 60px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        /* Header */
        .dashboard-header {
            background: var(--bg-card);
            border-bottom: var(--nb-border-thick);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .logo-box {
            width: 50px;
            height: 50px;
            background: var(--resq-blue);
            border: 3px solid var(--nb-black);
            box-shadow: 4px 4px 0px var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 1.5rem;
            color: white;
            transform: rotate(-5deg);
        }
        
        .logo-text h1 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .logo-text p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .header-nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 10px 18px;
            font-size: 0.8125rem;
        }
        
        /* Main Content */
        .dashboard-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            box-shadow: var(--nb-shadow-lg);
            padding: 24px;
            position: relative;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translate(-4px, -4px);
            box-shadow: var(--nb-shadow-xl);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border: 2px solid var(--nb-black);
        }
        
        .stat-card.danger::before { background: var(--resq-red); }
        .stat-card.warning::before { background: var(--resq-yellow); }
        .stat-card.info::before { background: var(--resq-blue); }
        .stat-card.success::before { background: var(--resq-lime); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border: 3px solid var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 3px 3px 0px var(--nb-black);
        }
        
        .stat-icon.danger { background: var(--resq-red); }
        .stat-icon.warning { background: var(--resq-yellow); }
        .stat-icon.info { background: var(--resq-blue); color: white; }
        .stat-icon.success { background: var(--resq-lime); }
        
        .stat-value {
            font-family: var(--font-mono);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-family: var(--font-display);
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
        }
        
        .stat-change {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 2px solid var(--nb-black);
            margin-top: 12px;
        }
        
        .stat-change.up {
            background: var(--resq-lime);
            color: var(--nb-black);
        }
        
        .stat-change.down {
            background: var(--resq-red);
            color: white;
        }
        
        .stat-change.info-badge {
            background: var(--resq-blue);
            color: white;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            font-size: 0.875rem;
            font-weight: 800;
            font-family: var(--font-display);
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .status-badge.online {
            background: var(--resq-lime);
            color: var(--nb-black);
        }
        
        .status-badge.offline {
            background: var(--resq-red);
            color: white;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border: 2px solid var(--nb-black);
        }
        
        .status-dot.online {
            background: var(--nb-black);
            animation: pulse 2s infinite;
        }
        
        .status-dot.offline {
            background: white;
            animation: blink 0.5s infinite;
        }
        
        /* Progress Bar */
        .progress-bar {
            height: 12px;
            background: var(--bg-secondary);
            border: 2px solid var(--nb-black);
            margin-top: 16px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--resq-lime);
            transition: width 0.3s ease;
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .chart-card {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            box-shadow: var(--nb-shadow-lg);
            padding: 28px;
            position: relative;
        }
        
        .chart-card::before {
            content: '📊 CHART';
            position: absolute;
            top: -14px;
            left: 20px;
            background: var(--resq-purple);
            color: white;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.625rem;
            padding: 4px 12px;
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .chart-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 4px;
            margin-top: 8px;
        }
        
        .chart-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Events Card */
        .events-card {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            box-shadow: var(--nb-shadow-lg);
            padding: 28px;
            position: relative;
        }
        
        .events-card::before {
            content: '🕐 LIVE';
            position: absolute;
            top: -14px;
            left: 20px;
            background: var(--resq-red);
            color: white;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.625rem;
            padding: 4px 12px;
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: blink 1s infinite;
        }
        
        .events-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 4px;
            margin-top: 8px;
        }
        
        .events-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .event-item {
            padding: 16px;
            background: var(--bg-secondary);
            border: 3px solid var(--nb-black);
            margin-bottom: 12px;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .event-item:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0px var(--nb-black);
        }
        
        .event-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
            background: var(--resq-red);
        }
        
        .event-item.no-events::before {
            background: var(--text-secondary);
        }
        
        .event-time {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 6px;
            font-family: var(--font-mono);
        }
        
        .event-message {
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Device List Styles */
        .devices-card {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            box-shadow: var(--nb-shadow-lg);
            padding: 28px;
            position: relative;
            margin-bottom: 32px;
        }

        .devices-card::before {
            content: '🖥️ DEVICES';
            position: absolute;
            top: -14px;
            left: 20px;
            background: var(--resq-cyan);
            color: var(--nb-black);
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.625rem;
            padding: 4px 12px;
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .device-item {
            background: var(--bg-secondary);
            border: 3px solid var(--nb-black);
            padding: 16px;
            position: relative;
            transition: all 0.2s ease;
        }

        .device-item:hover {
            transform: translate(-4px, -4px);
            box-shadow: 4px 4px 0px var(--nb-black);
        }

        .device-item.online { border-color: var(--resq-lime); }
        .device-item.offline { border-color: var(--resq-red); }

        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .device-name {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1rem;
        }

        .device-location {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .device-status {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border: 2px solid var(--nb-black);
        }

        .device-status.online { background: var(--resq-lime); color: var(--nb-black); }
        .device-status.offline { background: var(--resq-red); color: white; }

        .device-time {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 8px;
            font-family: var(--font-mono);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-nav {
                justify-content: center;
            }
        }
        
        @media (max-width: 600px) {
            .dashboard-header {
                padding: 16px 20px;
            }
            
            .dashboard-main {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .logo-box {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
            }
            
            .logo-text h1 {
                font-size: 1.25rem;
            }
            
            .nav-btn {
                padding: 8px 14px;
                font-size: 0.75rem;
            }
        }
        
        /* Dark Theme Overrides */
        [data-theme="dark"] .dashboard-header {
            background: var(--bg-secondary);
            border-color: var(--resq-cyan);
        }
        
        [data-theme="dark"] .logo-box {
            border-color: var(--resq-cyan);
            box-shadow: 4px 4px 0px rgba(92, 220, 232, 0.25);
        }
        
        [data-theme="dark"] .stat-card {
            background: var(--bg-secondary);
            border-color: var(--resq-blue);
            box-shadow: 6px 6px 0px rgba(74, 158, 255, 0.2);
        }
        
        [data-theme="dark"] .stat-card.danger {
            border-color: var(--resq-red);
            box-shadow: 6px 6px 0px rgba(255, 107, 107, 0.2);
        }
        
        [data-theme="dark"] .stat-card.warning {
            border-color: var(--resq-yellow);
            box-shadow: 6px 6px 0px rgba(255, 217, 61, 0.2);
        }
        
        [data-theme="dark"] .stat-card.info {
            border-color: var(--resq-blue);
            box-shadow: 6px 6px 0px rgba(74, 158, 255, 0.2);
        }
        
        [data-theme="dark"] .stat-card.success {
            border-color: var(--resq-lime);
            box-shadow: 6px 6px 0px rgba(107, 207, 127, 0.2);
        }
        
        [data-theme="dark"] .stat-card::before {
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .stat-icon {
            border-color: #0f0f1a;
            box-shadow: 3px 3px 0px rgba(74, 158, 255, 0.2);
        }
        
        [data-theme="dark"] .stat-change {
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .status-badge {
            border-color: #0f0f1a;
            box-shadow: 3px 3px 0px rgba(107, 207, 127, 0.25);
        }
        
        [data-theme="dark"] .status-dot {
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .progress-bar {
            background: var(--bg-card);
            border-color: var(--resq-lime);
        }
        
        [data-theme="dark"] .chart-card {
            background: var(--bg-secondary);
            border-color: var(--resq-purple);
            box-shadow: 6px 6px 0px rgba(184, 122, 255, 0.2);
        }
        
        [data-theme="dark"] .chart-card::before {
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(184, 122, 255, 0.3);
        }
        
        [data-theme="dark"] .events-card {
            background: var(--bg-secondary);
            border-color: var(--resq-red);
            box-shadow: 6px 6px 0px rgba(255, 107, 107, 0.2);
        }
        
        [data-theme="dark"] .events-card::before {
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(255, 107, 107, 0.3);
        }
        
        [data-theme="dark"] .event-item {
            background: var(--bg-card);
            border-color: var(--resq-red);
        }
        
        [data-theme="dark"] .event-item:hover {
            box-shadow: 4px 4px 0px rgba(255, 107, 107, 0.25);
        }
        
        [data-theme="dark"] .marquee-banner {
            background: var(--bg-secondary);
            border-color: var(--resq-cyan);
        }
        
        [data-theme="dark"] .nav-btn.nb-btn-outline {
            border-color: var(--resq-cyan);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .nav-btn.nb-btn-primary {
            border-color: var(--resq-blue);
            box-shadow: 4px 4px 0px rgba(74, 158, 255, 0.25);
        }
        
        [data-theme="dark"] .nav-btn.nb-btn-warning {
            border-color: #0f0f1a;
            box-shadow: 4px 4px 0px rgba(255, 217, 61, 0.25);
        }
        
        [data-theme="dark"] .nav-btn.nb-btn-danger {
            border-color: var(--resq-red);
            box-shadow: 4px 4px 0px rgba(255, 107, 107, 0.25);
        }
    </style>
</head>
<body>
    <!-- Marquee Banner -->
    <div class="marquee-banner">
        <div class="marquee-content">
            <span class="marquee-item">⚡ ResQTech Dashboard</span>
            <span class="marquee-item">🚨 Emergency Monitoring System</span>
            <span class="marquee-item">📡 Real-time Updates</span>
            <span class="marquee-item">🛡️ 24/7 Protection</span>
            <span class="marquee-item">⚡ ResQTech Dashboard</span>
            <span class="marquee-item">🚨 Emergency Monitoring System</span>
            <span class="marquee-item">📡 Real-time Updates</span>
            <span class="marquee-item">🛡️ 24/7 Protection</span>
        </div>
    </div>
    
    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-content">
            <div class="header-logo">
                <div class="logo-box">R</div>
                <div class="logo-text">
                    <h1>Dashboard</h1>
                    <p>Real-time Monitoring</p>
                </div>
            </div>
            <nav class="header-nav">
                <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="nb-btn nav-btn nb-btn-outline">
                    🌐 <?php echo getCurrentLang() === 'th' ? 'EN' : 'TH'; ?>
                </a>
                <button class="nb-btn nav-btn nb-btn-outline" onclick="toggleTheme()">🌙</button>
                <a href="index.php" class="nb-btn nav-btn nb-btn-primary">🏠 Home</a>
                <a href="control-room.php" class="nb-btn nav-btn nb-btn-warning">🖥️ Control</a>
                <a href="logout.php" class="nb-btn nav-btn nb-btn-danger">🚪 Logout</a>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalEvents"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-label">Total Events</div>
                        <span class="stat-change up">📈 All time</span>
                    </div>
                    <div class="stat-icon danger">🚨</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="todayEvents"><?php echo $stats['today_events']; ?></div>
                        <div class="stat-label">Today's Events</div>
                        <span class="stat-change <?php echo $stats['today_events'] > 0 ? 'up' : 'down'; ?>">
                            📅 <?php echo date('d M Y'); ?>
                        </span>
                    </div>
                    <div class="stat-icon warning">📊</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="last24h"><?php echo $stats['last_24h_events']; ?></div>
                        <div class="stat-label">Last 24 Hours</div>
                        <span class="stat-change up">⏰ Recent</span>
                    </div>
                    <div class="stat-icon info">📈</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">
                            <span class="status-badge <?php echo $stats['is_online'] ? 'online' : 'offline'; ?>" id="esp32Indicator">
                                <span class="status-dot <?php echo $stats['is_online'] ? 'online' : 'offline'; ?>"></span>
                                <span id="esp32StatusText"><?php echo $stats['is_online'] ? 'Online' : 'Offline'; ?></span>
                            </span>
                        </div>
                        <div class="stat-label">ESP32 Status</div>
                        <span class="stat-change up" id="uptimeText">
                            🔌 <?php echo number_format($stats['uptime_percentage'], 1); ?>% Uptime
                        </span>
                        <?php if ($stats['last_heartbeat']): ?>
                        <span class="stat-change info-badge" id="lastHeartbeat">
                            💓 Last: <?php echo $stats['last_heartbeat']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon success">📡</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['uptime_percentage']; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Active Devices List -->
        <div class="devices-card">
            <h3 class="chart-title">🖥️ Active Devices</h3>
            <p class="chart-subtitle">Real-time status of all connected ESP32 units</p>
            
            <div id="devicesList" class="devices-grid">
                <!-- Devices will be populated here via JS -->
                <div class="device-item offline">
                    <div class="device-header">
                        <span class="device-name">Loading...</span>
                    </div>
                    <div class="device-location">Please wait...</div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">📊 Events Overview</h3>
                <p class="chart-subtitle">Emergency events statistics</p>
                <div class="chart-container">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">📈 Activity Timeline</h3>
                <p class="chart-subtitle">Last 7 days activity</p>
                <div class="chart-container">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="events-card">
            <h3 class="events-title">🕐 Recent Events</h3>
            <p class="events-subtitle">Latest emergency notifications</p>
            
            <div id="recentEvents">
                <?php if (empty($recentLogs)): ?>
                    <div class="event-item no-events">
                        <div class="event-message">No events recorded yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="event-item">
                            <div class="event-time">🕐 <?php echo sanitizeInput($log['timestamp']); ?></div>
                            <div class="event-message"><?php echo sanitizeInput($log['message']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script src="<?= asset('assets/js/dashboard.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
    <script>
        // Chart data from PHP
        const phpStats = {
            today: <?php echo $stats['today_events']; ?>,
            last24h: <?php echo $stats['last_24h_events']; ?>,
            last7d: <?php echo $stats['last_7d_events']; ?>,
            total: <?php echo $stats['total_events']; ?>
        };
        const phpDailyStats = <?php echo json_encode($dailyStats); ?>;
        
        // Neo-Brutalism Chart Colors
        const chartColors = {
            red: '#ff3b30',
            yellow: '#ffcc00',
            blue: '#0066ff',
            lime: '#32de84',
            purple: '#af52de',
            black: '#1a1a1a',
            white: '#fefefe'
        };
        
        // Events Overview Chart
        const eventsCtx = document.getElementById('eventsChart').getContext('2d');
        new Chart(eventsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Today', 'Last 24h', 'Last 7 Days', 'Older'],
                datasets: [{
                    data: [
                        phpStats.today,
                        Math.max(0, phpStats.last24h - phpStats.today),
                        Math.max(0, phpStats.last7d - phpStats.last24h),
                        Math.max(0, phpStats.total - phpStats.last7d)
                    ],
                    backgroundColor: [chartColors.red, chartColors.yellow, chartColors.blue, chartColors.purple],
                    borderColor: chartColors.black,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { family: "'Space Grotesk', sans-serif", weight: '700', size: 12 }
                        }
                    }
                }
            }
        });

        // Timeline Chart
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        const dates = Object.keys(phpDailyStats);
        const values = Object.values(phpDailyStats);
        
        new Chart(timelineCtx, {
            type: 'bar',
            data: {
                labels: dates.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Events',
                    data: values,
                    backgroundColor: chartColors.red,
                    borderColor: chartColors.black,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { 
                            stepSize: 1,
                            font: { family: "'JetBrains Mono', monospace", weight: '600' }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            font: { family: "'Space Grotesk', sans-serif", weight: '700', size: 11 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

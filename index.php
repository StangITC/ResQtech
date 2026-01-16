<?php
/**
 * Main Page - ResQTech System
 * Modern Neo-Brutalism Design with Compact Navigation
 */

require_once __DIR__ . '/includes/init.php';

// ต้องเข้าสู่ระบบก่อน
requireLogin();
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#ff3b30">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ResQTech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="ResQTech">

    <title><?php echo t('system_title'); ?></title>

    <link rel="manifest" href="manifest.json?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="apple-touch-icon" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">

    <style>
        /* ==========================================
           INDEX PAGE - MODERN DESIGN
           ========================================== */

        body {
            display: block;
            min-height: 100vh;
            padding: 0;
            background: var(--bg-primary);
        }

        /* Main Layout */
        .page-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Welcome Hero Section */
        .hero-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .hero-section {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* User Welcome Card */
        .welcome-card {
            background: var(--bg-card);
            border: 3px solid var(--nb-black);
            box-shadow: 6px 6px 0px var(--nb-black);
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--resq-red), var(--resq-yellow), var(--resq-lime), var(--resq-cyan), var(--resq-blue));
        }

        .welcome-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--resq-blue), var(--resq-purple));
            border: 3px solid var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .welcome-info h2 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
        }

        .welcome-info .user-email {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .login-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-secondary);
            border: 2px solid var(--nb-black);
            font-size: 0.75rem;
            font-weight: 700;
            font-family: var(--font-mono);
        }

        /* Digital Clock */
        .clock-display {
            margin-top: 20px;
            padding: 16px;
            background: var(--nb-black);
            border: 3px solid var(--nb-black);
            text-align: center;
        }

        .clock-time {
            font-family: var(--font-mono);
            font-size: 2rem;
            font-weight: 800;
            color: var(--resq-lime);
            letter-spacing: 2px;
        }

        .clock-date {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: #888;
            margin-top: 4px;
        }

        /* Status Card */
        .status-card {
            background: var(--bg-card);
            border: 3px solid var(--nb-black);
            box-shadow: 6px 6px 0px var(--nb-black);
            padding: 24px;
        }

        .status-title {
            font-family: var(--font-display);
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-display {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border: 2px solid var(--nb-black);
            border-radius: 4px;
        }

        .status-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--resq-blue), var(--resq-purple));
            border: 2px solid var(--nb-black);
            border-radius: 8px;
            color: white;
            flex-shrink: 0;
        }
        
        .status-icon svg {
            width: 28px;
            height: 28px;
        }
        
        .status-display.online .status-icon {
            background: linear-gradient(135deg, var(--resq-lime), #2ecc71);
        }
        
        .status-display.offline .status-icon {
            background: linear-gradient(135deg, var(--resq-red), #e74c3c);
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(0.98);
            }
        }

        .status-info {
            flex: 1;
            min-width: 0;
        }

        .status-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 700;
        }

        .status-value {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 800;
            margin-top: 2px;
        }

        .status-value.online {
            color: var(--resq-lime);
        }

        .status-value.offline {
            color: var(--resq-red);
        }

        .status-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 4px;
            font-family: var(--font-mono);
        }

        /* Devices Grid */
        .devices-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 2px dashed var(--text-secondary);
        }

        .devices-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .devices-grid {
            display: grid;
            gap: 8px;
        }

        .device-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: var(--bg-secondary);
            border: 2px solid var(--nb-black);
            font-size: 0.8rem;
        }

        .device-item.online {
            border-left: 4px solid var(--resq-lime);
        }

        .device-item.offline {
            border-left: 4px solid var(--resq-red);
            opacity: 0.7;
        }

        .device-name {
            font-weight: 700;
        }

        .device-status {
            font-size: 0.7rem;
            padding: 2px 8px;
            border: 1px solid;
            font-weight: 700;
        }

        .device-status.online {
            background: var(--resq-lime);
            border-color: var(--nb-black);
        }

        .device-status.offline {
            background: var(--resq-red);
            color: white;
            border-color: var(--nb-black);
        }

        /* Notification Section */
        .notification-card {
            background: var(--bg-card);
            border: 3px solid var(--nb-black);
            box-shadow: 6px 6px 0px var(--nb-black);
            padding: 24px;
            margin-top: 20px;
        }

        .notification-card::before {
            content: '📢 MANUAL ALERT';
            position: absolute;
            top: -12px;
            left: 16px;
            background: var(--resq-red);
            color: white;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.65rem;
            padding: 4px 10px;
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            letter-spacing: 1px;
        }

        .notification-card {
            position: relative;
            margin-top: 32px;
        }

        .notification-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .notification-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-textarea {
            width: 100%;
            padding: 12px;
            font-family: var(--font-body);
            font-size: 0.9rem;
            border: 3px solid var(--nb-black);
            background: var(--bg-secondary);
            color: var(--text-primary);
            resize: vertical;
            min-height: 100px;
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--resq-blue);
            box-shadow: 4px 4px 0px var(--resq-blue);
        }

        .submit-btn {
            width: 100%;
            padding: 14px 24px;
            background: var(--resq-red);
            color: white;
            border: 3px solid var(--nb-black);
            box-shadow: 4px 4px 0px var(--nb-black);
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--nb-black);
        }

        .submit-btn:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--nb-black);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #status {
            margin-top: 12px;
            padding: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .status-success {
            background: var(--resq-lime);
            border: 2px solid var(--nb-black);
        }

        .status-error {
            background: var(--resq-red);
            color: white;
            border: 2px solid var(--nb-black);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: var(--bg-secondary);
            border: 2px solid var(--nb-black);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.15s ease;
        }

        .quick-action:hover {
            transform: translateY(-4px);
            box-shadow: 4px 4px 0px var(--nb-black);
            background: var(--resq-yellow);
        }

        .quick-action-icon {
            font-size: 1.5rem;
        }

        .quick-action-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dark Theme */
        [data-theme="dark"] .welcome-card {
            border-color: var(--resq-cyan);
            box-shadow: 6px 6px 0px rgba(92, 220, 232, 0.2);
        }

        [data-theme="dark"] .status-card {
            border-color: var(--resq-blue);
            box-shadow: 6px 6px 0px rgba(74, 158, 255, 0.2);
        }

        [data-theme="dark"] .notification-card {
            border-color: var(--resq-red);
            box-shadow: 6px 6px 0px rgba(255, 107, 107, 0.2);
        }

        [data-theme="dark"] .clock-display {
            background: #0a0a12;
            border-color: var(--resq-cyan);
        }

        [data-theme="dark"] .status-display {
            border-color: var(--resq-blue);
        }

        [data-theme="dark"] .quick-action:hover {
            background: var(--resq-cyan);
            color: var(--nb-black);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .page-main {
                padding: 16px;
            }

            .welcome-card,
            .status-card,
            .notification-card {
                padding: 16px;
                box-shadow: 4px 4px 0px var(--nb-black);
            }

            .clock-time {
                font-size: 1.5rem;
            }

            .welcome-info h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <?php renderNavigation('home', t('system_title'), t('system_subtitle')); ?>

    <main class="page-main">
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-header">
                    <?php
                    $userPic = getUserPicture();
                    if (isGoogleLogin() && $userPic && isValidUrl($userPic)): ?>
                        <div class="user-avatar">
                            <img src="<?php echo sanitizeInput($userPic); ?>" alt="Profile"
                                onerror="this.parentElement.innerHTML='👤'" referrerpolicy="no-referrer">
                        </div>
                    <?php else: ?>
                        <div class="user-avatar">👤</div>
                    <?php endif; ?>

                    <div class="welcome-info">
                        <h2><?php echo t('hello_user', ['username' => sanitizeInput(getUsername())]); ?></h2>
                        <?php if (isGoogleLogin() && getUserEmail()): ?>
                            <div class="user-email">📧 <?php echo sanitizeInput(getUserEmail()); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="login-meta">
                    <span class="meta-badge">🕐
                        <?php echo t('login_time', ['time' => date('H:i', getLoginTime())]); ?></span>
                    <span class="meta-badge">📅 <?php echo date('d/m/Y'); ?></span>
                    <span class="meta-badge">🔐 <?php echo isGoogleLogin() ? 'Google' : 'Admin'; ?></span>
                </div>

                <div class="clock-display">
                    <div class="clock-time" id="clockTime">--:--:--</div>
                    <div class="clock-date" id="clockDate">Loading...</div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="dashboard.php" class="quick-action">
                        <span class="quick-action-icon">📊</span>
                        <span class="quick-action-label">Dashboard</span>
                    </a>
                    <a href="control-room.php" class="quick-action">
                        <span class="quick-action-icon">🖥️</span>
                        <span class="quick-action-label">Control Room</span>
                    </a>
                    <a href="history-dashboard.php" class="quick-action">
                        <span class="quick-action-icon">📜</span>
                        <span class="quick-action-label">History</span>
                    </a>
                    <a href="live-dashboard.php" class="quick-action">
                        <span class="quick-action-icon">🔴</span>
                        <span class="quick-action-label">Live Feed</span>
                    </a>
                </div>
            </div>

            <!-- Status Card -->
            <div class="status-card">
                <div class="status-title">
                    <span>🔌</span>
                    <span>System Status</span>
                </div>

                <div class="status-display" id="esp32Indicator">
                    <div class="status-icon" id="statusIcon">❓</div>
                    <div class="status-info">
                        <div class="status-label">Connection</div>
                        <div class="status-value" id="esp32StatusText">CHECKING...</div>
                        <div class="status-meta" id="lastEvent">Waiting for signal...</div>
                    </div>
                </div>

                <!-- Active Devices -->
                <div class="devices-section">
                    <div class="devices-title">📡 Active Devices</div>
                    <div class="devices-grid" id="devicesList">
                        <div class="device-item offline">
                            <span class="device-name">Loading devices...</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Notification Section -->
        <section class="notification-card">
            <h3 class="notification-title"><?php echo t('notification_title'); ?></h3>
            <p class="notification-desc"><?php echo t('notification_desc'); ?></p>

            <form id="notificationForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label class="form-label" for="notificationMessage">
                        <?php echo t('notification_message_label'); ?>
                    </label>
                    <textarea id="notificationMessage" name="message" class="form-textarea" rows="3" maxlength="1000"
                        placeholder="<?php echo t('notification_message_placeholder'); ?>" required></textarea>
                </div>
                <button type="submit" id="sendButton" class="submit-btn">
                    📤 <?php echo t('send_button'); ?>
                </button>
            </form>

            <div id="status" role="status" aria-live="polite"></div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
    <script>
        // Enhanced Clock
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Bangkok' };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Bangkok' };

            const clockTime = document.getElementById('clockTime');
            const clockDate = document.getElementById('clockDate');

            if (clockTime) clockTime.textContent = now.toLocaleTimeString('th-TH', timeOptions);
            if (clockDate) clockDate.textContent = now.toLocaleDateString('th-TH', dateOptions);
        }

        // Update clock immediately and every second
        updateClock();
        setInterval(updateClock, 1000);

        // Enhanced devices list rendering
        function updateDevicesUI(devices) {
            const container = document.getElementById('devicesList');
            if (!container || !devices) return;

            if (devices.length === 0) {
                container.innerHTML = '<div class="device-item offline"><span class="device-name">No devices found</span></div>';
                return;
            }

            container.innerHTML = devices.map(device => `
                <div class="device-item ${device.is_online ? 'online' : 'offline'}">
                    <span class="device-name">${device.id} - ${device.location || 'Unknown'}</span>
                    <span class="device-status ${device.is_online ? 'online' : 'offline'}">
                        ${device.is_online ? 'ONLINE' : 'OFFLINE'}
                    </span>
                </div>
            `).join('');
        }
    </script>
</body>

</html>
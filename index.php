<?php
/**
 * Main Page - ResQTech System
 * Neo-Brutalism Design
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
    <meta http-equiv="refresh" content="300">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <style>
        /* Page Specific Styles */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }
        
        /* Background Stickers */
        .bg-sticker {
            position: fixed;
            font-size: 4rem;
            opacity: 0.08;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        
        .bg-sticker:nth-child(1) { top: 5%; left: 3%; animation-delay: 0s; }
        .bg-sticker:nth-child(2) { top: 70%; right: 5%; animation-delay: 1.5s; }
        .bg-sticker:nth-child(3) { bottom: 10%; left: 8%; animation-delay: 3s; }
        .bg-sticker:nth-child(4) { top: 25%; right: 12%; animation-delay: 4.5s; }
        .bg-sticker:nth-child(5) { bottom: 30%; right: 3%; animation-delay: 2s; }
        
        .main-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 10;
        }
        
        /* Header Card */
        .header-card {
            background: var(--resq-blue);
            color: white;
            padding: 24px;
            border: var(--nb-border-thick);
            position: relative;
            margin-bottom: 0;
        }
        
        .header-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: repeating-linear-gradient(
                90deg,
                var(--resq-red) 0px,
                var(--resq-red) 20px,
                var(--nb-black) 20px,
                var(--nb-black) 40px
            );
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .header-controls {
            display: flex;
            gap: 8px;
        }
        
        .header-controls .nb-toggle,
        .header-controls .lang-btn {
            background: rgba(255,255,255,0.15);
            border-color: white;
            color: white;
            box-shadow: 2px 2px 0px rgba(0,0,0,0.3);
        }
        
        .lang-btn {
            padding: 10px 14px;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.75rem;
            text-decoration: none;
            border: 3px solid white;
            transition: all 0.1s ease;
        }
        
        .lang-btn:hover {
            background: var(--resq-yellow);
            color: var(--nb-black);
            border-color: var(--nb-black);
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .logo-box {
            width: 56px;
            height: 56px;
            background: var(--resq-red);
            border: 3px solid var(--nb-black);
            box-shadow: 4px 4px 0px var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 2rem;
            color: white;
            transform: rotate(-5deg);
        }
        
        .header-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 4px;
        }
        
        /* Main Content */
        .main-content {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            border-top: none;
            padding: 28px;
            box-shadow: var(--nb-shadow-lg);
        }
        
        /* User Info Card */
        .user-card {
            background: var(--bg-secondary);
            border: 3px solid var(--nb-black);
            box-shadow: 5px 5px 0px var(--nb-black);
            padding: 24px;
            position: relative;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .user-card::before {
            content: '👤 USER';
            position: absolute;
            top: -14px;
            left: 16px;
            background: var(--resq-lime);
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
        
        .user-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            border: 4px solid var(--nb-black);
            box-shadow: 5px 5px 0px var(--nb-black);
            background: var(--resq-yellow);
            overflow: hidden;
            transform: rotate(-3deg);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .user-email {
            background: var(--bg-card);
            border: 2px solid var(--nb-black);
            padding: 6px 14px;
            display: inline-block;
            font-size: 0.875rem;
            margin-bottom: 16px;
        }
        
        .user-login-time {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }
        
        /* Digital Clock */
        .digital-clock {
            background: var(--nb-black);
            color: var(--resq-lime);
            font-family: var(--font-mono);
            font-size: 1.5rem;
            font-weight: 700;
            padding: 16px 24px;
            border: 3px solid var(--resq-lime);
            box-shadow: 5px 5px 0px var(--resq-lime);
            text-align: center;
            letter-spacing: 3px;
            margin: 20px 0;
            position: relative;
        }
        
        .digital-clock::before {
            content: '⏰ TIME';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--resq-lime);
            color: var(--nb-black);
            font-size: 0.625rem;
            padding: 2px 10px;
            font-family: var(--font-display);
            font-weight: 800;
        }
        
        /* User Actions */
        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 16px;
        }
        
        .user-actions .nb-btn {
            flex: 1;
            min-width: 120px;
            padding: 12px 16px;
            font-size: 0.8125rem;
        }
        
        /* ESP32 Status - New Design */
        .esp32-card {
            background: var(--bg-secondary);
            border: 3px solid var(--nb-black);
            box-shadow: 5px 5px 0px var(--nb-black);
            padding: 24px;
            position: relative;
            margin-bottom: 24px;
        }
        
        .esp32-card::before {
            content: '🔌 DEVICE';
            position: absolute;
            top: -14px;
            left: 16px;
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
        
        .esp32-title {
            font-family: var(--font-display);
            font-size: 1.125rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            text-align: center;
        }

        .status-display-card {
            display: flex;
            flex-direction: row;    /* Horizontal layout */
            align-items: center;    /* Center vertically */
            justify-content: flex-start; /* Left align content */
            gap: 20px;              /* Consistent gap */
            background: var(--bg-card);
            border: 3px solid var(--nb-black);
            padding: 20px;          /* Standard padding */
            transition: all 0.3s ease;
            text-align: left;       /* Left align text */
        }

        .status-display-card.online {
            border-color: var(--resq-lime);
            box-shadow: 4px 4px 0px rgba(50, 222, 132, 0.2);
        }

        .status-display-card.offline {
            border-color: var(--resq-red);
            box-shadow: 4px 4px 0px rgba(255, 59, 48, 0.2);
        }

        .status-icon-wrapper {
            width: 72px;            /* Slightly smaller than vertical mode */
            height: 72px;
            flex-shrink: 0;
            background: var(--nb-black);
            display: flex;
            align-items: center;    /* Center vertically */
            justify-content: center; /* Center horizontally */
            font-size: 2rem;
            border: 3px solid var(--nb-black);
            box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
            margin-bottom: 0;       /* Remove bottom margin */
            padding: 0;             /* Reset padding */
        }
        
        .status-icon-wrapper svg {
            display: block;         /* Remove inline spacing */
            margin: auto;           /* Ensure centering */
        }

        .status-display-card.online .status-icon-wrapper {
            background: var(--resq-lime);
            animation: pulse 2s infinite;
        }

        .status-display-card.offline .status-icon-wrapper {
            background: var(--resq-red);
            color: white;
        }

        .status-details {
            flex: 1;
        }

        .status-label-small {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .status-value-large {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .status-meta {
            font-size: 0.8125rem;
            font-family: var(--font-mono);
            color: var(--text-secondary);
            margin-top: 6px;
            font-weight: 500;
        }

        /* Responsive Status Card */
        @media (max-width: 400px) {
            .status-display-card {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
        }

        /* Mini Devices Grid */
        .devices-grid-mini {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }

        .device-item {
            background: var(--bg-card);
            border: 2px solid var(--nb-black);
            padding: 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .device-item.online { border-color: var(--resq-lime); }
        .device-item.offline { border-color: var(--resq-red); opacity: 0.8; }

        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .device-name { font-weight: 800; font-family: var(--font-display); }
        
        .device-status { 
            font-size: 0.65rem; 
            font-weight: 700; 
            padding: 2px 4px; 
            border: 1px solid var(--nb-black);
        }

        .device-status.online { background: var(--resq-lime); }
        .device-status.offline { background: var(--resq-red); color: white; }

        .device-location { font-size: 0.75rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .device-time { display: none; } /* Hide time in mini view to save space */
        
        /* Notification Section styles removed - using nb-notification-section from neo-brutalism.css */
        
        #sendButton {
            width: 100%;
            background: var(--resq-red);
            color: white;
            padding: 18px 24px;
            font-size: 1.125rem;
        }
        
        #sendButton:hover {
            background: #e02620;
        }
        
        #sendButton:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
            box-shadow: var(--nb-shadow);
        }
        
        #status {
            margin-top: 16px;
        }
        
        .status-success {
            background: var(--resq-lime) !important;
            color: var(--nb-black) !important;
        }
        
        .status-error {
            background: var(--resq-red) !important;
            color: white !important;
        }
        
        /* Rotation Badge */
        .rotate-badge {
            position: absolute;
            top: -20px;
            right: -15px;
            width: 50px;
            height: 50px;
            background: var(--resq-yellow);
            border: 3px solid var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            animation: spin 15s linear infinite;
            z-index: 20;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 540px) {
            .main-container {
                max-width: 100%;
            }
            
            .header-card,
            .main-content {
                padding: 20px;
            }
            
            .header-top {
                flex-direction: column-reverse;
                gap: 16px;
            }
            
            .header-controls {
                justify-content: flex-end;
                width: 100%;
            }
            
            .user-actions .nb-btn {
                flex: 1 1 100%;
            }
            
            .digital-clock {
                font-size: 1.25rem;
            }
            
            .rotate-badge {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
                top: -15px;
                right: -10px;
            }
        }
        
        /* Dark Theme Overrides */
        [data-theme="dark"] .header-card {
            border-color: var(--resq-cyan);
        }
        
        [data-theme="dark"] .header-card::after {
            background: repeating-linear-gradient(
                90deg,
                var(--resq-red) 0px,
                var(--resq-red) 20px,
                #0f0f1a 20px,
                #0f0f1a 40px
            );
        }
        
        [data-theme="dark"] .main-content {
            border-color: var(--resq-cyan);
            box-shadow: 6px 6px 0px rgba(92, 220, 232, 0.2);
        }
        
        [data-theme="dark"] .logo-box {
            border-color: var(--resq-cyan);
            box-shadow: 4px 4px 0px rgba(92, 220, 232, 0.25);
        }
        
        [data-theme="dark"] .user-card {
            background: var(--bg-secondary);
            border-color: var(--resq-purple);
            box-shadow: 5px 5px 0px rgba(184, 122, 255, 0.2);
        }
        
        [data-theme="dark"] .user-card::before {
            background: var(--resq-lime);
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(107, 207, 127, 0.3);
            color: #0f0f1a;
        }
        
        [data-theme="dark"] .user-avatar {
            border-color: var(--resq-purple);
            box-shadow: 5px 5px 0px rgba(184, 122, 255, 0.25);
        }
        
        [data-theme="dark"] .user-email {
            background: var(--bg-card);
            border-color: var(--resq-purple);
        }
        
        [data-theme="dark"] .digital-clock {
            background: var(--bg-card);
            border-color: var(--resq-lime);
            box-shadow: 5px 5px 0px rgba(107, 207, 127, 0.25);
        }
        
        [data-theme="dark"] .digital-clock::before {
            background: var(--resq-lime);
            color: #0f0f1a;
        }
        
        [data-theme="dark"] .esp32-card {
            background: var(--bg-secondary);
            border-color: var(--resq-cyan);
            box-shadow: 5px 5px 0px rgba(92, 220, 232, 0.2);
        }
        
        [data-theme="dark"] .esp32-card::before {
            background: var(--resq-cyan);
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(92, 220, 232, 0.3);
            color: #0f0f1a;
        }
        
        [data-theme="dark"] .status-indicator {
            background: var(--bg-card);
            border-color: var(--resq-cyan);
        }
        
        [data-theme="dark"] .status-dot {
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .last-event {
            background: var(--resq-orange);
            color: #0f0f1a;
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(255, 170, 92, 0.3);
        }
        

        [data-theme="dark"] .rotate-badge {
            background: var(--resq-orange);
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .lang-btn {
            background: rgba(92, 220, 232, 0.15);
            border-color: var(--resq-cyan);
        }
        
        [data-theme="dark"] .lang-btn:hover {
            background: var(--resq-cyan);
            color: #0f0f1a;
            border-color: #0f0f1a;
        }
        
        [data-theme="dark"] .header-controls .nb-toggle {
            background: rgba(92, 220, 232, 0.15);
            border-color: var(--resq-cyan);
            box-shadow: 2px 2px 0px rgba(92, 220, 232, 0.2);
        }
        
        [data-theme="dark"] .nb-btn-primary {
            border-color: var(--resq-blue);
            box-shadow: 4px 4px 0px rgba(74, 158, 255, 0.3);
        }
        
        [data-theme="dark"] .nb-btn-warning {
            border-color: #0f0f1a;
            box-shadow: 4px 4px 0px rgba(255, 217, 61, 0.3);
        }
        
        [data-theme="dark"] .nb-btn-danger {
            border-color: var(--resq-red);
            box-shadow: 4px 4px 0px rgba(255, 107, 107, 0.3);
        }
        
        [data-theme="dark"] #sendButton {
            border-color: var(--resq-red);
            box-shadow: 4px 4px 0px rgba(255, 107, 107, 0.3);
        }
    </style>
</head>
<body>
    <!-- Background Stickers -->
    <div class="bg-sticker">🚨</div>
    <div class="bg-sticker">🔔</div>
    <div class="bg-sticker">⚡</div>
    <div class="bg-sticker">🛡️</div>
    <div class="bg-sticker">📡</div>
    
    <div class="main-container">
        <div class="rotate-badge">⚡</div>
        
        <!-- Header -->
        <div class="header-card">
            <div class="header-top">
                <div class="header-logo">
                    <div class="logo-box">R</div>
                    <div>
                        <h1 class="header-title"><?php echo t('system_title'); ?></h1>
                        <p class="header-subtitle"><?php echo t('system_subtitle'); ?></p>
                    </div>
                </div>
                <div class="header-controls">
                    <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="lang-btn">
                        <?php echo getCurrentLang() === 'th' ? 'EN' : 'TH'; ?>
                    </a>
                    <button class="nb-toggle" onclick="toggleTheme()" title="<?php echo t('theme_toggle'); ?>">🌙</button>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- User Info -->
            <div class="user-card">
                <?php 
                $userPic = getUserPicture();
                if (isGoogleLogin() && $userPic && isValidUrl($userPic)): 
                ?>
                <div class="user-avatar">
                    <img src="<?php echo sanitizeInput($userPic); ?>" 
                         alt="Profile Picture" 
                         onerror="this.parentElement.innerHTML='👤'"
                         referrerpolicy="no-referrer">
                </div>
                <?php endif; ?>
                
                <h2 class="user-name">👋 <?php echo t('hello_user', ['username' => sanitizeInput(getUsername())]); ?></h2>
                
                <?php if (isGoogleLogin() && getUserEmail()): ?>
                <div class="user-email">
                    📧 <?php echo sanitizeInput(getUserEmail()); ?>
                </div>
                <?php endif; ?>
                
                <p class="user-login-time"><?php echo t('login_time', ['time' => date('d/m/Y H:i:s', getLoginTime())]); ?></p>
                
                <div class="digital-clock" id="currentTime">
                    🕐 <?php echo t('loading_clock'); ?>
                </div>
                
                <div class="user-actions">
                    <a href="dashboard.php" class="nb-btn nb-btn-primary">📊 Dashboard</a>
                    <a href="control-room.php" class="nb-btn nb-btn-warning">🖥️ Control Room</a>
                    <a href="logout.php" class="nb-btn nb-btn-danger">🚪 <?php echo t('logout'); ?></a>
                </div>
            </div>
            
            <!-- ESP32 Status -->
            <div class="esp32-card">
                <h2 class="esp32-title">🔌 SYSTEM STATUS</h2>
                
                <div class="status-display-card" id="esp32Indicator">
                    <div class="status-icon-wrapper">
                        <span class="status-icon-large" id="statusIcon">❓</span>
                    </div>
                    <div class="status-details">
                        <div class="status-label-small">CONNECTION</div>
                        <div class="status-value-large" id="esp32StatusText">CHECKING...</div>
                        <div class="status-meta" id="lastEvent">Waiting for signal...</div>
                    </div>
                </div>

                <!-- Active Devices List (Mini Version) -->
                <div style="margin-top: 24px; border-top: 2px solid var(--nb-black); padding-top: 16px;">
                    <h3 style="font-family: var(--font-display); font-size: 0.875rem; margin-bottom: 12px; text-transform: uppercase;">📡 Active Devices</h3>
                    <div id="devicesList" class="devices-grid-mini">
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.8rem;">Loading devices...</div>
                    </div>
                </div>
            </div>
            
            <!-- Notification Section -->
            <div class="nb-notification-section">
                <h1 class="nb-section-title"><?php echo t('notification_title'); ?></h1>
                <p class="nb-section-desc"><?php echo t('notification_desc'); ?></p>
                
                <form id="notificationForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="nb-form-group" style="margin-top: 20px;">
                        <label class="nb-label" for="notificationMessage"><?php echo t('notification_message_label'); ?></label>
                        <textarea
                            id="notificationMessage"
                            name="message"
                            class="nb-input"
                            rows="4"
                            maxlength="1000"
                            placeholder="<?php echo t('notification_message_placeholder'); ?>"
                            required
                        ></textarea>
                    </div>
                    <button type="submit" id="sendButton" class="nb-btn nb-btn-danger" style="width: 100%">
                        📤 <?php echo t('send_button'); ?>
                    </button>
                </form>
                
                <div id="status" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script src="<?= asset('assets/js/dashboard.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

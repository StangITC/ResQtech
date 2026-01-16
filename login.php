<?php
/**
 * Login Page - ResQTech System
 * Neo-Brutalism Design
 */

require_once __DIR__ . '/includes/init.php';

// ถ้าเข้าสู่ระบบแล้ว ให้ไปหน้า index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// ตรวจสอบข้อความจาก URL parameters
if (isset($_GET['logout'])) {
    $success_message = t('logout_success');
}
if (isset($_GET['timeout'])) {
    $error_message = t('session_timeout');
}
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    $success_message = 'เข้าสู่ระบบด้วย Google สำเร็จ!';
}
if (isset($_GET['google_error'])) {
    $error_message = 'Google Login Error: ' . sanitizeInput($_GET['google_error']);
}

// ตรวจสอบการ Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // ตรวจสอบ brute force
        $attemptCheck = checkLoginAttempts();

        if (!$attemptCheck['allowed']) {
            $remaining_minutes = ceil($attemptCheck['remaining_time'] / 60);
            $error_message = t('too_many_attempts', ['minutes' => $remaining_minutes]);
        } else {
            if (validateLogin($username, $password)) {
                // Login สำเร็จ
                resetLoginAttempts();
                createLoginSession($username);

                // Log successful login
                logMessage(LOGIN_LOG_FILE, "Login success: $username from " . getClientIP());

                header('Location: index.php');
                exit;
            } else {
                // Login ผิด
                recordFailedAttempt();
                $error_message = t('login_failed');
            }
        }
    } // end of CSRF else block
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
    <meta http-equiv="refresh" content="300">
    <meta name="theme-color" content="#ff3b30">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ResQTech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="ResQTech">

    <title><?php echo t('login_title'); ?> - ResQTech</title>

    <link rel="manifest" href="manifest.json?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            overflow-x: hidden;
        }

        /* Decorative Background */
        .bg-decoration {
            position: fixed;
            pointer-events: none;
            z-index: 0;
        }

        .bg-sticker {
            position: fixed;
            font-size: 3rem;
            opacity: 0.15;
            animation: float 6s ease-in-out infinite;
        }

        .bg-sticker:nth-child(1) {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .bg-sticker:nth-child(2) {
            top: 60%;
            right: 8%;
            animation-delay: 1s;
        }

        .bg-sticker:nth-child(3) {
            bottom: 15%;
            left: 10%;
            animation-delay: 2s;
        }

        .bg-sticker:nth-child(4) {
            top: 30%;
            right: 15%;
            animation-delay: 3s;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }

        .login-header {
            background: var(--resq-red);
            color: white;
            padding: 32px 28px;
            border: var(--nb-border-thick);
            border-bottom: none;
            position: relative;
            overflow: visible;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: repeating-linear-gradient(90deg,
                    var(--resq-yellow) 0px,
                    var(--resq-yellow) 16px,
                    var(--nb-black) 16px,
                    var(--nb-black) 32px);
        }

        .header-controls {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .header-controls .nb-toggle {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            color: white;
            box-shadow: 2px 2px 0px rgba(0, 0, 0, 0.3);
        }

        .lang-toggle {
            padding: 8px 14px;
            background: var(--resq-yellow);
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--nb-black);
            text-decoration: none;
            transition: all 0.1s ease;
        }

        .lang-toggle:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px var(--nb-black);
        }

        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding-top: 16px;
        }

        .login-logo-box {
            width: 72px;
            height: 72px;
            background: white;
            border: 4px solid var(--nb-black);
            box-shadow: 6px 6px 0px var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 2.5rem;
            color: var(--resq-red);
            transform: rotate(-5deg);
        }

        .login-title {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .login-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
            text-align: center;
        }

        .login-body {
            background: var(--bg-card);
            border: var(--nb-border-thick);
            border-top: none;
            padding: 32px 28px;
            box-shadow: var(--nb-shadow-lg);
        }

        /* Emergency Badge */
        .emergency-badge {
            position: absolute;
            top: -20px;
            right: -10px;
            background: var(--resq-yellow);
            color: var(--nb-black);
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.625rem;
            padding: 6px 12px;
            border: 2px solid var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
            transform: rotate(12deg);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Form Override */
        .nb-form-group {
            margin-bottom: 20px;
        }

        .nb-input:focus {
            border-color: var(--resq-red);
        }

        .submit-btn {
            width: 100%;
            background: var(--resq-red);
            color: white;
            padding: 16px 24px;
            font-size: 1rem;
        }

        .submit-btn:hover {
            background: #e02620;
        }

        /* Security Section */
        .security-section {
            margin-top: 24px;
            padding: 20px;
            background: var(--bg-secondary);
            border: 3px solid var(--nb-black);
        }

        .security-title {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 0.875rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            padding: 8px 10px;
            background: var(--bg-card);
            border: 2px solid var(--nb-black);
            font-weight: 500;
        }

        .security-item::before {
            content: '✓';
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            background: var(--resq-lime);
            border: 2px solid var(--nb-black);
            font-size: 0.625rem;
            font-weight: 900;
        }

        /* Google Button */
        .google-btn {
            margin-top: 8px;
        }

        .google-setup-notice {
            margin-top: 24px;
            padding: 16px;
            background: var(--resq-yellow);
            border: 3px solid var(--nb-black);
            box-shadow: 3px 3px 0px var(--nb-black);
            font-weight: 700;
            font-size: 0.875rem;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 24px 20px;
            }

            .login-body {
                padding: 24px 20px;
            }

            .login-logo-box {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .security-grid {
                grid-template-columns: 1fr;
            }

            .header-controls {
                position: static;
                justify-content: center;
                margin-bottom: 16px;
            }

            .emergency-badge {
                top: -15px;
                right: 5px;
            }
        }

        /* Dark Theme Overrides */
        [data-theme="dark"] .login-header {
            border-color: var(--resq-cyan);
        }

        [data-theme="dark"] .login-header::after {
            background: repeating-linear-gradient(90deg,
                    var(--resq-yellow) 0px,
                    var(--resq-yellow) 16px,
                    #0f0f1a 16px,
                    #0f0f1a 32px);
        }

        [data-theme="dark"] .login-body {
            border-color: var(--resq-cyan);
            box-shadow: 6px 6px 0px rgba(92, 220, 232, 0.2);
        }

        [data-theme="dark"] .login-logo-box {
            background: var(--bg-card);
            border-color: var(--resq-cyan);
            box-shadow: 6px 6px 0px rgba(92, 220, 232, 0.25);
            color: var(--resq-red);
        }

        [data-theme="dark"] .emergency-badge {
            background: var(--resq-orange);
            color: #0f0f1a;
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(255, 170, 92, 0.3);
        }

        [data-theme="dark"] .security-section {
            background: var(--bg-secondary);
            border-color: var(--resq-purple);
        }

        [data-theme="dark"] .security-item {
            background: var(--bg-card);
            border-color: var(--resq-purple);
            color: var(--text-primary);
        }

        [data-theme="dark"] .security-item::before {
            background: var(--resq-lime);
            border-color: #0f0f1a;
            color: #0f0f1a;
        }

        [data-theme="dark"] .lang-toggle {
            background: var(--resq-cyan);
            color: #0f0f1a;
            border-color: #0f0f1a;
            box-shadow: 2px 2px 0px rgba(92, 220, 232, 0.3);
        }

        [data-theme="dark"] .google-setup-notice {
            background: var(--resq-orange);
            color: #0f0f1a;
            border-color: #0f0f1a;
            box-shadow: 3px 3px 0px rgba(255, 170, 92, 0.3);
        }

        [data-theme="dark"] .nb-input {
            background: var(--bg-secondary);
            border-color: var(--resq-blue);
        }

        [data-theme="dark"] .nb-input:focus {
            border-color: var(--resq-cyan);
            box-shadow: 4px 4px 0px rgba(92, 220, 232, 0.3);
        }

        [data-theme="dark"] .submit-btn {
            border-color: var(--resq-red);
            box-shadow: 4px 4px 0px rgba(255, 107, 107, 0.3);
        }

        [data-theme="dark"] .nb-google-btn {
            background: var(--bg-secondary);
            border-color: var(--resq-blue);
            box-shadow: 4px 4px 0px rgba(74, 158, 255, 0.25);
        }

        [data-theme="dark"] .nb-google-btn:hover {
            background: var(--resq-yellow);
            color: #0f0f1a;
        }

        [data-theme="dark"] .nb-divider::before,
        [data-theme="dark"] .nb-divider::after {
            background: var(--resq-purple);
        }

        [data-theme="dark"] .header-controls .nb-toggle {
            background: rgba(92, 220, 232, 0.2);
            border-color: var(--resq-cyan);
            box-shadow: 2px 2px 0px rgba(92, 220, 232, 0.3);
        }
    </style>
</head>

<body>
    <!-- Background Decorations -->
    <div class="bg-sticker">🚨</div>
    <div class="bg-sticker">🔔</div>
    <div class="bg-sticker">⚡</div>
    <div class="bg-sticker">🛡️</div>

    <div class="login-card">
        <div class="emergency-badge">🚨 Emergency System</div>

        <!-- Header -->
        <div class="login-header">
            <div class="header-controls">
                <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="lang-toggle">
                    <?php echo getCurrentLang() === 'th' ? '🌐 EN' : '🌐 TH'; ?>
                </a>
                <button class="nb-toggle" onclick="toggleTheme()" title="<?php echo t('theme_toggle'); ?>">🌙</button>
            </div>

            <div class="login-logo">
                <div class="login-logo-box">R</div>
                <h1 class="login-title"><?php echo t('login_title'); ?></h1>
                <p class="login-subtitle"><?php echo t('login_subtitle'); ?></p>
            </div>
        </div>

        <!-- Body -->
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="nb-alert nb-alert-error">
                    <span>❌</span>
                    <span><?php echo sanitizeInput($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="nb-alert nb-alert-success">
                    <span>✅</span>
                    <span><?php echo sanitizeInput($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="nb-form-group">
                    <label class="nb-label">
                        <span>👤</span>
                        <?php echo t('username'); ?>
                    </label>
                    <div class="nb-input-wrapper">
                        <input type="text" id="username" name="username" class="nb-input" required
                            value="<?php echo sanitizeInput($_POST['username'] ?? ''); ?>" autocomplete="username"
                            placeholder="<?php echo t('username_placeholder'); ?>">
                    </div>
                </div>

                <div class="nb-form-group">
                    <label class="nb-label">
                        <span>🔑</span>
                        <?php echo t('password'); ?>
                    </label>
                    <div class="nb-input-wrapper">
                        <input type="password" id="password" name="password" class="nb-input" required
                            autocomplete="current-password" placeholder="<?php echo t('password_placeholder'); ?>">
                    </div>
                </div>

                <button type="submit" class="nb-btn submit-btn">
                    🔓 <?php echo t('login_button'); ?>
                </button>
            </form>

            <?php if (isGoogleOAuthConfigured()): ?>
                <div class="nb-divider">
                    <span><?php echo t('or_login_with'); ?></span>
                </div>

                <a href="<?php echo getGoogleLoginUrl(); ?>" class="nb-google-btn google-btn">
                    <svg width="20" height="20" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"
                            fill="#4285F4" />
                        <path
                            d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.44 15.983 5.485 18 9.003 18z"
                            fill="#34A853" />
                        <path
                            d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z"
                            fill="#FBBC05" />
                        <path
                            d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.426 0 9.003 0 5.485 0 2.44 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z"
                            fill="#EA4335" />
                    </svg>
                    <span><?php echo t('sign_in_google'); ?></span>
                </a>
            <?php else: ?>
                <div class="google-setup-notice">
                    💡 <strong>Google Login ยังไม่ได้ตั้งค่า</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
</body>

</html>
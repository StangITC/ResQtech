<?php
/**
 * Authentication Functions - ResQTech System
 * ระบบยืนยันตัวตนและจัดการ Session
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

/**
 * Initialize secure session
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // ตั้งค่า session ที่ปลอดภัย
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie (หมดเมื่อปิด browser)
            'path' => '/',
            'domain' => '', // Current domain
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax' // ใช้ Lax เพื่อให้ OAuth redirect ทำงานได้
        ]);
        
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', 3600); // 1 hour
        
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getLoginUrl(string $suffix = ''): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $script = str_replace('\\', '/', $script);
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

    foreach (['/api', '/tools'] as $trim) {
        if ($dir === $trim) {
            $dir = '';
            break;
        }
        if ($dir !== '' && substr($dir, -strlen($trim)) === $trim) {
            $dir = substr($dir, 0, -strlen($trim));
            break;
        }
    }

    $base = $dir === '' ? '' : $dir;
    return $base . '/login.php' . $suffix;
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin(): void {
    initSession();
    
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        destroySession();
        header('Location: ' . getLoginUrl('?timeout=1'));
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically (every 5 minutes)
    // But NOT if we're in the middle of OAuth flow
    if (!isset($_SESSION['google_oauth_state'])) {
        if (!isset($_SESSION['regenerated']) || (time() - $_SESSION['regenerated']) > 300) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = time();
        }
    }
}

/**
 * Validate login credentials
 */
function validateLogin(string $username, string $password): bool {
    // ตรวจสอบ username
    if ($username !== ADMIN_USERNAME) {
        return false;
    }
    
    // ตรวจสอบ password ด้วย hash
    return password_verify($password, ADMIN_PASSWORD_HASH);
}

/**
 * Create login session
 */
function createLoginSession(string $username, string $method = 'traditional', array $extra = []): void {
    // Regenerate session ID เพื่อป้องกัน session fixation
    session_regenerate_id(true);
    
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['login_method'] = $method;
    $_SESSION['ip_address'] = getClientIP();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['regenerated'] = time();
    
    // Extra data (for Google login)
    foreach ($extra as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    // Generate CSRF token
    $_SESSION['csrf_token'] = generateToken();
    $_SESSION['csrf_token_time'] = time();
}

/**
 * Destroy session completely
 */
function destroySession(): void {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = generateToken();
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(?string $token): bool {
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check login attempts (brute force protection)
 */
function checkLoginAttempts(): array {
    $ip = getClientIP();
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt'] = time();
    }
    
    // Reset after lockout time
    if (isset($_SESSION['first_attempt']) && (time() - $_SESSION['first_attempt']) > LOGIN_LOCKOUT_TIME) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt'] = time();
    }
    
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $remaining = LOGIN_LOCKOUT_TIME - (time() - $_SESSION['first_attempt']);
        return [
            'allowed' => false,
            'remaining_time' => max(0, $remaining),
            'attempts' => $_SESSION['login_attempts']
        ];
    }
    
    return [
        'allowed' => true,
        'remaining_time' => 0,
        'attempts' => $_SESSION['login_attempts']
    ];
}

/**
 * Record failed login attempt
 */
function recordFailedAttempt(): void {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt'] = time();
    }
    $_SESSION['login_attempts']++;
    
    // Log failed attempt
    logMessage(LOGIN_LOG_FILE, sprintf(
        "Failed login attempt from IP: %s, User-Agent: %s",
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ));
}

/**
 * Reset login attempts
 */
function resetLoginAttempts(): void {
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['first_attempt']);
}

/**
 * Get session user info
 */
function getUsername(): string {
    return $_SESSION['username'] ?? 'Unknown';
}

function getLoginTime(): int {
    return $_SESSION['login_time'] ?? time();
}

function getLoginMethod(): string {
    return $_SESSION['login_method'] ?? 'traditional';
}

function getUserEmail(): ?string {
    return $_SESSION['email'] ?? null;
}

function getUserPicture(): ?string {
    return $_SESSION['picture'] ?? null;
}

function isGoogleLogin(): bool {
    return getLoginMethod() === 'google';
}

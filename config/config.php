<?php
/**
 * Configuration File - ResQTech System
 * ไฟล์ตั้งค่าระบบ
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

/**
 * Load .env file
 */
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Split name and value
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

/**
 * Helper to get env value
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Convert boolean strings
    if (strtolower($value) === 'true') return true;
    if (strtolower($value) === 'false') return false;
    
    return $value;
}

// ==========================================
// 1. System Settings
// ==========================================

// Timezone
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Asia/Bangkok'));

// Session Timeout (seconds) - 30 minutes
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 1800));

// Login Security
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_TIME', (int)env('LOGIN_LOCKOUT_TIME', 900)); // 15 minutes
// CSRF token lifetime (seconds)
define('CSRF_TOKEN_EXPIRE', (int)env('CSRF_TOKEN_EXPIRE', 600)); // 10 minutes

// ==========================================
// 2. Admin Credentials
// ==========================================

// Username
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));

// Password Hash (Default: 'password')
// Use tools/generate-password.php to generate new hash
define('ADMIN_PASSWORD_HASH', env('ADMIN_PASSWORD_HASH', ''));

// ==========================================
// 3. API Keys & Integration
// ==========================================

// LINE Official Account
define('LINE_CHANNEL_ACCESS_TOKEN', env('LINE_CHANNEL_ACCESS_TOKEN', ''));
define('LINE_USER_ID', env('LINE_USER_ID', ''));

// ESP32 Integration
define('ESP32_API_KEY', env('ESP32_API_KEY', ''));

// Google OAuth (Optional)
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', 'http://localhost/ResQtech/google-callback.php'));
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');

// Allowed Google users (security policy)
define('ALLOW_ANY_GOOGLE_USER', env('ALLOW_ANY_GOOGLE_USER', false));

// Parse comma-separated emails
$allowedEmailsEnv = env('ALLOWED_EMAILS', '');
define('ALLOWED_EMAILS', !empty($allowedEmailsEnv) ? array_map('trim', explode(',', $allowedEmailsEnv)) : []);

// ==========================================
// 4. Logging & Directories
// ==========================================

// Log Directory
define('LOG_DIR', __DIR__ . '/../logs/');

// Log Files
define('ERROR_LOG_FILE', LOG_DIR . 'error.log');
define('HEARTBEAT_LOG_FILE', LOG_DIR . 'heartbeat.log');
define('EMERGENCY_LOG_FILE', LOG_DIR . 'emergency.log');
define('LOGIN_LOG_FILE', LOG_DIR . 'login.log');

// ==========================================
// 5. Security Headers
// ==========================================

define('SECURITY_HEADERS', [
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self' https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:;"
]);

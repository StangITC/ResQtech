<?php
/**
 * Google OAuth Callback Handler - ResQTech System
 */

require_once __DIR__ . '/includes/init.php';

// Prevent caching for callback
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Debug: Log session info
logMessage(ERROR_LOG_FILE, sprintf(
    "Google Callback - Session ID: %s, Has OAuth State: %s",
    session_id(),
    isset($_SESSION['google_oauth_state']) ? 'yes' : 'no'
));

// ตรวจสอบ error จาก Google
if (isset($_GET['error'])) {
    header('Location: login.php?google_error=' . urlencode($_GET['error']));
    exit;
}

// ตรวจสอบ authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?google_error=no_code');
    exit;
}

// ตรวจสอบ state (CSRF protection)
if (!validateOAuthState($_GET['state'] ?? null)) {
    // ถ้า state ไม่ตรง อาจเป็นเพราะ session หมดอายุ ให้ลอง login ใหม่
    header('Location: login.php?google_error=' . urlencode('Session expired. Please try again.'));
    exit;
}

try {
    // แลกเปลี่ยน code เป็น access token
    $tokenData = exchangeCodeForToken($_GET['code']);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    // ดึงข้อมูล user
    $userInfo = getGoogleUserInfo($tokenData['access_token']);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        throw new Exception('Failed to get user info');
    }
    
    // ตรวจสอบว่า email ได้รับอนุญาต
    if (!isEmailAllowed($userInfo['email'])) {
        header('Location: login.php?google_error=email_not_allowed');
        exit;
    }
    
    // สร้าง session
    createLoginSession(
        $userInfo['name'] ?? $userInfo['email'],
        'google',
        [
            'email' => $userInfo['email'],
            'google_id' => $userInfo['id'],
            'picture' => $userInfo['picture'] ?? null
        ]
    );
    
    // Log successful login
    logMessage(LOGIN_LOG_FILE, sprintf(
        "Google Login: %s (%s) from %s",
        $userInfo['name'] ?? 'Unknown',
        $userInfo['email'],
        getClientIP()
    ));
    
    header('Location: index.php?google_login=success');
    exit;
    
} catch (Exception $e) {
    logMessage(ERROR_LOG_FILE, 'Google OAuth Error: ' . $e->getMessage());
    header('Location: login.php?google_error=' . urlencode($e->getMessage()));
    exit;
}

<?php
/**
 * Google OAuth Functions - ResQTech System
 * ฟังก์ชันสำหรับ Google Login
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

/**
 * Check if Google OAuth is configured
 */
function isGoogleOAuthConfigured(): bool
{
    return defined('GOOGLE_CLIENT_ID')
        && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'
        && defined('GOOGLE_CLIENT_SECRET')
        && GOOGLE_CLIENT_SECRET !== 'YOUR_GOOGLE_CLIENT_SECRET';
}

/**
 * Generate Google Login URL
 * State จะถูกสร้างใหม่เฉพาะเมื่อยังไม่มี หรือหมดอายุแล้ว
 */
function getGoogleLoginUrl(): string
{
    // ใช้ state เดิมถ้ายังไม่หมดอายุ (5 นาที)
    $needNewState = true;
    if (isset($_SESSION['google_oauth_state']) && isset($_SESSION['google_oauth_time'])) {
        if ((time() - $_SESSION['google_oauth_time']) < 300) {
            $needNewState = false;
        }
    }

    if ($needNewState) {
        $_SESSION['google_oauth_state'] = generateToken(16);
        $_SESSION['google_oauth_time'] = time();
    }

    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'state' => $_SESSION['google_oauth_state'],
        'prompt' => 'select_account'
    ];

    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Validate OAuth state
 */
function validateOAuthState(?string $state): bool
{
    // Debug logging
    logMessage(ERROR_LOG_FILE, sprintf(
        "OAuth State Check - Received: %s, Session: %s, Session ID: %s",
        $state ?? 'null',
        $_SESSION['google_oauth_state'] ?? 'not set',
        session_id()
    ));

    if (empty($state)) {
        logMessage(ERROR_LOG_FILE, "OAuth Error: No state parameter received");
        return false;
    }

    if (!isset($_SESSION['google_oauth_state'])) {
        logMessage(ERROR_LOG_FILE, "OAuth Error: No state in session (session may have expired)");
        return false;
    }

    // Check if state is too old (10 minutes max)
    if (isset($_SESSION['google_oauth_time']) && (time() - $_SESSION['google_oauth_time']) > 600) {
        logMessage(ERROR_LOG_FILE, "OAuth Error: State expired (older than 10 minutes)");
        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_time']);
        return false;
    }

    $valid = hash_equals($_SESSION['google_oauth_state'], $state);

    // Always clear the state after validation attempt
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_time']);

    if (!$valid) {
        logMessage(ERROR_LOG_FILE, "OAuth Error: State mismatch");
    }

    return $valid;
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken(string $code): ?array
{
    $data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * Get user info from Google
 */
function getGoogleUserInfo(string $accessToken): ?array
{
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * Check if email is allowed
 */
function isEmailAllowed(string $email): bool
{
    if (ALLOW_ANY_GOOGLE_USER) {
        return true;
    }

    $email = strtolower($email);
    $allowedEmails = array_map('strtolower', ALLOWED_EMAILS);

    return in_array($email, $allowedEmails);
}

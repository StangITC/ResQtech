<?php
/**
 * Core Functions - ResQTech System
 * ฟังก์ชันหลักของระบบ
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

/**
 * ตั้งค่า Security Headers
 */
function setSecurityHeaders(): void
{
    foreach (SECURITY_HEADERS as $header => $value) {
        header("$header: $value");
    }
}

/**
 * Sanitize input string
 */
function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL format (must be http or https)
 */
function isValidUrl(string $url): bool
{
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check scheme
    $parsed = parse_url($url);
    if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
        return false;
    }

    return true;
}

/**
 * Generate secure random token
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Log message to file
 */
function logMessage(string $file, string $message): bool
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;

    // สร้าง directory ถ้ายังไม่มี
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Log rotation: หมุนไฟล์เมื่อขนาดเกิน 5MB
    $maxSize = 5 * 1024 * 1024; // 5MB
    if (file_exists($file) && filesize($file) > $maxSize) {
        $rotateCount = 5; // เก็บไฟล์ backup 5 ไฟล์
        for ($i = $rotateCount - 1; $i >= 0; $i--) {
            $oldFile = $file . ($i === 0 ? '' : '.' . $i);
            $newFile = $file . '.' . ($i + 1);
            if (file_exists($oldFile)) {
                if ($i === $rotateCount - 1) {
                    unlink($oldFile); // ลบไฟล์เก่าสุด
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
    }

    return file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX) !== false;
}

function appendJsonLine(string $file, array $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $line = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return false;
    }

    return file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Read log file
 */
function readLogFile(string $file, int $limit = 100): array
{
    if (!file_exists($file)) {
        return [];
    }

    $logs = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($logs === false) {
        return [];
    }

    $logs = array_reverse($logs);
    $result = [];

    foreach (array_slice($logs, 0, $limit) as $log) {
        if (preg_match('/\[(.*?)\]\s*(.*)/', $log, $matches)) {
            $result[] = [
                'timestamp' => $matches[1],
                'message' => trim($matches[2])
            ];
        }
    }

    return $result;
}

/**
 * Send LINE notification
 */
function sendLineNotification(string $message): array
{
    $payload = [
        'to' => LINE_USER_ID,
        'messages' => [['type' => 'text', 'text' => $message]]
    ];

    $timeout = (int) env('LINE_HTTP_TIMEOUT', 5);
    $timeout = max(1, min($timeout, 30));
    $sslVerify = (bool) env('LINE_SSL_VERIFY', true);

    // Use file_get_contents (stream context) instead of curl to prevent crashes on some local envs
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . LINE_CHANNEL_ACCESS_TOKEN . "\r\n",
            'content' => json_encode($payload),
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => $sslVerify,
            'verify_peer_name' => $sslVerify
        ]
    ];

    $context = stream_context_create($opts);
    $t0 = microtime(true);
    $result = @file_get_contents('https://api.line.me/v2/bot/message/push', false, $context);
    $t1 = microtime(true);

    // Check headers for status code
    $httpCode = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/1\.[01] (\d+)/', $header, $matches)) {
                $httpCode = (int) $matches[1];
                break;
            }
        }
    }
    
    $errorMsg = '';
    if ($result === false) {
        $lastError = error_get_last();
        $errorMsg = $lastError['message'] ?? '';
    }

    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'response' => $result,
        'error' => $errorMsg,
        'duration_ms' => (int) round(($t1 - $t0) * 1000)
    ];
}

function registerFcmTokenForUser(string $username, string $token, string $platform = ''): array
{
    $token = trim($token);
    if ($token === '') {
        return ['success' => false, 'message' => 'Empty token'];
    }

    $platform = trim($platform);
    if ($platform === '') $platform = 'unknown';

    $data = [];
    if (file_exists(FCM_TOKENS_FILE)) {
        $data = json_decode((string) file_get_contents(FCM_TOKENS_FILE), true) ?? [];
    }
    if (!is_array($data)) $data = [];

    $userTokens = $data[$username] ?? [];
    if (!is_array($userTokens)) $userTokens = [];

    $now = time();
    $found = false;
    foreach ($userTokens as &$entry) {
        if (is_array($entry) && ($entry['token'] ?? '') === $token) {
            $entry['platform'] = $platform;
            $entry['updated_at'] = $now;
            $found = true;
            break;
        }
    }
    unset($entry);

    if (!$found) {
        $userTokens[] = [
            'token' => $token,
            'platform' => $platform,
            'created_at' => $now,
            'updated_at' => $now
        ];
    }

    $data[$username] = array_values($userTokens);

    if (!is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0755, true);
    }
    file_put_contents(FCM_TOKENS_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    return ['success' => true];
}

function getRegisteredFcmTokens(): array
{
    if (!file_exists(FCM_TOKENS_FILE)) return [];
    $data = json_decode((string) file_get_contents(FCM_TOKENS_FILE), true) ?? [];
    if (!is_array($data)) return [];

    $tokens = [];
    foreach ($data as $user => $entries) {
        if (!is_array($entries)) continue;
        foreach ($entries as $entry) {
            $t = is_array($entry) ? ($entry['token'] ?? '') : '';
            if (is_string($t) && $t !== '') $tokens[] = $t;
        }
    }

    $tokens = array_values(array_unique($tokens));
    return $tokens;
}

function fcmBase64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function loadFcmServiceAccount(): array
{
    $file = (string) FCM_SERVICE_ACCOUNT_FILE;
    $file = trim($file);
    if ($file === '') {
        return ['success' => false, 'service_account' => null, 'error' => 'FCM_SERVICE_ACCOUNT_FILE not set'];
    }

    if (!file_exists($file)) {
        return ['success' => false, 'service_account' => null, 'error' => 'Service account file not found'];
    }

    $raw = (string) file_get_contents($file);
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['success' => false, 'service_account' => null, 'error' => 'Invalid service account JSON'];
    }

    if (!isset($json['client_email'], $json['private_key']) || !is_string($json['client_email']) || !is_string($json['private_key'])) {
        return ['success' => false, 'service_account' => null, 'error' => 'Missing client_email/private_key in service account'];
    }

    return ['success' => true, 'service_account' => $json, 'error' => ''];
}

function getFcmAccessToken(array $serviceAccount): array
{
    static $cache = null;

    $clientEmail = (string) ($serviceAccount['client_email'] ?? '');
    if (is_array($cache) && ($cache['client_email'] ?? '') === $clientEmail) {
        $expiresAt = (int) ($cache['expires_at'] ?? 0);
        if ($expiresAt - 60 > time() && is_string($cache['access_token'] ?? null) && $cache['access_token'] !== '') {
            return ['success' => true, 'access_token' => $cache['access_token'], 'expires_at' => $expiresAt, 'error' => ''];
        }
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ];

    $segments = [
        fcmBase64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
        fcmBase64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES))
    ];
    $signingInput = implode('.', $segments);

    $privateKeyPem = (string) ($serviceAccount['private_key'] ?? '');
    $pkey = openssl_pkey_get_private($privateKeyPem);
    if ($pkey === false) {
        return ['success' => false, 'access_token' => '', 'expires_at' => 0, 'error' => 'Invalid private key'];
    }

    $signature = '';
    $ok = openssl_sign($signingInput, $signature, $pkey, OPENSSL_ALGO_SHA256);
    openssl_free_key($pkey);
    if (!$ok) {
        return ['success' => false, 'access_token' => '', 'expires_at' => 0, 'error' => 'Failed to sign JWT'];
    }

    $jwt = $signingInput . '.' . fcmBase64UrlEncode($signature);

    $body = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);

    $timeout = (int) env('FCM_HTTP_TIMEOUT', 8);
    $timeout = max(2, min($timeout, 30));
    $sslVerify = (bool) env('FCM_SSL_VERIFY', true);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => $sslVerify,
            'verify_peer_name' => $sslVerify
        ]
    ];

    $context = stream_context_create($opts);
    $t0 = microtime(true);
    $result = @file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    $t1 = microtime(true);

    $httpCode = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/^HTTP\/1\.[01] (\d+)/', $h, $m)) {
                $httpCode = (int) $m[1];
                break;
            }
        }
    }

    if (!is_string($result) || $result === '') {
        $lastError = error_get_last();
        $err = $lastError['message'] ?? '';
        return ['success' => false, 'access_token' => '', 'expires_at' => 0, 'error' => $err !== '' ? $err : 'Empty response', 'duration_ms' => (int) round(($t1 - $t0) * 1000), 'http_code' => $httpCode];
    }

    $json = json_decode($result, true);
    if (!is_array($json) || !isset($json['access_token'])) {
        return ['success' => false, 'access_token' => '', 'expires_at' => 0, 'error' => 'Invalid token response', 'duration_ms' => (int) round(($t1 - $t0) * 1000), 'http_code' => $httpCode, 'response' => $result];
    }

    $accessToken = (string) $json['access_token'];
    $expiresIn = isset($json['expires_in']) ? (int) $json['expires_in'] : 3600;
    $expiresAt = time() + max(60, min($expiresIn, 3600));

    $cache = [
        'client_email' => $clientEmail,
        'access_token' => $accessToken,
        'expires_at' => $expiresAt
    ];

    return ['success' => $httpCode === 200, 'access_token' => $accessToken, 'expires_at' => $expiresAt, 'error' => '', 'duration_ms' => (int) round(($t1 - $t0) * 1000), 'http_code' => $httpCode, 'response' => $result];
}

function sendFcmNotification(array $tokens, string $title, string $body, array $data = []): array
{
    $tokens = array_values(array_filter($tokens, fn($t) => is_string($t) && trim($t) !== ''));
    if (empty($tokens)) {
        return ['success' => false, 'http_code' => 0, 'response' => null, 'error' => 'No tokens', 'duration_ms' => 0];
    }

    $saLoad = loadFcmServiceAccount();
    if (!($saLoad['success'] ?? false)) {
        return ['success' => false, 'http_code' => 0, 'response' => null, 'error' => (string) ($saLoad['error'] ?? 'Service account not available'), 'duration_ms' => 0];
    }

    $serviceAccount = (array) $saLoad['service_account'];
    $projectId = trim((string) FCM_PROJECT_ID);
    if ($projectId === '') {
        $projectId = trim((string) ($serviceAccount['project_id'] ?? ''));
    }
    if ($projectId === '') {
        return ['success' => false, 'http_code' => 0, 'response' => null, 'error' => 'FCM_PROJECT_ID not set', 'duration_ms' => 0];
    }

    $tokenRes = getFcmAccessToken($serviceAccount);
    if (!($tokenRes['success'] ?? false) || !is_string($tokenRes['access_token'] ?? null) || $tokenRes['access_token'] === '') {
        return [
            'success' => false,
            'http_code' => (int) ($tokenRes['http_code'] ?? 0),
            'response' => $tokenRes['response'] ?? null,
            'error' => (string) ($tokenRes['error'] ?? 'Failed to get access token'),
            'duration_ms' => (int) ($tokenRes['duration_ms'] ?? 0)
        ];
    }
    $accessToken = (string) $tokenRes['access_token'];

    $timeout = (int) env('FCM_HTTP_TIMEOUT', 5);
    $timeout = max(1, min($timeout, 30));
    $sslVerify = (bool) env('FCM_SSL_VERIFY', true);

    $dataStringMap = [];
    foreach ($data as $k => $v) {
        $key = is_string($k) ? $k : (string) $k;
        if (is_string($v)) {
            $dataStringMap[$key] = $v;
        } elseif (is_bool($v)) {
            $dataStringMap[$key] = $v ? 'true' : 'false';
        } elseif (is_int($v) || is_float($v)) {
            $dataStringMap[$key] = (string) $v;
        } elseif ($v === null) {
            $dataStringMap[$key] = '';
        } else {
            $enc = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $dataStringMap[$key] = $enc !== false ? $enc : '';
        }
    }

    $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

    $t0 = microtime(true);
    $successCount = 0;
    $failCount = 0;
    $lastHttpCode = 0;
    $responses = [];
    $errorMsg = '';

    foreach ($tokens as $token) {
        if ((microtime(true) - $t0) > 18.0) {
            $errorMsg = $errorMsg !== '' ? $errorMsg : 'Timeout budget exceeded';
            break;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $dataStringMap,
                'android' => [
                    'priority' => 'HIGH'
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10'
                    ]
                ]
            ]
        ];

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json; charset=utf-8\r\n" .
                    "Authorization: Bearer " . $accessToken . "\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => $sslVerify,
                'verify_peer_name' => $sslVerify
            ]
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);

        $httpCode = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/1\.[01] (\d+)/', $header, $matches)) {
                    $httpCode = (int) $matches[1];
                    break;
                }
            }
        }
        $lastHttpCode = $httpCode;

        if ($httpCode === 200) {
            $successCount++;
        } else {
            $failCount++;
            if ($errorMsg === '') {
                $errorMsg = 'FCM send failed';
            }
        }

        $responses[] = [
            'token' => $token,
            'http_code' => $httpCode,
            'response' => $result
        ];
    }

    $t1 = microtime(true);
    return [
        'success' => $successCount > 0,
        'http_code' => $lastHttpCode,
        'response' => $responses,
        'error' => $errorMsg,
        'duration_ms' => (int) round(($t1 - $t0) * 1000),
        'sent' => $successCount,
        'failed' => $failCount,
        'tokens' => count($tokens)
    ];
}

/**
 * Rate limiting check
 */
function checkRateLimit(string $key, int $maxAttempts, int $timeWindow): bool
{
    $cacheFile = LOG_DIR . 'rate_limit_' . md5($key) . '.json';

    $data = [];
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    $now = time();

    // ลบ entries ที่หมดอายุ
    $data = array_filter($data, fn($timestamp) => ($now - $timestamp) < $timeWindow);

    if (count($data) >= $maxAttempts) {
        return false;
    }

    $data[] = $now;
    file_put_contents($cacheFile, json_encode($data), LOCK_EX);

    // Cleanup: ลบไฟล์ rate limit ที่หมดอายุแล้ว (10% chance เพื่อลด I/O)
    if (mt_rand(1, 10) === 1) {
        cleanupExpiredRateLimitFiles($timeWindow);
    }

    return true;
}

/**
 * Cleanup expired rate limit files
 */
function cleanupExpiredRateLimitFiles(int $maxAge = 3600): void
{
    $files = glob(LOG_DIR . 'rate_limit_*.json');
    $now = time();

    foreach ($files as $file) {
        if (($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}

/**
 * Get client IP address
 */
function getClientIP(): string
{
    // Priority: Cloudflare > Remote Addr (Most secure)
    // We trust REMOTE_ADDR the most unless we are behind Cloudflare

    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    // Only trust X-Forwarded-For if explicitly allowed (add logic here if needed)
    // For now, default to REMOTE_ADDR to prevent spoofing

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get asset URL with cache busting
 * ใช้ file modification time เป็น version
 */
function asset(string $path): string
{
    $realPath = __DIR__ . '/../' . ltrim($path, '/');
    $version = file_exists($realPath) ? filemtime($realPath) : time();
    return $path . '?v=' . $version;
}

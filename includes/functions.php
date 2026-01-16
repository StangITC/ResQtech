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

    // Use file_get_contents (stream context) instead of curl to prevent crashes on some local envs
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . LINE_CHANNEL_ACCESS_TOKEN . "\r\n",
            'content' => json_encode($payload),
            'timeout' => 5,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
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

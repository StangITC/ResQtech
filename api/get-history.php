<?php
/**
 * Get History API - ResQTech System
 * API สำหรับดึงประวัติการแจ้งเตือน
 */

require_once __DIR__ . '/../includes/init.php';

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Cookie");
    exit(0);
}

header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

// ตรวจสอบการ Login (Required for security)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$history = [];

if (file_exists(EMERGENCY_LOG_FILE)) {
    // อ่านไฟล์ Log แบบย้อนกลับ (ล่าสุดขึ้นก่อน)
    $lines = file(EMERGENCY_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!empty($lines)) {
        $reversedLines = array_reverse($lines);

        // จำกัดจำนวนที่ส่งกลับ (เช่น 50 รายการล่าสุด)
        $limit = 50;
        $count = 0;

        foreach ($reversedLines as $line) {
            if ($count >= $limit)
                break;

            // Parse: [2024-01-01 10:00:00] Emergency (button pressed) from Device1 (Main Hall) IP: ...
            if (preg_match('/\[(.*?)\]\s*Emergency(?: button pressed)? from (.*?) \((.*?)\)/', $line, $matches)) {
                $history[] = [
                    'time' => $matches[1],
                    'device' => trim($matches[2]),
                    'location' => trim($matches[3]),
                    'event' => 'Emergency Alert',
                    'status' => 'ALERT'
                ];
                $count++;
            }
        }
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $history
]);

<?php
/**
 * ESP32 Receiver API - ResQTech System
 * API สำหรับรับสัญญาณจาก ESP32
 */

require_once __DIR__ . '/../includes/init.php';

// ป้องกัน Script หยุดทำงานหาก Client ตัดการเชื่อมต่อก่อน (เพื่อให้มั่นใจว่า Log/Notification จะถูกดำเนินการจนจบ)
ignore_user_abort(true);
set_time_limit(20); // จำกัดเวลาทำงานสูงสุด 20 วินาทีป้องกัน process ค้าง

header('Content-Type: application/json');
// CORS Removed for security - ESP32 should connect directly, not via browser AJAX from other domains
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

/*
// Handle CORS preflight (Disabled)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}
*/

// ตรวจสอบ API Key
$tRecv = microtime(true);
$serverRecvMs = (int) round($tRecv * 1000);
$requestId = bin2hex(random_bytes(8));

$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true) ?: [];
$receivedKey = $_GET['key'] ?? $_POST['key'] ?? ($json['key'] ?? '');
if (!hash_equals(ESP32_API_KEY, $receivedKey)) {
    jsonResponse(['status' => 'error', 'message' => 'Invalid API Key'], 401);
}

// Rate limiting
$clientIP = getClientIP();
if (!checkRateLimit("esp32_$clientIP", 60, 60)) { // 60 requests per minute
    jsonResponse(['status' => 'error', 'message' => 'Rate limit exceeded'], 429);
}

// รับ action
$action = $_GET['action'] ?? $_POST['action'] ?? ($json['action'] ?? '');
$timestamp = date('Y-m-d H:i:s');

// รับข้อมูลอุปกรณ์ (Optional)
$deviceId = $_GET['device_id'] ?? $_POST['device_id'] ?? ($json['device_id'] ?? 'unknown_device');
$location = $_GET['location'] ?? $_POST['location'] ?? ($json['location'] ?? 'Unknown Location');
$seq = $_GET['seq'] ?? $_POST['seq'] ?? ($json['seq'] ?? null);
$seq = is_numeric($seq) ? (int) $seq : null;

// ป้องกัน XSS/Injection พื้นฐานในข้อมูลที่รับมา
$deviceId = htmlspecialchars(strip_tags($deviceId));
$location = htmlspecialchars(strip_tags($location));

switch ($action) {
    case 'heartbeat':
        // บันทึก Heartbeat พร้อมข้อมูลอุปกรณ์
        logMessage(HEARTBEAT_LOG_FILE, "Heartbeat from {$deviceId} ({$location}) IP: {$clientIP}");
        $serverTotalMs = (int) round((microtime(true) - $tRecv) * 1000);
        appendJsonLine(LOG_DIR . 'perf_events.jsonl', [
            'action' => 'heartbeat',
            'request_id' => $requestId,
            'seq' => $seq,
            'device_id' => $deviceId,
            'location' => $location,
            'ip' => $clientIP,
            'server_recv_ms' => $serverRecvMs,
            'server_total_ms' => $serverTotalMs
        ]);
        
        jsonResponse([
            'status' => 'success',
            'message' => 'Heartbeat received',
            'timestamp' => $timestamp,
            'request_id' => $requestId,
            'seq' => $seq,
            'server_recv_ms' => $serverRecvMs,
            'server_total_ms' => $serverTotalMs
        ]);
        break;
        
    case 'emergency':
        // บันทึก Emergency Event พร้อมข้อมูลอุปกรณ์
        logMessage(EMERGENCY_LOG_FILE, "Emergency button pressed from {$deviceId} ({$location}) IP: {$clientIP}");
        
        // ส่ง LINE Notification
        $message = "🚨 ฉุกเฉิน!\n" .
                   "สถานที่: {$location}\n" .
                   "อุปกรณ์: {$deviceId}\n" .
                   "เวลา: {$timestamp}";
        
        $tLineStart = microtime(true);
        $result = sendLineNotification($message);
        $tLineEnd = microtime(true);
        $lineApiMs = isset($result['duration_ms']) ? (int) $result['duration_ms'] : (int) round(($tLineEnd - $tLineStart) * 1000);
        $serverTotalMs = (int) round((microtime(true) - $tRecv) * 1000);
        appendJsonLine(LOG_DIR . 'perf_events.jsonl', [
            'action' => 'emergency',
            'request_id' => $requestId,
            'seq' => $seq,
            'device_id' => $deviceId,
            'location' => $location,
            'ip' => $clientIP,
            'server_recv_ms' => $serverRecvMs,
            'server_total_ms' => $serverTotalMs,
            'line_api_ms' => $lineApiMs,
            'line_http_code' => $result['http_code'] ?? 0,
            'line_success' => (bool) ($result['success'] ?? false)
        ]);
        
        if ($result['success']) {
            jsonResponse([
                'status' => 'success',
                'message' => 'Emergency alert sent',
                'timestamp' => $timestamp,
                'request_id' => $requestId,
                'seq' => $seq,
                'server_recv_ms' => $serverRecvMs,
                'server_total_ms' => $serverTotalMs,
                'line_api_ms' => $lineApiMs,
                'line_http_code' => $result['http_code'] ?? 0
            ]);
        } else {
            jsonResponse([
                'status' => 'error',
                'message' => 'Failed to send LINE notification',
                'line_response' => $result['response'],
                'request_id' => $requestId,
                'seq' => $seq,
                'server_recv_ms' => $serverRecvMs,
                'server_total_ms' => $serverTotalMs,
                'line_api_ms' => $lineApiMs,
                'line_http_code' => $result['http_code'] ?? 0
            ], 500);
        }
        break;
        
    default:
        jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

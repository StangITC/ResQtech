<?php
/**
 * Send Notification API - ResQTech System
 * API สำหรับส่งการแจ้งเตือน LINE
 */

require_once __DIR__ . '/../includes/init.php';

// Debug: บันทึกการเริ่มทำงาน
file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

// Clear any previous output buffer to ensure clean JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

// ต้อง POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Method not allowed\n", FILE_APPEND);
    jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Unauthorized\n", FILE_APPEND);
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

// ตรวจสอบ CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Invalid CSRF\n", FILE_APPEND);
    jsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
}

// Rate limiting
$userId = getUsername();
if (!checkRateLimit("notification_$userId", 10, 60)) { // 10 requests per minute
    file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Rate limit exceeded\n", FILE_APPEND);
    jsonResponse(['status' => 'error', 'message' => 'Rate limit exceeded'], 429);
}

// รับข้อความจากผู้ใช้
$rawMessage = $_POST['message'] ?? '';
file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Message length: " . strlen($rawMessage) . "\n", FILE_APPEND);

$rawMessage = str_replace(["\r\n", "\r"], "\n", $rawMessage);
$rawMessage = trim($rawMessage);

if ($rawMessage === '') {
    jsonResponse(['status' => 'error', 'message' => 'กรุณากรอกข้อความที่ต้องการส่ง'], 400);
}

$messageLength = function_exists('mb_strlen') ? mb_strlen($rawMessage, 'UTF-8') : strlen($rawMessage);
if ($messageLength > 1000) {
    jsonResponse(['status' => 'error', 'message' => 'ข้อความยาวเกินไป (สูงสุด 1000 ตัวอักษร)'], 400);
}

// ตัดอักขระควบคุมที่ไม่ต้องการ (ยกเว้น newline)
$rawMessage = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $rawMessage) ?? $rawMessage;

// ส่ง LINE Notification
$message = "✅ ได้รับแจ้งเตือน!\n" .
    "รายละเอียด:\n" . $rawMessage . "\n\n" .
    "ผู้ส่ง: " . getUsername() . "\n" .
    "เวลา: " . date('Y-m-d H:i:s');

file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - Sending LINE...\n", FILE_APPEND);

$result = sendLineNotification($message);

file_put_contents(__DIR__ . '/../logs/debug_noti.log', date('Y-m-d H:i:s') . " - LINE Result: " . json_encode($result) . "\n", FILE_APPEND);

if ($result['success']) {
    jsonResponse(['status' => 'success', 'message' => 'ส่งการแจ้งเตือนสำเร็จ!']);
} else {
    logMessage(ERROR_LOG_FILE, 'LINE notification failed: ' . $result['error']);
    jsonResponse(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $result['error']], 500);
}

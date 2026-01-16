<?php
/**
 * Mobile Login API - ResQTech System
 * API สำหรับเข้าสู่ระบบผ่าน Mobile App
 */

require_once __DIR__ . '/../includes/init.php';

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Cookie");
    header("Access-Control-Allow-Credentials: true");
    exit(0);
}

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// รับข้อมูล JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit;
}

// ตรวจสอบ Brute Force
$loginCheck = checkLoginAttempts();
if (!$loginCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many login attempts. Please try again later.',
        'wait_seconds' => $loginCheck['remaining_time']
    ]);
    exit;
}

// ตรวจสอบรหัสผ่าน
if (validateLogin($username, $password)) {
    // Login สำเร็จ
    createLoginSession($username, 'mobile');
    resetLoginAttempts();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'username' => $username,
            'session_id' => session_id()
        ]
    ]);
} else {
    // Login ผิดพลาด
    recordFailedAttempt();
    
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid username or password'
    ]);
}

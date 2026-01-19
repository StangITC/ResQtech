<?php
require_once __DIR__ . '/../includes/init.php';

// Handle CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$originHost = $origin ? (parse_url($origin, PHP_URL_HOST) ?? '') : '';
$allowedHosts = ['localhost', '127.0.0.1'];
// Allow LAN IPs
if ($originHost && strpos($originHost, '192.168.') === 0) {
    $allowedHosts[] = $originHost;
}

$corsOrigin = in_array($originHost, $allowedHosts, true) ? $origin : '';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($corsOrigin) {
        header("Access-Control-Allow-Origin: $corsOrigin");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

if ($corsOrigin) {
    header("Access-Control-Allow-Origin: $corsOrigin");
    header("Access-Control-Allow-Credentials: true");
}

if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true) ?: [];

$token = $json['token'] ?? '';
$platform = $json['platform'] ?? '';

if (!is_string($token) || trim($token) === '') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid token'], 400);
}

$ok = registerFcmTokenForUser(getUsername(), $token, is_string($platform) ? $platform : '');

if (!($ok['success'] ?? false)) {
    jsonResponse(['status' => 'error', 'message' => $ok['message'] ?? 'Failed'], 500);
}

jsonResponse(['status' => 'success']);


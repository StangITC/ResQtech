<?php
/**
 * ESP32 Status Check API - ResQTech System
 * API สำหรับตรวจสอบสถานะ ESP32
 */

require_once __DIR__ . '/../includes/init.php';

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    // Allow specific origins only (add your allowed origins here)
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

// ตรวจสอบ Heartbeat แบบ Multi-device
$devices = []; // เก็บสถานะล่าสุดของแต่ละอุปกรณ์ Key = Device ID

if (file_exists(HEARTBEAT_LOG_FILE)) {
    // อ่านไฟล์ทั้งหมด (ควรจำกัดจำนวนบรรทัดถ้านานไป แต่เบื้องต้นอ่านหมดเพื่อความชัวร์)
    $heartbeats = file(HEARTBEAT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!empty($heartbeats)) {
        // วนลูปจาก "ล่าสุด" ไป "เก่าสุด" (Reverse) เพื่อให้เจอข้อมูลล่าสุดเร็วขึ้น
        $reversedLogs = array_reverse($heartbeats);

        foreach ($reversedLogs as $line) {
            // Parse: [Time] Heartbeat from {device_id} ({location}) IP: {ip}
            if (preg_match('/\[(.*?)\] Heartbeat from (.*?) \((.*?)\)/', $line, $matches)) {
                $timeStr = $matches[1];
                $deviceId = trim($matches[2]);
                $location = trim($matches[3]);
                $timestamp = strtotime($timeStr);

                // ถ้ายังไม่เคยเจอ Device นี้ (เพราะเราวนจากล่าสุด) ให้บันทึกไว้
                if (!isset($devices[$deviceId])) {
                    $secondsAgo = time() - $timestamp;
                    $devices[$deviceId] = [
                        'id' => $deviceId,
                        'location' => $location,
                        'last_seen' => $timeStr,
                        'seconds_ago' => $secondsAgo,
                        'is_online' => ($secondsAgo <= 65) // เพิ่ม Timeout เป็น 65s เผื่อ Network Delay
                    ];
                }
            } elseif (preg_match('/\[(.*?)\] Heartbeat from/', $line, $matches)) {
                // Backward compatibility for old log format (unknown device)
                if (!isset($devices['unknown'])) {
                    $timeStr = $matches[1];
                    $timestamp = strtotime($timeStr);
                    $secondsAgo = time() - $timestamp;
                    $devices['unknown'] = [
                        'id' => 'unknown',
                        'location' => 'Unknown Location',
                        'last_seen' => $timeStr,
                        'seconds_ago' => $secondsAgo,
                        'is_online' => ($secondsAgo <= 65)
                    ];
                }
            }
        }
    }
}

// สรุปสถานะภาพรวม (ถ้ามีตัวใดตัวหนึ่ง Online ถือว่าระบบ Connected)
$isConnected = false;
$lastHeartbeatTime = null;
$heartbeatSecondsAgo = 0;
$mainDeviceInfo = null;

if (!empty($devices)) {
    // เรียงลำดับตามความใหม่
    usort($devices, function ($a, $b) {
        return $a['seconds_ago'] <=> $b['seconds_ago'];
    });

    $mostRecent = $devices[0];
    $isConnected = $mostRecent['is_online'];
    $lastHeartbeatTime = $mostRecent['last_seen'];
    $heartbeatSecondsAgo = $mostRecent['seconds_ago'];
    $mainDeviceInfo = [
        'id' => $mostRecent['id'],
        'location' => $mostRecent['location']
    ];
}

// Compute uptime percentage over last 60 minutes
$uptimeLast60m = 0;
if (!empty($devices)) {
    $now = time();
    $windowSeconds = 3600; // 60 minutes
    $onlineMinutes = 0;

    // Check how many minutes in the last hour had at least one online device
    foreach ($devices as $device) {
        if ($device['seconds_ago'] <= 60) {
            // Device was seen within the last minute
            $onlineMinutes = max($onlineMinutes, 1);
        }
    }

    // Simple estimation: if any device currently online, assume good uptime
    $hasOnlineDevice = array_filter($devices, fn($d) => $d['is_online']);
    if (!empty($hasOnlineDevice)) {
        $uptimeLast60m = 100; // Currently online = 100%
    } else {
        $uptimeLast60m = 0; // Currently offline = 0%
    }
}

// ตรวจสอบ Emergency Events
$response = [
    'status' => 'success',
    'last_event' => null,
    'is_recent' => false,
    'is_connected' => $isConnected,
    'last_heartbeat' => $lastHeartbeatTime,
    'heartbeat_seconds_ago' => $heartbeatSecondsAgo,
    'device_info' => $mainDeviceInfo, // Add Heartbeat Device Info
    'devices_list' => array_values($devices), // List of all devices
    'seconds_ago' => null,
    'total_events' => 0,
    'timestamp' => time(),
    'uptime_percentage_last_60m' => isset($uptimeLast60m) ? $uptimeLast60m : 0
];

if (file_exists(EMERGENCY_LOG_FILE)) {
    $logs = file(EMERGENCY_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!empty($logs)) {
        $lastLog = end($logs);

        if (preg_match('/\[(.*?)\]/', $lastLog, $matches)) {
            $lastTime = $matches[1];
            $logTimestamp = strtotime($lastTime);
            $secondsAgo = time() - $logTimestamp;

            $response['last_event'] = $lastTime;
            $response['is_recent'] = ($secondsAgo <= 10);
            $response['seconds_ago'] = $secondsAgo;

            // Parse Emergency Device Info
            if (preg_match('/from (.*?) \((.*?)\) IP:/', $lastLog, $devMatches)) {
                $response['emergency_device'] = [
                    'id' => trim($devMatches[1]),
                    'location' => trim($devMatches[2])
                ];
            }
        }

        $response['total_events'] = count($logs);
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

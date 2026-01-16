<?php
/**
 * Real-time Stream API - ResQTech System
 * Server-Sent Events (SSE) for < 1s latency
 */

require_once __DIR__ . '/../includes/init.php';

// Debug logging disabled for production
// file_put_contents(__DIR__ . '/../logs/stream_debug.log', date('Y-m-d H:i:s') . " - Stream request received\n", FILE_APPEND);

// ป้องกัน Timeout
set_time_limit(0); // Unlimited execution time for SSE
ignore_user_abort(true);

// ปิดการ buffering เพื่อให้ส่งข้อมูลได้ทันที
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) {
    ob_end_flush();
}
ob_implicit_flush(1);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // For Nginx
header('Content-Encoding: none'); // Prevent Gzip

// Disable gzip/compression output
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Clear all buffers
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(1);

// ส่ง Retry Interval ให้ Client (3 วินาที)
echo "retry: 3000\n\n";

// Padding to force browser to process the stream immediately (bypass buffering)
// 4KB padding to bypass most antivirus/proxy buffers
echo ":" . str_repeat(" ", 4096) . "\n\n";
flush();

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    // Debug disabled
    // file_put_contents(__DIR__ . '/../logs/stream_debug.log', date('Y-m-d H:i:s') . " - Unauthorized access\n", FILE_APPEND);

    echo "event: error\n";
    echo "data: " . json_encode(['message' => 'Unauthorized']) . "\n\n";
    flush();
    exit;
}

// file_put_contents(__DIR__ . '/../logs/stream_debug.log', date('Y-m-d H:i:s') . " - Stream started for user: " . ($_SESSION['username'] ?? 'unknown') . "\n", FILE_APPEND);

// Close session to prevent locking (Critical for SSE performance)
session_write_close();

// Initial Ping to confirm connection immediately
echo ": connected\n\n";
echo ":" . str_repeat(" ", 1024) . "\n\n"; // More padding
flush();

// ฟังก์ชันสำหรับอ่านไฟล์บรรทัดสุดท้าย
function getLastLine($file)
{
    if (!file_exists($file))
        return null;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return !empty($lines) ? end($lines) : null;
}

$lastHeartbeatRaw = null;
$lastEmergencyRaw = null;

// Loop เพื่อส่งข้อมูล (Max 30s เพื่อไม่ให้ Process ค้างนานเกินไป)
$startTime = time();
while (true) {
    // 1. ตรวจสอบ Heartbeat
    $currentHeartbeat = getLastLine(HEARTBEAT_LOG_FILE);
    if ($currentHeartbeat !== $lastHeartbeatRaw) {
        $lastHeartbeatRaw = $currentHeartbeat;

        $isConnected = false;
        $heartbeatSecondsAgo = 0;
        $hbTimestampStr = '';

        if ($currentHeartbeat && preg_match('/\[(.*?)\]/', $currentHeartbeat, $matches)) {
            $hbTimestampStr = $matches[1];
            $hbTimestamp = strtotime($hbTimestampStr);
            $heartbeatSecondsAgo = time() - $hbTimestamp;
            $isConnected = ($heartbeatSecondsAgo <= 30);

            // ส่งข้อมูล Heartbeat
            $data = [
                'type' => 'heartbeat',
                'is_connected' => $isConnected,
                'last_heartbeat' => $hbTimestampStr,
                'seconds_ago' => $heartbeatSecondsAgo,
                'timestamp' => time()
            ];

            echo "event: heartbeat\n";
            echo "data: " . json_encode($data) . "\n\n";
            flush();
        }
    }

    // 2. ตรวจสอบ Emergency
    $currentEmergency = getLastLine(EMERGENCY_LOG_FILE);
    if ($currentEmergency !== $lastEmergencyRaw) {
        $lastEmergencyRaw = $currentEmergency;

        if ($currentEmergency && preg_match('/\[(.*?)\]/', $currentEmergency, $matches)) {
            $emTimestampStr = $matches[1];
            $emTimestamp = strtotime($emTimestampStr);
            $secondsAgo = time() - $emTimestamp;

            // แจ้งเตือนถ้าเหตุการณ์เพิ่งเกิด (ภายใน 10 วินาที)
            $isRecent = ($secondsAgo <= 10);

            $data = [
                'type' => 'emergency',
                'last_event' => $emTimestampStr,
                'is_recent' => $isRecent,
                'seconds_ago' => $secondsAgo,
                'timestamp' => time()
            ];

            echo "event: emergency\n";
            echo "data: " . json_encode($data) . "\n\n";
            flush();
        }
    }

    // ส่ง Ping ทุก 5 วินาทีเพื่อรักษา Connection
    if ((time() - $startTime) % 5 === 0) {
        echo "event: ping\n";
        echo "data: " . time() . "\n\n";
        flush();
    }

    // หยุดหลังจาก 15 วินาที (ลดเวลาลงเพื่อเลี่ยง Connection Timeout/Abort บนบาง Environment)
    if ((time() - $startTime) > 15) {
        // Send a closing comment to ensure last packet is sent
        echo ": closing\n\n";
        flush();
        break;
    }

    // รอ 0.5 วินาที (เร็วกว่า Polling 1s เดิมถึง 2 เท่า)
    usleep(500000);
}

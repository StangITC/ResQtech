<?php
/**
 * Dashboard API - ResQTech System
 * API สำหรับ Dashboard Real-time Updates
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

/**
 * Get emergency logs
 */
function getEmergencyLogs(int $limit = 1000): array {
    return readLogFile(EMERGENCY_LOG_FILE, $limit);
}

/**
 * Get heartbeat logs
 */
function getHeartbeatLogs(int $limit = 1000): array {
    if (!file_exists(HEARTBEAT_LOG_FILE)) {
        return [];
    }
    
    $logs = file(HEARTBEAT_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_slice($logs, -$limit);
}

/**
 * Calculate statistics
 */
function getStatistics(): array {
    $emergencyLogs = getEmergencyLogs();
    $heartbeatLogs = getHeartbeatLogs();
    
    $now = time();
    $today = date('Y-m-d');
    
    $stats = [
        'total_events' => count($emergencyLogs),
        'today_events' => 0,
        'last_24h_events' => 0,
        'last_7d_events' => 0,
        'total_heartbeats' => count($heartbeatLogs),
        'uptime_percentage' => 0,
        'last_event' => null,
        'last_heartbeat' => null,
        'is_online' => false,
        'hourly_stats' => array_fill(0, 24, 0),
        'daily_stats' => []
    ];
    
    // นับเหตุการณ์
    foreach ($emergencyLogs as $log) {
        $timestamp = strtotime($log['timestamp']);
        $logDate = date('Y-m-d', $timestamp);
        $logHour = (int)date('H', $timestamp);
        
        if ($logDate === $today) {
            $stats['today_events']++;
            $stats['hourly_stats'][$logHour]++;
        }
        
        if (($now - $timestamp) <= 86400) {
            $stats['last_24h_events']++;
        }
        
        if (($now - $timestamp) <= 604800) {
            $stats['last_7d_events']++;
            
            if (!isset($stats['daily_stats'][$logDate])) {
                $stats['daily_stats'][$logDate] = 0;
            }
            $stats['daily_stats'][$logDate]++;
        }
        
        if (!$stats['last_event']) {
            $stats['last_event'] = $log['timestamp'];
        }
    }
    
    // เติมวันที่ขาดหาย
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        if (!isset($stats['daily_stats'][$date])) {
            $stats['daily_stats'][$date] = 0;
        }
    }
    ksort($stats['daily_stats']);
    
    // ตรวจสอบ heartbeat
    if (!empty($heartbeatLogs)) {
        $lastHeartbeat = end($heartbeatLogs);
        if (preg_match('/\[(.*?)\]/', $lastHeartbeat, $matches)) {
            $stats['last_heartbeat'] = $matches[1];
            $hbTime = strtotime($matches[1]);
            $stats['is_online'] = ($now - $hbTime) <= 30;

            // Compute uptime percentage over last 60 minutes:
            // Count distinct minutes that have at least one heartbeat
            $windowSeconds = 3600;
            $minutesSet = [];
            $recentCount = 0;
            foreach ($heartbeatLogs as $line) {
                if (preg_match('/\[(.*?)\]/', $line, $m)) {
                    $t = strtotime($m[1]);
                    if (($now - $t) <= $windowSeconds) {
                        $recentCount++;
                        $minuteKey = date('YmdHi', $t);
                        $minutesSet[$minuteKey] = true;
                    }
                }
            }
            $presentMinutes = count($minutesSet);
            $stats['uptime_percentage'] = round(($presentMinutes / 60) * 100, 2);
            $stats['total_heartbeats_last_60m'] = $recentCount;
        }
    }
    
    return $stats;
}

$stats = getStatistics();
$stats['recent_events'] = array_slice(getEmergencyLogs(10), 0, 10);

echo json_encode($stats, JSON_UNESCAPED_UNICODE);

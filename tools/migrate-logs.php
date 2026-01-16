<?php
/**
 * Log Migration Tool - ResQTech System
 * ย้าย log files จาก root ไปยัง logs/ directory
 * 
 * วิธีใช้: php tools/migrate-logs.php
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

$rootDir = dirname(__DIR__);
$logsDir = $rootDir . '/logs';

// สร้าง logs directory ถ้ายังไม่มี
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "Created logs directory\n";
}

// รายการไฟล์ที่ต้องย้าย
$filesToMigrate = [
    'emergency_log.txt' => 'emergency.log',
    'heartbeat_log.txt' => 'heartbeat.log',
    'login_log.txt' => 'login.log'
];

foreach ($filesToMigrate as $oldFile => $newFile) {
    $oldPath = $rootDir . '/' . $oldFile;
    $newPath = $logsDir . '/' . $newFile;
    
    if (file_exists($oldPath)) {
        // ถ้าไฟล์ใหม่มีอยู่แล้ว ให้ append
        if (file_exists($newPath)) {
            $content = file_get_contents($oldPath);
            file_put_contents($newPath, $content, FILE_APPEND | LOCK_EX);
            echo "Appended $oldFile to $newFile\n";
        } else {
            // ย้ายไฟล์
            rename($oldPath, $newPath);
            echo "Moved $oldFile to $newFile\n";
        }
    } else {
        echo "Skipped $oldFile (not found)\n";
    }
}

echo "\nMigration complete!\n";

<?php
/**
 * Password Hash Generator - ResQTech System
 * ใช้สำหรับสร้าง password hash ที่ปลอดภัย
 * 
 * วิธีใช้: php tools/generate-password.php your_password
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

if ($argc < 2) {
    echo "Usage: php generate-password.php <password>\n";
    echo "Example: php generate-password.php mySecurePassword123\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\n";
echo "Copy this hash to config/config.php:\n";
echo "define('ADMIN_PASSWORD_HASH', '$hash');\n";
echo "\n";

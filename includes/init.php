<?php
/**
 * Application Initialization - ResQTech System
 * ไฟล์เริ่มต้นระบบ
 */

// Define application constant
define('RESQTECH_APP', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Set error log file
ini_set('error_log', ERROR_LOG_FILE);

// Create log directory if not exists
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Load core functions (order matters!)
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Initialize session BEFORE loading lang.php (which uses $_SESSION)
initSession();

// Set security headers
setSecurityHeaders();

// Prevent caching (Real-time Requirement)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Load language and OAuth (these use session)
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/google-oauth.php';
require_once __DIR__ . '/navigation.php';

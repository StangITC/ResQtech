<?php
/**
 * Language System - ResQTech
 * ระบบจัดการภาษา
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

// ตั้งค่าภาษาเริ่มต้น
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'th';
}

// เปลี่ยนภาษาเมื่อมีการร้องขอ
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
}

$current_lang = $_SESSION['language'];

// ข้อความในภาษาต่างๆ
$translations = [
    'th' => [
        // หน้า Login
        'login_title' => 'เข้าสู่ระบบ',
        'login_subtitle' => 'ResQTech Notification System',
        'username' => 'ชื่อผู้ใช้',
        'password' => 'รหัสผ่าน',
        'username_placeholder' => 'กรอกชื่อผู้ใช้',
        'password_placeholder' => 'กรอกรหัสผ่าน',
        'login_button' => 'เข้าสู่ระบบ',
        'security_title' => 'ระบบความปลอดภัย',
        'security_session' => 'Session Timeout',
        'security_brute' => 'Brute Force Protection',
        'security_csrf' => 'CSRF Protection',
        'security_headers' => 'Secure Headers',
        'logout_success' => 'ออกจากระบบเรียบร้อยแล้ว',
        'session_timeout' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
        'login_failed' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
        'too_many_attempts' => 'คุณพยายาม Login ผิดหลายครั้ง กรุณารอ {minutes} นาที',
        'or_login_with' => 'หรือเข้าสู่ระบบด้วย',
        'sign_in_google' => 'เข้าสู่ระบบด้วย Google',
        
        // หน้า Index
        'system_title' => 'ResQTech System',
        'system_subtitle' => 'Smart Notification & Management Platform',
        'hello_user' => 'สวัสดี, {username}!',
        'login_time' => 'เข้าสู่ระบบเมื่อ: {time}',
        'logout' => 'ออกจากระบบ',
        'send_notification' => 'ส่งการแจ้งเตือน',
        'notification_title' => 'ส่งการแจ้งเตือน',
        'notification_desc' => 'กดปุ่มด้านล่างเพื่อส่งการแจ้งเตือนไปยัง LINE Official Account ทันที',
        'notification_message_label' => 'ข้อความ/รายละเอียด',
        'notification_message_placeholder' => 'พิมพ์รายละเอียดที่ต้องการส่งไปยัง LINE...',
        'send_button' => 'ส่งการแจ้งเตือน',
        'sending' => 'กำลังส่ง...',
        'send_success' => 'ส่งการแจ้งเตือนสำเร็จ!',
        'send_error' => 'เกิดข้อผิดพลาด! กรุณาตรวจสอบ Log',
        'connection_error' => 'ไม่สามารถเชื่อมต่อกับ Server ได้',
        'loading_clock' => 'กำลังโหลด...',
        
        // ทั่วไป
        'theme_toggle' => 'เปลี่ยนธีม',
        'language' => 'ภาษา',
        'thai' => 'ไทย',
        'english' => 'English'
    ],
    
    'en' => [
        // Login Page
        'login_title' => 'Login',
        'login_subtitle' => 'ResQTech Notification System',
        'username' => 'Username',
        'password' => 'Password',
        'username_placeholder' => 'Enter username',
        'password_placeholder' => 'Enter password',
        'login_button' => 'Login',
        'security_title' => 'Security System',
        'security_session' => 'Session Timeout',
        'security_brute' => 'Brute Force Protection',
        'security_csrf' => 'CSRF Protection',
        'security_headers' => 'Secure Headers',
        'logout_success' => 'Successfully logged out',
        'session_timeout' => 'Session expired. Please login again',
        'login_failed' => 'Invalid username or password',
        'too_many_attempts' => 'Too many login attempts. Please wait {minutes} minutes',
        'or_login_with' => 'Or sign in with',
        'sign_in_google' => 'Sign in with Google',
        
        // Index Page
        'system_title' => 'ResQTech System',
        'system_subtitle' => 'Smart Notification & Management Platform',
        'hello_user' => 'Hello, {username}!',
        'login_time' => 'Logged in at: {time}',
        'logout' => 'Logout',
        'send_notification' => 'Send Notification',
        'notification_title' => 'Send Notification',
        'notification_desc' => 'Click the button below to send notification to LINE Official Account immediately',
        'notification_message_label' => 'Message / Details',
        'notification_message_placeholder' => 'Type the details to send to LINE...',
        'send_button' => 'Send Notification',
        'sending' => 'Sending...',
        'send_success' => 'Notification sent successfully!',
        'send_error' => 'An error occurred! Please check the log',
        'connection_error' => 'Unable to connect to server',
        'loading_clock' => 'Loading...',
        
        // General
        'theme_toggle' => 'Toggle Theme',
        'language' => 'Language',
        'thai' => 'ไทย',
        'english' => 'English'
    ]
];

/**
 * Translate text
 */
function t(string $key, array $replacements = []): string {
    global $translations, $current_lang;
    
    $text = $translations[$current_lang][$key] ?? $key;
    
    foreach ($replacements as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }
    
    return $text;
}

/**
 * Get language switch URL
 */
function getLangUrl(string $lang): string {
    $currentUrl = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($currentUrl);
    
    $params = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $params);
    }
    
    $params['lang'] = $lang;
    
    return $parsedUrl['path'] . '?' . http_build_query($params);
}

/**
 * Get current language
 */
function getCurrentLang(): string {
    global $current_lang;
    return $current_lang;
}

<?php
/**
 * Logout Handler - ResQTech System
 */

require_once __DIR__ . '/includes/init.php';

// Log logout
if (isLoggedIn()) {
    logMessage(LOGIN_LOG_FILE, "Logout: " . getUsername() . " from " . getClientIP());
}

// Destroy session
destroySession();

// Redirect to login
header('Location: login.php?logout=1');
exit;

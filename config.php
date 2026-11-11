<?php
/**
 * Megan's Jewelry POS - Configuration File
 * 
 * SECURITY NOTE: In production, move this file outside the web root
 * or protect it with .htaccess
 */

// Prevent direct access
if (!defined('APP_INITIALIZED')) {
    define('APP_INITIALIZED', true);
}

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', 'sql306.infinityfree.com');
define('DB_NAME', 'if0_42821359_jewelry_db');
define('DB_USER', 'if0_42821359');
define('DB_PASS', 'zWylINrUHX0Q');
define('DB_CHARSET', 'utf8mb4');

// ==================== APPLICATION SETTINGS ====================
define('APP_NAME', "Megan's Jewelry");
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Manila');
define('APP_URL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// ==================== SESSION SETTINGS ====================
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_REGENERATE_INTERVAL', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCK_DURATION', 900); // 15 minutes

// ==================== SECURITY SETTINGS ====================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// ==================== FILE UPLOAD SETTINGS ====================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);

// ==================== PAGINATION SETTINGS ====================
define('ITEMS_PER_PAGE', 20);

// ==================== ENVIRONMENT ====================
// Set to 'production' when live
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// ==================== ERROR REPORTING ====================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    
    // Create logs directory if it doesn't exist
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    ini_set('error_log', $log_dir . '/error.log');
}

// ==================== TIMEZONE ====================
date_default_timezone_set(APP_TIMEZONE);

// ==================== CURRENCY SETTINGS ====================
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');
define('DECIMAL_PLACES', 2);

// ==================== EMAIL SETTINGS (for future use) ====================
define('MAIL_FROM_EMAIL', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_SUPPORT_EMAIL', 'support@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// ==================== GOLD PRICES (Auto-update recommended) ====================
// Last updated: 2025-11-24
define('GOLD_PRICES', [
    '24k' => 7680,  // ₱ per gram
    '22k' => 7040,
    '21k' => 6720,
    '18k' => 5736,
    '14k' => 4480,
    '10k' => 3200,
]);

// ==================== HELPER FUNCTIONS ====================

/**
 * Get configuration value
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if in development mode
 */
function is_debug() {
    return DEBUG_MODE;
}

/**
 * Log message to file
 */
function log_message($message, $type = 'info') {
    if (!DEBUG_MODE) return;
    
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}
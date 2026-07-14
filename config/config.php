<?php
/**
 * DigitalDine - Configuration File
 * Central configuration for the restaurant website
 */

// Site Configuration
define('SITE_NAME', 'DigitalDine');
define('SITE_URL', 'http://localhost/restaurant_project');
define('ADMIN_URL', SITE_URL . '/admin');
define('CUSTOMER_URL', SITE_URL . '/customer');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_db');
define('DB_CHARSET', 'utf8mb4');

// DateTime Configuration
define('TIMEZONE', 'UTC');
date_default_timezone_set(TIMEZONE);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 6);

// Restaurant Settings
define('RESTAURANT_NAME', 'DigitalDine Restaurant');
define('RESTAURANT_EMAIL', 'info@DigitalDine.com');
define('RESTAURANT_PHONE', '(555) 123-4567');
define('RESTAURANT_ADDRESS', '123 Food Street, City, State 12345');

// Operating Hours (24-hour format)
define('OPERATING_HOURS', [
    'Monday' => ['11:00', '22:00'],
    'Tuesday' => ['11:00', '22:00'],
    'Wednesday' => ['11:00', '22:00'],
    'Thursday' => ['11:00', '22:00'],
    'Friday' => ['11:00', '23:00'],
    'Saturday' => ['11:00', '23:00'],
    'Sunday' => ['12:00', '21:00'],
]);

// Order Settings
define('TAX_RATE', 0.08); // 8% tax
define('RESERVATION_MAX_GUESTS', 20);
define('RESERVATION_NOTICE_HOURS', 1); // Reservation must be at least 1 hour in advance

// Pagination
define('ITEMS_PER_PAGE', 10);

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_DIR', __DIR__ . '/../assets/images/');

// Email Settings (for future implementation)
define('MAIL_FROM', RESTAURANT_EMAIL);
define('MAIL_FROM_NAME', RESTAURANT_NAME);

// Feature Flags
define('ENABLE_RESERVATIONS', true);
define('ENABLE_LOYALTY_PROGRAM', false);
define('ENABLE_EMAIL_NOTIFICATIONS', false);

// Debug Mode (set to false in production)
define('DEBUG_MODE', true);

// Error Reporting
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Log file location
define('LOG_FILE', __DIR__ . '/../logs/app.log');

/**
 * Helper function to log messages
 */
function log_message($level, $message) {
    if (!is_dir(dirname(LOG_FILE))) {
        @mkdir(dirname(LOG_FILE), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $level: $message\n";
    @file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
}

/**
 * Helper function to get restaurant info
 */
function get_restaurant_info() {
    return [
        'name' => RESTAURANT_NAME,
        'email' => RESTAURANT_EMAIL,
        'phone' => RESTAURANT_PHONE,
        'address' => RESTAURANT_ADDRESS,
    ];
}

/**
 * Helper function to get operating hours
 */
function get_operating_hours() {
    return OPERATING_HOURS;
}

/**
 * Helper function to check if restaurant is open
 */
function is_restaurant_open() {
    $day = date('l'); // e.g., "Monday"
    $current_time = date('H:i');

    if (!isset(OPERATING_HOURS[$day])) {
        return false;
    }

    $hours = OPERATING_HOURS[$day];
    return $current_time >= $hours[0] && $current_time <= $hours[1];
}
?>

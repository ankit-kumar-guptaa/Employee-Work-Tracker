<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_tracker');

// Application configuration
define('APP_NAME', 'Employee Daily Tracker');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/employee-work-tracker');

// Admin credentials (hardcoded for simplicity)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Session lifetime (30 minutes)
define('SESSION_LIFETIME', 1800);

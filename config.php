<?php
// 1. SECURE SESSION SETTINGS
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

// 2. START SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. DATABASE CREDENTIALS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'activeance_db');

// 4. ERROR REPORTING (CRITICAL: Turn off display_errors for production)
ini_set('display_errors', 0); // Prevents DB credentials leaking
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-errors.log');
error_reporting(E_ALL);

// 5. CORS
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'https://yourdomain.com' // UPDATE THIS to your actual live domain
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 6. PREFLIGHT
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}
?>
<?php
// config/init.php

// 1. Load Environment Variables
require_once __DIR__ . '/Env.php';
Env::load(__DIR__ . '/../.env');

// 2. Database Connection
require_once __DIR__ . '/database.php';
global $pdo;

// 3. Set the session save path to a folder inside your project
//    (this folder is guaranteed to be writable by the web server)
$sessionPath = __DIR__ . '/../sessions';   // C:/xampp/htdocs/quiz_generator/sessions

// Create the folder if it doesn’t exist
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

// *** THIS IS THE MISSING PIECE ***
// Force PHP to use our custom session directory
ini_set('session.save_path', $sessionPath);
session_save_path($sessionPath);

// 4. Start Session SECURELY
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly'  => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// 5. CSRF secret from .env
define('CSRF_SECRET', getenv('CSRF_SECRET') ?: 'fallback-secret-change-in-env');

// 6. Timezone and Error Reporting
date_default_timezone_set('Asia/Manila');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
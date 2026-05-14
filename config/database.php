<?php
require_once __DIR__ . '/Env.php';
Env::load(__DIR__ . '/../.env');

$host = Env::get('DB_HOST', 'casestudy');
$port = Env::get('DB_PORT', '3306');
$db   = Env::get('DB_NAME', 'quiz_generator_db');
$user = Env::get('DB_USER', 'root');
$pass = Env::get('DB_PASS', '');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
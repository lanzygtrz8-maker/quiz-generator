<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/src/Auth.php';
Auth::logout();
header("Location: login.php");
exit;
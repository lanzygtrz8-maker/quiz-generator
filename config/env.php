<?php
class Env {
    public static function load($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Environment file not found at: $filePath");
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }

    public static function get($key, $default = null) {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
<?php
function generateCsrfToken() {
    $nonce = bin2hex(random_bytes(16));
    $expires = time() + 3600;
    $payload = $nonce . ':' . $expires;
    $signature = hash_hmac('sha256', $payload, CSRF_SECRET);
    return base64_encode($payload . ':' . $signature);
}

function validateCsrfToken($token) {
    if (empty($token)) {
        http_response_code(403);
        die("CSRF token validation failed. Missing token.");
    }
    $decoded = base64_decode($token);
    if ($decoded === false) {
        http_response_code(403);
        die("CSRF token validation failed. Invalid format.");
    }
    $parts = explode(':', $decoded);
    if (count($parts) !== 3) {
        http_response_code(403);
        die("CSRF token validation failed. Malformed token.");
    }
    list($nonce, $expires, $signature) = $parts;
    if ((int)$expires < time()) {
        http_response_code(403);
        die("CSRF token validation failed. Token expired. Please reload the page.");
    }
    $payload = $nonce . ':' . $expires;
    $expected = hash_hmac('sha256', $payload, CSRF_SECRET);
    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        die("CSRF token validation failed. Token mismatch.");
    }
    return true;
}
<?php
ob_start();

/**
 * MAIN FRONT CONTROLLER
 * - Routes /api/* to PHP backend
 * - Serves Angular static files
 * - Handles SPA fallback correctly
 */

// Normalize request path (remove query string)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// ---------------- API ROUTING ----------------
if (strpos($path, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    exit;
}

// ---------------- ANGULAR DIST PATH ----------------
$distPath = __DIR__ . '/frontend/dist/word-tracker/browser';

// ---------------- STATIC FILES ----------------
$filePath = realpath($distPath . $path);

if (
    $path !== '/' &&
    $filePath &&
    strpos($filePath, realpath($distPath)) === 0 &&
    is_file($filePath)
) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'html' => 'text/html',
        'json' => 'application/json',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2'
    ];

    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000');

    readfile($filePath);
    exit;
}

// ---------------- SPA FALLBACK ----------------
$indexHtml = $distPath . '/index.html';

if (file_exists($indexHtml)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($indexHtml);
    exit;
}

// ---------------- SAFE FAIL ----------------
http_response_code(503);
echo 'Application is updating. Please try again shortly.';
exit;

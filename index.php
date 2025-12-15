<?php
ob_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

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
    if (ob_get_length())
        ob_clean(); // Ensure clean start for API
    require __DIR__ . '/api/index.php';
    exit;
}

// ---------------- ANGULAR DIST PATH ----------------
$distPath = __DIR__ . '/frontend/dist/word-tracker/browser';
$realDistPath = realpath($distPath);

// ---------------- STATIC FILES ----------------
if ($realDistPath) {
    $filePath = realpath($distPath . $path);

    if (
        $path !== '/' &&
        $filePath &&
        strpos($filePath, $realDistPath) === 0 &&
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

        // Clean any potential warning output
        if (ob_get_length())
            ob_clean();

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');

        readfile($filePath);
        exit;
    }
}

// ---------------- SPA FALLBACK ----------------
$indexHtml = $distPath . '/index.html';

if (file_exists($indexHtml)) {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: text/html; charset=UTF-8');
    readfile($indexHtml);
    exit;
}

// ---------------- SAFE FAIL ----------------
http_response_code(503);
echo 'Application is updating. Please try again shortly.';
exit;

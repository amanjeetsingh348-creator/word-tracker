<?php
ob_start();

// 1. Parse Request
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// 2. API Routing
if (strpos($path, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    exit;
}

// 3. Static Files (Serve assets directly if they exist)
// Note: Adapting to Railway layout where Angular builds to frontend/dist/word-tracker/browser
$baseDistPath = __DIR__ . '/frontend/dist/word-tracker/browser';
$filePath = $baseDistPath . $path;

if ($path !== '/' && file_exists($filePath) && is_file($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'html' => 'text/html',
        'json' => 'application/json'
    ];
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
    header("Content-Type: $mime");
    readfile($filePath);
    exit;
}

// 4. Angular SPA Fallback
// If not API and not a static file, serve index.html
header("Content-Type: text/html; charset=UTF-8");
$indexHtml = $baseDistPath . '/index.html';

if (file_exists($indexHtml)) {
    readfile($indexHtml);
} else {
    // Graceful error if build is missing
    echo "<h1>Maintenance</h1><p>System is updating. Please try again in 1 minute.</p>";
}
exit;


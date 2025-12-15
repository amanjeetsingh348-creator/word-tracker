<?php
// router.php

// 1. Get request path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2. Define API prefix
// Adjust this if your API calls don't start with /api
$apiPrefix = '/api/';

// 3. Handle API Requests
if (strpos($uri, $apiPrefix) === 0 || $uri === '/api') {
    // Forward to the main backend index.php
    require __DIR__ . '/index.php';
    exit;
}

// 4. Handle Static Files from public folder
$publicDir = __DIR__ . '/public';
$filePath = $publicDir . $uri;

if (file_exists($filePath) && is_file($filePath)) {
    // Serve file with correct MIME type
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'html' => 'text/html',
        'json' => 'application/json',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain'
    ];
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'text/plain';
    header("Content-Type: $mime");
    readfile($filePath);
    exit;
}

// 5. Fallback to index.html for Angular routing (SPA)
if (file_exists($publicDir . '/index.html')) {
    header("Content-Type: text/html");
    readfile($publicDir . '/index.html');
} else {
    // Error if build failed or not found (and not an API call)
    http_response_code(404);
    echo "Frontend not found. The Angular build might have failed or output path is incorrect.";
}
?>
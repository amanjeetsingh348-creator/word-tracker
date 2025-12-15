<?php
// app/index.php

// NEVER echo before headers
ob_start();

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ---- API ROUTING ----
if (strpos($request, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    exit;
}

// ---- STATIC FILES ----
$file = __DIR__ . '/public' . $request;
if ($request !== '/' && file_exists($file)) {
    return false;
}

// ---- ANGULAR SPA FALLBACK ----
header("Content-Type: text/html; charset=UTF-8");
readfile(__DIR__ . '/public/index.html');
exit;

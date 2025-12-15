<?php
// backend-php/index.php

// 1. Init Configuration
require_once 'config/cors.php';
require_once 'config/database.php';

// Handle Preflight and CORS headers
handleCors();

// 2. Parse URL to determine API Endpoint
// Request URI comes in like /api/login or /word-tracker/backend-php/api/login depending on host
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($request_uri, '/'));

// Universal Router
// Routes requests from /word-tracker/endpoint.php to /backend-php/api/endpoint.php
// OR /endpoint.php to api/endpoint.php if served from root

$path = parse_url($request_uri, PHP_URL_PATH);
$filename = basename($path); // e.g. login.php or login

// If no extension, assume .php
if (strpos($filename, '.') === false) {
    $filename .= '.php';
}

// Security: Prevent directory traversal
$filename = basename($filename);

$apiFile = __DIR__ . '/api/' . $filename;

if (file_exists($apiFile)) {
    require $apiFile;
    exit; // IMPORTANT: Exit after serving API file to prevent double 404
}

// 3. Fallback / 404 - Only reached if API file not found
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    "message" => "API Endpoint not found",
    "status" => "error",
    "path" => $request_uri,
    "looking_for" => $apiFile
]);
?>
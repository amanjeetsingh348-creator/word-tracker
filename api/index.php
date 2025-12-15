<?php
// Secure API Router

// 1. Clean Output Buffer
if (ob_get_length())
    ob_clean();

// 2. Handle CORS
require_once __DIR__ . '/../config/cors.php';
handleCors();

// 3. Set Global API Headers
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

// 4. Parse Endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Strip /api prefix
$endpoint = substr($path, 4); // "/api" -> ""

// 5. Router Logic
if ($endpoint === '' || $endpoint === '/') {
    echo json_encode(['status' => 'healthy', 'message' => 'Word Tracker API v1']);
    exit;
}

// Clean Input
$endpoint = trim($endpoint, '/');

// Security: Prevent directory traversal and malicious dot files
if (strpos($endpoint, '..') !== false || strpos($endpoint, '/.') !== false || $endpoint === '.php' || strpos($endpoint, '.php/') !== false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit;
}

// Determine File Path
$filename = $endpoint . '.php';

// Handle cases where user requests /api/login.php directly
if (substr($endpoint, -4) === '.php') {
    $filename = $endpoint;
}

$targetFile = __DIR__ . '/' . $filename;

// 6. Execute or 404
if (file_exists($targetFile) && is_file($targetFile)) {
    // Prevent recursive loop if target is this index.php
    if (realpath($targetFile) === __FILE__) {
        echo json_encode(['status' => 'healthy', 'message' => 'API Root']);
        exit;
    }

    try {
        require $targetFile;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server Error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => "Endpoint not found: $endpoint"]);
}
exit;
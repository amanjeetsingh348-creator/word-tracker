<?php
header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'];

// Remove /api from path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(str_replace('/api', '', $path), '/');

// Handle /api or /api/
if ($path === '') {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'API endpoint required'
    ]);
    exit;
}

switch ($path) {

    case 'login':
        require __DIR__ . '/login.php';
        break;

    case 'register':
        require __DIR__ . '/register.php';
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'path' => $path
        ]);
}
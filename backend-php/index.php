<?php
// backend-php/index.php

require_once 'config/cors.php';
require_once 'config/database.php';

handleCors();

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Simple Router
// Assumes URL format: /api/{resource}/{action} or just /{resource}/{action} depending on server config
// For XAMPP localhost/word-tracker/backend-php/index.php?...

// To make it cleaner, we'll assume the user sets up .htaccess or calls index.php directly
// Let's parse the path.
$path = parse_url($request_uri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Basic dispatch logic
// Example: /backend-php/api/login
// We need to find where 'api' starts
$apiIndex = array_search('api', $pathParts);

if ($apiIndex !== false && isset($pathParts[$apiIndex + 1])) {
    $endpoint = $pathParts[$apiIndex + 1];

    switch ($endpoint) {
        case 'register':
            require 'api/register.php';
            break;
        case 'login':
            require 'api/login.php';
            break;
        case 'create-plan':
            require 'api/create_plan.php';
            break;
        case 'get-plans':
            require 'api/get_plans.php';
            break;
        case 'get-plan':
            require 'api/get_plan.php';
            break;
        case 'update-plan':
            require 'api/update_plan.php';
            break;
        case 'delete-plan':
            require 'api/delete_plan.php';
            break;
        case 'add-progress':
            require 'api/add_progress.php';
            break;
        case 'db-health':
            require 'api/db-health.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found"]);
            break;
    }
} else {
    echo json_encode(["message" => "Word Tracker API Running"]);
}
?>
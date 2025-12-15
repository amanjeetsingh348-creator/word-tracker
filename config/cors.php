<?php
// backend-php/config/cors.php

function handleCors()
{
    // Check if headers have already been sent by index.php or another source
    if (headers_sent()) {
        return;
    }

    // Allowed origins for CORS
    $allowedOrigins = [
        'http://localhost:4200',           // Local Angular dev
        'http://localhost',                // Local XAMPP
        'http://localhost:8000',           // Alternative local port

        // ADD YOUR RAILWAY FRONTEND URL HERE (Example):
        'https://word-tracker-production-c3bf.up.railway.app',

        // Vercel (if using)
        'https://word-tracker.vercel.app',
    ];

    // Get the origin from the request
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    // Check if origin is allowed
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (getenv('RAILWAY_ENVIRONMENT') || strpos($origin, 'railway.app') !== false) {
        // Allow all Railway domains in Railway environment
        header("Access-Control-Allow-Origin: {$origin}");
    } elseif (empty($origin) || $origin === 'null') {
        // For direct API access (testing)
        header("Access-Control-Allow-Origin: *");
    } else {
        // Default: allow all for development (remove in production if needed)
        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // Cache preflight for 1 day

    // Handle Preflight Options Request immediately
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}
?>
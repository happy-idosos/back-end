<?php
// ========================================
// CORS HEADERS - MUST BE FIRST!
// ========================================
header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
//header("Access-Control-Allow-Origin: http://localhost:5173");//
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

// Handle OPTIONS preflight immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================================
// UPLOAD CONFIGURATION
// ========================================
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '200M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');

// ========================================
// LOAD APPLICATION FILES
// ========================================
require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/routes/rotas.php';

// ========================================
// ROUTE HANDLING
// ========================================
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/back-end/api-php#', '', $uri);
$uri = preg_replace('#^/index\.php#', '', $uri);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Dispatch route
$response = route($method, $uri);

if (!$response) {
    http_response_code(404);
    echo json_encode([
        'erro' => 'Rota não encontrada',
        'uri' => $uri
    ]);
    exit;
}

// ========================================
// SEND RESPONSE
// ========================================
header('Content-Type: application/json; charset=utf-8');

if (is_array($response)) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo $response;
}

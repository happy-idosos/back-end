<?php
// config/cors.php - SALVE ESTE ARQUIVO

$allowedOrigins = [
    'https://www.happyidosos.com.br',
    'https://happyidosos.com.br', 
    'http://localhost:5173'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Permite uma origem padrão se não estiver na lista
    header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Responde imediatamente para requisições OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
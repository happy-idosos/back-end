<?php
// config/cors.php - VERSÃO CORRIGIDA

$allowedOrigins = [
    'https://www.happyidosos.com.br',
    'https://happyidosos.com.br',
    'http://localhost:5173',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Para produção, permite apenas o domínio principal
    header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token");
header("Access-Control-Expose-Headers: Authorization");
header("Access-Control-Max-Age: 86400");

// Responde imediatamente para requisições OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
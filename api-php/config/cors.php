<?php
// config/cors.php - VERSÃO SUPER PERMISSIVA PARA DEBUG

$allowedOrigins = [
    'https://www.happyidosos.com.br',
    'https://happyidosos.com.br',
    'http://localhost:5173',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// SEMPRE permite o domínio principal, mesmo sem HTTP_ORIGIN
header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token, Access-Control-Allow-Headers");
header("Access-Control-Expose-Headers: Authorization, Content-Length");
header("Access-Control-Max-Age: 86400");

// Log para debug (remove depois)
error_log("🎯 CORS Headers enviados para: " . $origin);

// Responde imediatamente para requisições OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    error_log("🎯 Preflight OPTIONS recebido");
    http_response_code(200);
    exit();
}
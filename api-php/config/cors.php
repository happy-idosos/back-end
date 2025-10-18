<?php
// config/cors.php — versão inteligente (aceita produção e localhost)

$allowedOrigins = [
    'https://www.happyidosos.com.br',
    'https://happyidosos.com.br',
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:5173'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verifica se a origem é permitida
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Origem não reconhecida — opcional para segurança
    header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
}

header("Vary: Origin"); // Garante cache correto quando várias origens são usadas
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token");
header("Access-Control-Expose-Headers: Authorization, Content-Length");
header("Access-Control-Max-Age: 86400");

// Log para debug (opcional, pode remover depois)
error_log("🎯 CORS para origem: " . ($origin ?: 'NENHUMA'));

// Responde imediatamente para requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

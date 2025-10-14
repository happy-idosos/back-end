<?php

header("Access-Control-Allow-Origin: https://www.happyidosos.com.br");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Habilita CORS e dependências básicas
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/connection.php';

// Carrega as rotas
require_once __DIR__ . '/routes/rotas.php';

// -------------------------------------------
// NORMALIZAÇÃO DA URI (com ou sem index.php)
// -------------------------------------------
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove caminho base do projeto
$uri = preg_replace('#^/back-end/api-php#', '', $uri);

// Remove /index.php (se existir)
$uri = preg_replace('#^/index\.php#', '', $uri);

// Remove barras finais duplicadas
$uri = rtrim($uri, '/');

// Identifica o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------
// DESPACHA A ROTA
// -------------------------------------------
$response = route($method, $uri);

// Caso a rota não exista
if (!$response) {
    http_response_code(404);
    echo json_encode([
        'erro' => 'Rota não encontrada',
        'uri' => $uri
    ]);
    exit;
}

// -------------------------------------------
// RETORNA RESPOSTA
// -------------------------------------------
header('Content-Type: application/json; charset=utf-8');

// Se o controller retornar array → converte em JSON
if (is_array($response)) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo $response;
}

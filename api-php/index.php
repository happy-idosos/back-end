<?php
// -------------------------------------------
// Inicialização e dependências
// -------------------------------------------

header("Access-Control-Allow-Origin: *"); // permite qualquer origem
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
// 1️⃣ Habilita CORS (deve ser o PRIMEIRO require)
require_once __DIR__ . '/config/cors.php';

// 2️⃣ Conexão com o banco de dados
require_once __DIR__ . '/config/connection.php';

// 3️⃣ Carrega rotas da API
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
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
?>

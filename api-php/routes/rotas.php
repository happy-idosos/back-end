<?php

require_once __DIR__ . '/../controllers/CadastroUsuarioController.php';
require_once __DIR__ . '/../controllers/CadastroAsiloController.php';
require_once __DIR__ . '/../config/connection.php';

// Configuração de headers
header("Content-Type: application/json; charset=UTF-8");

// Obtém o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Obtém o caminho da URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Lê o corpo da requisição
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $method !== 'GET') {
    $input = $_POST;
}

// Detecta o caminho base dinamicamente
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = rtrim($scriptPath, '/');
$path = str_replace($basePath, '', $path);
$path = trim($path, '/');

// Trata requisições OPTIONS para CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Adiciona log para debug
if (isset($_GET['debug'])) {
    error_log("Path: $path");
    error_log("Method: $method");
    error_log("Base Path: $basePath");
}

try {
    switch ($path) {
        case 'cadastro/usuario':
            if ($method === 'POST') {
                $cadastroUsuarioController = new CadastroUsuarioController($conn);
                $result = $cadastroUsuarioController->cadastrar($input);
                http_response_code($result['status']);
                echo json_encode($result);
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Método não permitido para esta rota."]);
            }
            break;

        case 'cadastro/asilo':
            if ($method === 'POST') {
                $cadastroAsiloController = new CadastroAsiloController($conn);
                $result = $cadastroAsiloController->cadastrar($input);
                http_response_code($result['status']);
                echo json_encode($result);
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Método não permitido para esta rota."]);
            }
            break;

        case '':
            http_response_code(200);
            echo json_encode(["message" => "API Happy Idosos está funcionando!"]);
            break;

        default:
            http_response_code(404);
            echo json_encode(["message" => "Rota não encontrada.", "path" => $path]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Erro interno do servidor",
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
<?php
require_once __DIR__ . '/../controllers/ExemploController.php';
require_once __DIR__ . '/../controllers/CadastroUsuarioController.php';
require_once __DIR__ . '/../controllers/CadastroAsiloController.php';
require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../controllers/ListagemController.php';
require_once __DIR__ . '/../controllers/VideoController.php';
require_once __DIR__ . '/../controllers/FiltraAsiloController.php';
require_once __DIR__ . '/../controllers/EsqueceuSenhaController.php';
require_once __DIR__ . '/../controllers/EventosController.php';
require_once __DIR__ . '/../config/connection.php';

header("Content-Type: application/json; charset=UTF-8");

// Normaliza URI e método
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$uri = str_replace($scriptName, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];

// Lê corpo JSON (quando não for GET)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $method !== 'GET') {
    $input = $_POST;
}

// Instancia os controllers
$exemploController = new ExemploController();
$cadastroUsuarioController = new CadastroUsuarioController($conn);
$cadastroAsiloController = new CadastroAsiloController($conn);
$loginController = new LoginController($conn);
$listagemController = new ListagemController($conn);
$videoController = new VideoController($conn);
$filtraAsiloController = new FiltraAsiloController($conn);
$esqueceuSenhaController = new EsqueceuSenhaController($conn);
$eventosController = new EventosController($conn);


// Rotas
if ($uri == '/api' && $method == 'GET') {
    $exemploController->index();
} elseif ($uri == '/api/cadastro/usuario' && $method == 'POST') {
    $result = $cadastroUsuarioController->cadastrar($input);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/cadastro/asilo' && $method == 'POST') {
    $result = $cadastroAsiloController->cadastrar($input);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/login' && $method == 'POST') {
    $result = $loginController->login($input);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/usuarios' && $method == 'GET') {
    $result = $listagemController->listarUsuarios();
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/asilos' && $method == 'GET') {
    $result = $listagemController->listarAsilos();
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/videos' && $method == 'POST') {
    $result = $videoController->uploadVideo($_FILES, $input);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/videos' && $method == 'GET') {
    $result = $videoController->listarVideos();
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/filtra/asilos' && $method == 'POST') {
    $result = $filtraAsiloController->filtrar($input);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/esqueceu-senha' && $method == 'POST') {
    $result = $esqueceuSenhaController->solicitarReset($input['email']);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/resetar_senha' && $method == 'POST') {
    $result = $esqueceuSenhaController->redefinirSenha($input['token'], $input['novaSenha']);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/eventos' && $method == 'GET') {
    $result = $eventosController->listarEventos();
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/eventos' && $method == 'POST') {
    $result = $eventosController->criarEvento(
        $input['nome_evento'],
        $input['descricao'],
        $input['data'],
        $input['id_usuario']
    );
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/eventos/participar' && $method == 'POST') {
    $result = $eventosController->participarEvento($input['id_usuario'], $input['id_evento']);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif ($uri == '/api/eventos/participantes' && $method == 'GET') {
    $result = $eventosController->listarParticipantes($input['id_evento']);
    http_response_code($result['status']);
    echo json_encode($result);
} else {
    http_response_code(404);
    echo json_encode(["erro" => "Rota não encontrada", "uri" => $uri]);
}

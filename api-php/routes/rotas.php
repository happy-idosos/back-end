<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/ExemploController.php';
require_once __DIR__ . '/../controllers/CadastroUsuarioController.php';
require_once __DIR__ . '/../controllers/CadastroAsiloController.php';
require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../controllers/ListagemController.php';
require_once __DIR__ . '/../controllers/VideoController.php';
require_once __DIR__ . '/../controllers/FiltraAsiloController.php';
require_once __DIR__ . '/../controllers/EsqueceuSenhaController.php';
require_once __DIR__ . '/../controllers/EventoController.php';
require_once __DIR__ . '/../controllers/ParticipacaoController.php';
require_once __DIR__ . '/../controllers/ContatoController.php';

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
$eventoController = new EventoController($conn);
$participacaoController = new ParticipacaoController($conn);
$contatoController = new ContatoController($conn);


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
} elseif ($uri == '/api/reset-senha' && $method == 'GET') {
    // retorna apenas o token para o front validar
    $token = $_GET['token'] ?? null;

    if (!$token) {
        http_response_code(400);
        echo json_encode(["status" => 400, "message" => "Token inválido"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id_usuario FROM reset_senha WHERE token = :token AND expira_em > NOW()");
    $stmt->bindParam(":token", $token);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(400);
        echo json_encode(["status" => 400, "message" => "Token inválido ou expirado"]);
        exit;
    }

    http_response_code(200);
    echo json_encode(["status" => 200, "message" => "Token válido", "token" => $token]);
} elseif ($uri == '/api/reset-senha' && $method == 'POST') {
    $result = $esqueceuSenhaController->redefinirSenha($input['token'], $input['novaSenha']);
    http_response_code($result['status']);
    echo json_encode($result);
} elseif($uri == '/api/eventos' && $method == 'GET'){
    $result = $eventoController->listarEventos();
    http_response_code($result['status']);
    echo json_encode($result);
}
elseif($uri == '/api/eventos/criar' && $method == 'POST'){
    // Aqui $id_asilo viria do token/autenticação
    $id_asilo = $input['id_asilo'] ?? null; // simulação
    $result = $eventoController->criarEvento($id_asilo,$input['titulo'],$input['descricao'],$input['data_evento']);
    http_response_code($result['status']);
    echo json_encode($result);
}
elseif($uri == '/api/eventos/participar' && $method == 'POST'){
    // Aqui $id_usuario viria do token/autenticação
    $id_usuario = $input['id_usuario'] ?? null; // simulação
    $id_evento = $input['id_evento'] ?? null;
    $result = $participacaoController->participarEvento($id_usuario,$id_evento);
    http_response_code($result['status']);
    echo json_encode($result);
}
elseif($uri == '/api/eventos/meus' && $method == 'GET'){
    // $id_usuario do token
    $id_usuario = $_GET['id_usuario'] ?? null; // simulação
    $result = $participacaoController->listarParticipacoes($id_usuario);
    http_response_code($result['status']);
    echo json_encode($result);
}
// Rota de contato
elseif($uri == '/api/contato' && $method == 'POST'){
    $arquivo = $_FILES['arquivo'] ?? null;
    $result = $contatoController->enviar($input, $arquivo);
    http_response_code($result['status']);
    echo json_encode($result);
} else {
    http_response_code(404);
    echo json_encode(["erro" => "Rota não encontrada", "uri" => $uri]);
}

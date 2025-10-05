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

// --- Normalização da rota ---
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$uri = str_replace($scriptName, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri = rtrim($uri, '/'); // remove barra no final se existir
$method = $_SERVER['REQUEST_METHOD'];

// --- Lê o body JSON ou fallback para $_POST ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $method !== 'GET') {
    $input = $_POST;
}

// --- Instancia os controllers ---
$exemploController       = new ExemploController();
$cadastroUsuarioController = new CadastroUsuarioController($conn);
$cadastroAsiloController   = new CadastroAsiloController($conn);
$loginController           = new LoginController($conn);
$listagemController        = new ListagemController($conn);
$videoController           = new VideoController($conn);
$filtraAsiloController     = new FiltraAsiloController($conn);
$esqueceuSenhaController   = new EsqueceuSenhaController($conn);
$eventoController          = new EventoController($conn);
$participacaoController    = new ParticipacaoController($conn);
$contatoController         = new ContatoController($conn);

// --- Rotas ---
switch (true) {
    case ($uri === '/api' && $method === 'GET'):
        $exemploController->index();
        break;

    case ($uri === '/api/cadastro/usuario' && $method === 'POST'):
        resposta($cadastroUsuarioController->cadastrar($input));
        break;

    case ($uri === '/api/cadastro/asilo' && $method === 'POST'):
        resposta($cadastroAsiloController->cadastrar($input));
        break;

    case ($uri === '/api/login' && $method === 'POST'):
        resposta($loginController->login($input));
        break;

    case ($uri === '/api/usuarios' && $method === 'GET'):
        resposta($listagemController->listarUsuarios());
        break;

    case ($uri === '/api/asilos' && $method === 'GET'):
        resposta($listagemController->listarAsilos());
        break;

    case ($uri === '/api/videos' && $method === 'POST'):
        resposta($videoController->uploadVideo($_FILES, $input));
        break;

    case ($uri === '/api/videos' && $method === 'GET'):
        resposta($videoController->listarVideos());
        break;

    case ($uri === '/api/filtra/asilos' && $method === 'POST'):
        resposta($filtraAsiloController->filtrar($input));
        break;

    // ==================================================
    // ROTAS DE RECUPERAÇÃO DE SENHA (ATUALIZADAS)
    // ==================================================
    case ($uri === '/api/esqueceu-senha' && $method === 'POST'):
        if (!isset($input['email'])) {
            resposta(["status" => 400, "message" => "Email é obrigatório"], 400);
        }
        resposta($esqueceuSenhaController->solicitarReset($input['email']));
        break;

    case ($uri === '/api/reset-senha' && $method === 'GET'):
        $token = $_GET['token'] ?? null;
        if (!$token) {
            resposta(["status" => 400, "message" => "Token é obrigatório"], 400);
        }
        resposta($esqueceuSenhaController->validarToken($token));
        break;

    case ($uri === '/api/reset-senha' && $method === 'POST'):
        if (!isset($input['token']) || !isset($input['novaSenha'])) {
            resposta(["status" => 400, "message" => "Token e nova senha são obrigatórios"], 400);
        }
        resposta($esqueceuSenhaController->redefinirSenha($input['token'], $input['novaSenha']));
        break;

    // ==================================================
    // ROTAS DE EVENTOS E PARTICIPAÇÕES
    // ==================================================
    case ($uri === '/api/eventos' && $method === 'GET'):
        resposta($eventoController->listarEventos());
        break;

    case ($uri === '/api/eventos/criar' && $method === 'POST'):
        $id_asilo = $input['id_asilo'] ?? null;
        resposta($eventoController->criarEvento($id_asilo, $input['titulo'], $input['descricao'], $input['data_evento']));
        break;

    case ($uri === '/api/eventos/participar' && $method === 'POST'):
        $id_usuario = $input['id_usuario'] ?? null;
        $id_evento  = $input['id_evento'] ?? null;
        resposta($participacaoController->participarEvento($id_usuario, $id_evento));
        break;

    case ($uri === '/api/eventos/meus' && $method === 'GET'):
        $id_usuario = $_GET['id_usuario'] ?? null;
        resposta($participacaoController->listarParticipacoes($id_usuario));
        break;

    // ==================================================
    // ROTA DE CONTATO
    // ==================================================
    case ($uri === '/api/contato' && $method === 'POST'):
        $arquivo = $_FILES['arquivo'] ?? null;
        resposta($contatoController->enviar($input, $arquivo));
        break;

    default:
        resposta(["erro" => "Rota não encontrada", "uri" => $uri], 404);
        break;
}

// --- Função utilitária ---
function resposta($result, $statusCode = null) {
    http_response_code($statusCode ?? $result['status'] ?? 200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
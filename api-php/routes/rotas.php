<?php


require_once __DIR__ . '/../middleware/AuthMiddleware.php';


foreach (glob(__DIR__ . '/../controllers/*.php') as $file) {
    require_once $file;
}

// Conexão com o banco de dados
if (!isset($conn) && isset($GLOBALS['conn'])) {
    $conn = $GLOBALS['conn'];
}

// Instancia controllers
$exemploController         = class_exists('ExemploController') ? new ExemploController() : null;
$cadastroUsuarioController = class_exists('CadastroUsuarioController') ? new CadastroUsuarioController($conn) : null;
$cadastroAsiloController   = class_exists('CadastroAsiloController') ? new CadastroAsiloController($conn) : null;
$loginController           = class_exists('LoginController') ? new LoginController($conn) : null;
$listagemController        = class_exists('ListagemController') ? new ListagemController($conn) : null;
$videoController           = class_exists('VideoController') ? new VideoController($conn) : null;
$filtraAsiloController     = class_exists('FiltraAsiloController') ? new FiltraAsiloController($conn) : null;
$esqueceuSenhaController   = class_exists('EsqueceuSenhaController') ? new EsqueceuSenhaController($conn) : null;
$eventoController          = class_exists('EventoController') ? new EventoController($conn) : null;
$participacaoController    = class_exists('ParticipacaoController') ? new ParticipacaoController($conn) : null;
$contatoController         = class_exists('ContatoController') ? new ContatoController($conn) : null;
$editarPerfilController = class_exists('EditarPerfilController') ? new EditarPerfilController($conn) : null;

// Helpers 
if (!function_exists('getJsonInput')) {
    function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
        return $_POST ?? [];
    }
}

if (!function_exists('safeCall')) {
    function safeCall(callable $fn)
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            return [
                'status' => 500,
                'error'  => 'Erro interno no servidor',
                'detail' => $e->getMessage()
            ];
        }
    }
}

// Definição de rotas
$routes = [
    // ========== ROTAS PÚBLICAS ==========

    // Rota de debug do token (remova depois de testar)
['GET', '/api/debug/token', function () {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if (!$token) {
        return ['status' => 400, 'message' => 'Token não enviado'];
    }
    
    $token = str_replace('Bearer ', '', $token);
    
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['status' => 400, 'message' => 'Token inválido'];
        }
        
        $payload = $parts[1];
        $payload = str_replace(['-', '_'], ['+', '/'], $payload);
        $mod4 = strlen($payload) % 4;
        if ($mod4) {
            $payload .= str_repeat('=', 4 - $mod4);
        }
        
        $payloadDecoded = base64_decode($payload);
        $payloadData = json_decode($payloadDecoded, true);
        
        return [
            'status' => 200,
            'token_info' => [
                'payload_completo' => $payloadData,
                'tem_campo_data' => isset($payloadData['data']),
                'dados_extraidos' => isset($payloadData['data']) ? [
                    'id' => $payloadData['data']['id'] ?? null,
                    'tipo' => $payloadData['data']['tipo'] ?? null,
                    'nome' => $payloadData['data']['nome'] ?? null
                ] : null
            ]
        ];
    } catch (Exception $e) {
        return ['status' => 500, 'message' => 'Erro ao decodificar token', 'error' => $e->getMessage()];
    }
}],

    // Health check
    ['GET', '/', function () {
        return ['status' => 200, 'message' => 'API Happy Idosos funcionando'];
    }],

    ['GET', '/api', function () {
        return ['status' => 200, 'message' => 'API Happy Idosos v1.0'];
    }],

    // Cadastro (público)
    ['POST', '/api/cadastro/usuario', function () use ($cadastroUsuarioController) {
        $input = getJsonInput();
        return safeCall(fn() => $cadastroUsuarioController->cadastrar($input));
    }],

    ['POST', '/api/cadastro/asilo', function () use ($cadastroAsiloController) {
        $input = getJsonInput();
        return safeCall(fn() => $cadastroAsiloController->cadastrar($input));
    }],

    // Login (público)
    ['POST', '/api/login', function () use ($loginController) {
        $input = getJsonInput();
        return safeCall(fn() => $loginController->login($input));
    }],

    // Listagens públicas
    ['GET', '/api/usuarios', function () use ($listagemController) {
        return safeCall(fn() => $listagemController->listarUsuarios());
    }],

    ['GET', '/api/asilos', function () use ($listagemController) {
        return safeCall(fn() => $listagemController->listarAsilos());
    }],

    // Filtrar asilos (público)
    ['POST', '/api/filtra/asilos', function () use ($filtraAsiloController) {
        $input = getJsonInput();
        return safeCall(fn() => $filtraAsiloController->filtrar($input));
    }],

    // Recuperação de senha (público)
    ['POST', '/api/esqueceu-senha', function () use ($esqueceuSenhaController) {
        $input = getJsonInput();
        $email = $input['email'] ?? null;
        return safeCall(fn() => $esqueceuSenhaController->solicitarReset($email));
    }],

    ['POST', '/api/reset-senha', function () use ($esqueceuSenhaController) {
        $input = getJsonInput();
        return safeCall(fn() => $esqueceuSenhaController->redefinirSenha(
            $input['token'] ?? null,
            $input['novaSenha'] ?? null
        ));
    }],

['GET', '/api/reset-senha', function() use ($esqueceuSenhaController) {
    // valida token via query string ?token=...
    $token = $_GET['token'] ?? null;
    if (!$token) return ['status' => 400, 'message' => 'Token inválido'];
    
    // Usa o método do controller em vez de fazer consulta direta
    return safeCall(fn() => $esqueceuSenhaController->validarToken($token));
}],

    // Eventos - listagem pública
    ['GET', '/api/eventos', function () use ($eventoController) {
        return safeCall(fn() => $eventoController->listarEventos());
    }],

    ['GET', '/api/eventos/:id', function () use ($eventoController) {
        $id_evento = $_GET['id'] ?? null;
        return safeCall(fn() => $eventoController->buscarEvento($id_evento));
    }],

    // Vídeos - listagem pública
    ['GET', '/api/videos', function () use ($videoController) {
        return safeCall(fn() => $videoController->listarVideos());
    }],

    // Contato (público)
    ['POST', '/api/contato', function () use ($contatoController) {
        $input = getJsonInput();
        $arquivo = $_FILES['arquivo'] ?? null;
        return safeCall(fn() => $contatoController->enviar($input, $arquivo));
    }],

    // ========== ROTAS PROTEGIDAS (REQUEREM AUTENTICAÇÃO) ==========

    // Eventos - criar (apenas asilos)
    ['POST', '/api/eventos/criar', function () use ($eventoController) {
        $user = AuthMiddleware::requireType('asilo');
        $input = getJsonInput();
        return safeCall(fn() => $eventoController->criarEvento(
            $user,
            $input['titulo'] ?? null,
            $input['descricao'] ?? null,
            $input['data_evento'] ?? null
        ));
    }],

    // Participações - participar de evento (apenas usuários)
    ['POST', '/api/eventos/participar', function () use ($participacaoController) {
        $user = AuthMiddleware::requireType('usuario');
        $input = getJsonInput();
        return safeCall(fn() => $participacaoController->participarEvento(
            $user,
            $input['id_evento'] ?? null
        ));
    }],

    // Participações - cancelar participação (apenas usuários)
    ['DELETE', '/api/eventos/participar', function () use ($participacaoController) {
        $user = AuthMiddleware::requireType('usuario');
        $input = getJsonInput();
        return safeCall(fn() => $participacaoController->cancelarParticipacao(
            $user,
            $input['id_evento'] ?? null
        ));
    }],

    // Participações - listar minhas participações (apenas usuários)
    ['GET', '/api/eventos/meus', function () use ($participacaoController) {
        $user = AuthMiddleware::requireType('usuario');
        return safeCall(fn() => $participacaoController->listarMinhasParticipacoes($user));
    }],

    // Participações - listar participantes de um evento (apenas asilos)
    ['GET', '/api/eventos/:id/participantes', function () use ($participacaoController) {
        $user = AuthMiddleware::requireType('asilo');
        $id_evento = $_GET['id'] ?? null;
        return safeCall(fn() => $participacaoController->listarParticipantes($user, $id_evento));
    }],

    // Vídeos - upload (requer autenticação)
// CORREÇÃO: A rota de upload deve ser POST /api/videos

// Rota de upload de vídeos - CORRIGIDA
['POST', '/api/videos', function () use ($videoController) {
    // Habilita exibição de erros para debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $user = AuthMiddleware::requireAuth();
    
    // Debug do que está chegando
    error_log("=== DEBUG UPLOAD VIDEO ===");
    error_log("FILES: " . print_r($_FILES, true));
    error_log("POST: " . print_r($_POST, true));
    error_log("USER: " . print_r($user, true));
    
    return safeCall(fn() => $videoController->uploadVideo($user, $_FILES, $_POST));
}],

    // Vídeos - deletar (apenas autor)
    ['DELETE', '/api/videos/:id', function () use ($videoController) {
        $user = AuthMiddleware::requireAuth();
        $id_midia = $_GET['id'] ?? null;
        return safeCall(fn() => $videoController->deletarVideo($user, $id_midia));
    }],
    // Editar perfil (usuários e asilos)
['PUT', '/api/perfil/editar', function () use ($editarPerfilController) {
    $input = getJsonInput();
    return safeCall(fn() => $editarPerfilController->editarPerfil($input, $_FILES));
}],

// Buscar perfil completo
['GET', '/api/perfil', function () use ($editarPerfilController) {
    return safeCall(fn() => $editarPerfilController->buscarPerfil());
}],

// Rota específica para upload de foto (POST)
['POST', '/api/perfil/foto', function () use ($editarPerfilController) {
    return safeCall(fn() => $editarPerfilController->atualizarFoto($_FILES));
}],

// Rota alternativa para upload de foto (PUT)
['PUT', '/api/perfil/foto', function () use ($editarPerfilController) {
    return safeCall(fn() => $editarPerfilController->atualizarFoto($_FILES));
}],

// Rota de debug do token e autenticação
['GET', '/api/debug/auth', function () {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if (!$token) {
        return ['status' => 400, 'message' => 'Token não enviado'];
    }
    
    $token = str_replace('Bearer ', '', $token);
    
    // Decodifica manualmente para ver a estrutura
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['status' => 400, 'message' => 'Token inválido'];
    }
    
    $payload = $parts[1];
    $payload = str_replace(['-', '_'], ['+', '/'], $payload);
    $mod4 = strlen($payload) % 4;
    if ($mod4) {
        $payload .= str_repeat('=', 4 - $mod4);
    }
    
    $payloadDecoded = base64_decode($payload);
    $payloadData = json_decode($payloadDecoded, true);
    
    return [
        'status' => 200,
        'token_info' => $payloadData,
        'auth_middleware_result' => AuthMiddleware::requireAuth()
    ];
}],

];

// Implementação da função route()
if (!function_exists('route')) {
    function route(string $method, string $uri)
    {
        global $routes;

        // Normaliza URI
        if ($uri === '' || $uri === null) $uri = '/';
        if ($uri[0] !== '/') $uri = '/' . $uri;

        foreach ($routes as $r) {
            [$m, $u, $handler] = $r;

            if (strtoupper($method) !== strtoupper($m)) continue;

            // Rota exata
            if ($u === $uri) {
                $res = $handler();
                if (is_null($res)) {
                    return ['status' => 204, 'message' => 'Sem conteúdo'];
                }
                return $res;
            }

            // Rota com parâmetros dinâmicos (ex: /api/eventos/:id)
            $pattern = preg_replace('/:\w+/', '([^/]+)', $u);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove o match completo

                // Extrai nomes dos parâmetros
                preg_match_all('/:(\w+)/', $u, $paramNames);
                $params = array_combine($paramNames[1], $matches);

                // Adiciona parâmetros ao $_GET
                $_GET = array_merge($_GET, $params);

                $res = $handler();
                if (is_null($res)) {
                    return ['status' => 204, 'message' => 'Sem conteúdo'];
                }
                return $res;
            }
        }

        return false; // Rota não encontrada
    }
}

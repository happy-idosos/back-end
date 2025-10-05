<?php
// api-php/routes/rotas.php

// Carrega todos os controllers automaticamente 
foreach (glob(__DIR__ . '/../controllers/*.php') as $file) {
    require_once $file;
}

// Garante que $conn (PDO) esteja disponível
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

// Helpers 
if (!function_exists('getJsonInput')) {
    function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
        // fallback to POST (form-data) if JSON is not present
        return $_POST ?? [];
    }
}

if (!function_exists('safeCall')) {
    /**
     * Executa callback com try/catch e retorna um array padronizado de erro em caso de exception.
     */
    function safeCall(callable $fn)
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            // Em desenvolvimento pode expor $e->getMessage(); em produção registre em log e retorne mensagem genérica
            return [
                'status' => 500,
                'error'  => 'Erro interno no servidor',
                'detail' => $e->getMessage()
            ];
        }
    }
}

// - Definição de rotas (method, uri, handler) -
// Atenção: as URIs devem ter barra inicial, ex: '/api', '/api/usuarios'
$routes = [
    // Root / health
    ['GET',  '/',                function() use ($exemploController) {
        return safeCall(fn() => ['status' => 200, 'message' => 'API funcionando corretamente']);
    }],

    ['GET',  '/api',             function() use ($exemploController) {
        return safeCall(fn() => method_exists($exemploController, 'index') ? $exemploController->index() : ['status'=>200,'message'=>'API ok']);
    }],

    // Cadastro
    ['POST', '/api/cadastro/usuario', function() use ($cadastroUsuarioController) {
        $input = getJsonInput();
        return safeCall(fn() => $cadastroUsuarioController->cadastrar($input));
    }],
    ['POST', '/api/cadastro/asilo', function() use ($cadastroAsiloController) {
        $input = getJsonInput();
        return safeCall(fn() => $cadastroAsiloController->cadastrar($input));
    }],

    // Login
    ['POST', '/api/login', function() use ($loginController) {
        $input = getJsonInput();
        return safeCall(fn() => $loginController->login($input));
    }],

    // Listagens
    ['GET', '/api/usuarios', function() use ($listagemController) {
        return safeCall(fn() => $listagemController->listarUsuarios());
    }],
    ['GET', '/api/asilos', function() use ($listagemController) {
        return safeCall(fn() => $listagemController->listarAsilos());
    }],

    // Vídeos
    ['POST', '/api/videos', function() use ($videoController) {
        $input = getJsonInput();
        return safeCall(fn() => $videoController->uploadVideo($_FILES ?? [], $input));
    }],
    ['GET', '/api/videos', function() use ($videoController) {
        return safeCall(fn() => $videoController->listarVideos());
    }],

    // Filtrar Asilos
    ['POST', '/api/filtra/asilos', function() use ($filtraAsiloController) {
        $input = getJsonInput();
        return safeCall(fn() => $filtraAsiloController->filtrar($input));
    }],

    // Recuperação de senha
    ['POST', '/api/esqueceu-senha', function() use ($esqueceuSenhaController) {
        $input = getJsonInput();
        $email = $input['email'] ?? null;
        return safeCall(fn() => $esqueceuSenhaController->solicitarReset($email));
    }],
    ['POST', '/api/reset-senha', function() use ($esqueceuSenhaController) {
        $input = getJsonInput();
        return safeCall(fn() => $esqueceuSenhaController->redefinirSenha($input['token'] ?? null, $input['novaSenha'] ?? null));
    }],
    ['GET', '/api/reset-senha', function() use ($conn) {
        // valida token via query string ?token=...
        $token = $_GET['token'] ?? null;
        if (!$token) return ['status' => 400, 'message' => 'Token inválido'];
        $stmt = $conn->prepare("SELECT id_usuario FROM reset_senha WHERE token = :token AND expira_em > NOW()");
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['status' => 400, 'message' => 'Token inválido ou expirado'];
        return ['status' => 200, 'message' => 'Token válido', 'token' => $token];
    }],

    // Eventos / Participações
    ['GET', '/api/eventos', function() use ($eventoController) {
        return safeCall(fn() => $eventoController->listarEventos());
    }],
    ['POST', '/api/eventos/criar', function() use ($eventoController) {
        $input = getJsonInput();
        return safeCall(fn() => $eventoController->criarEvento($input['id_asilo'] ?? null, $input['titulo'] ?? null, $input['descricao'] ?? null, $input['data_evento'] ?? null));
    }],
    ['POST', '/api/eventos/participar', function() use ($participacaoController) {
        $input = getJsonInput();
        return safeCall(fn() => $participacaoController->participarEvento($input['id_usuario'] ?? null, $input['id_evento'] ?? null));
    }],
    ['GET', '/api/eventos/meus', function() use ($participacaoController) {
        $id_usuario = $_GET['id_usuario'] ?? null;
        return safeCall(fn() => $participacaoController->listarParticipacoes($id_usuario));
    }],

    // Contato (com arquivo)
    ['POST', '/api/contato', function() use ($contatoController) {
        $input = getJsonInput();
        $arquivo = $_FILES['arquivo'] ?? null;
        return safeCall(fn() => $contatoController->enviar($input, $arquivo));
    }],
];

//  Implementação da função route() (evita Undefined function) 
if (!function_exists('route')) {
    /**
     * route()
     * @param string $method HTTP method (GET, POST, ...)
     * @param string $uri    URI normalizada (ex: '/api/usuarios')
     * @return array|false Resultado da rota ou false se não encontrada
     */
    function route(string $method, string $uri)
    {
        global $routes;

        // Normaliza uri: vazio -> '/'
        if ($uri === '' || $uri === null) $uri = '/';

        // Garante barra inicial
        if ($uri[0] !== '/') $uri = '/' . $uri;

        foreach ($routes as $r) {
            [$m, $u, $handler] = $r;
            if (strtoupper($method) !== strtoupper($m)) continue;
            // rota exata
            if ($u === $uri) {
                // executa retorno do handler
                $res = $handler();
                // se o handler retornar null ou true/false, converte em resposta uniforme
                if (is_null($res)) {
                    return ['status' => 204, 'message' => 'Sem conteúdo'];
                }
                return $res;
            }
        }

        return false; // não encontrou a rota
    }
}

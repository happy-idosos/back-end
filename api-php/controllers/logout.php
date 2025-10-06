<?php
session_start();

// Remove todas as variáveis da sessão
$_SESSION = [];

// Destrói a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Retorno padronizado JSON
header('Content-Type: application/json');
echo json_encode([
    'status' => 200,
    'message' => 'Logout realizado com sucesso'
]);
exit;

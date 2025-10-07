<?php
// -------------------------------------------
// CONFIGURAÇÃO CORS GLOBAL — Happy Idosos API
// -------------------------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Permitir cookies/sessões, se precisar (descomente se for usar)
// header("Access-Control-Allow-Credentials: true");

// -------------------------------------------
// PRÉ-REQUISIÇÃO (OPTIONS)
// -------------------------------------------
// O navegador envia uma requisição OPTIONS antes de POST/PUT/DELETE.
// Este bloco responde imediatamente para evitar erro CORS.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>

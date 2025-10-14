<?php
// Headers CORS para desenvolvimento
header("Access-Control-Allow-Origin: https://www.happyidosos.com.br"); // URL do seu front-end em produção
header("Access-Control-Allow-Origin: http://localhost:5173"); // URL do seu React
//header("Access-Control-Allow-Origin: *"); // Permitir todas as origens (para desenvolvimento)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Para requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Seu código existente continua aqui...
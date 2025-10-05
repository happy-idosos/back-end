<?php
$uri = ltrim($_SERVER['REQUEST_URI'], '/');

// Se existir um arquivo ou diretório, serve normalmente
if (file_exists(__DIR__ . '/' . $uri)) {
    return false;
}

// Caso contrário, direciona para o index.php
require_once __DIR__ . '/index.php';

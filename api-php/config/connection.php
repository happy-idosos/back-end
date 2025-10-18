<?php
require_once __DIR__ . '/../vendor/autoload.php';

// ========================================
// CARREGAR VARIÁVEIS DE AMBIENTE
// ========================================
try {
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    } else {
        error_log("⚠️ Arquivo .env não encontrado — usando valores padrão locais.");
    }
} catch (Exception $e) {
    error_log("⚠️ Erro ao carregar .env: " . $e->getMessage());
}

// ========================================
// DETECTAR AMBIENTE (LOCAL OU PRODUÇÃO)
// ========================================
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1']);

// ========================================
// CONFIGURAÇÕES DE BANCO
// ========================================
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'happy_idosos';

// Se for local, usa root sem senha por padrão
if ($isLocal) {
    $username = $_ENV['DB_USER_LOCAL'] ?? 'root';
    $password = $_ENV['DB_PASS_LOCAL'] ?? '';
} else {
    $username = $_ENV['DB_USER'] ?? 'happyidosos_user';
    $password = $_ENV['DB_PASS'] ?? 'senha_segura_123';
}

// ========================================
// CONEXÃO PDO
// ========================================
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

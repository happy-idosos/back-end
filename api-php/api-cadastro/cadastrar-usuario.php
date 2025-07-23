<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include '../conexao.php';
include '../funcoes/validadores.php';

$data = json_decode(file_get_contents("php://input"), true);

$nome = trim($data['nome'] ?? '');
$email = trim($data['email'] ?? '');
$senha = trim($data['senha'] ?? '');
$tipo = strtolower(trim($data['tipo'] ?? ''));
$documento = preg_replace('/[^0-9]/', '', $data['documento'] ?? '');

// / Validações antes de tentar qualquer INSERT

if (!$nome || !$email || !$senha || !$tipo || !$documento) {
    http_response_code(400);
    echo json_encode(["erro" => "Todos os campos são obrigatórios"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["erro" => "Email inválido"]);
    exit;
}

if (strlen($senha) < 6) {
    echo json_encode(["erro" => "A senha deve ter pelo menos 6 caracteres"]);
    exit;
}

if (!in_array($tipo, ['voluntario', 'asilos'])) {
    echo json_encode(["erro" => "Tipo de usuário inválido"]);
    exit;
}

if ($tipo === 'voluntario' && !validarCPF($documento)) {
    echo json_encode(["erro" => "CPF inválido"]);
    exit;
}

if ($tipo === 'asilos' && !validarCNPJ($documento)) {
    echo json_encode(["erro" => "CNPJ inválido"]);
    exit;
}

// / Hash seguro da senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// / Tenta inserir no banco
$query = "INSERT INTO usuarios (nome, email, senha, tipo, documento) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $nome, $email, $senhaHash, $tipo, $documento);

if ($stmt->execute()) {
    echo json_encode(["status" => "sucesso", "mensagem" => "Usuário cadastrado com sucesso"]);
    exit;
}

// / Trata erro de duplicidade de email
if ($stmt->errno === 1062) {
    echo json_encode(["erro" => "Email já está em uso"]);
    exit;
}

// / Erro genérico
http_response_code(500);
echo json_encode(["erro" => "Erro ao cadastrar: " . $stmt->error]);

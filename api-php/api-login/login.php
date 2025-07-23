<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include '../conexao.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$senha = trim($data['senha'] ?? '');

if (!$email || !$senha) {
    http_response_code(400);
    echo json_encode(["erro" => "Email e senha são obrigatórios"]);
    exit;
}

$query = "SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if ($usuario && password_verify($senha, $usuario['senha'])) {
    echo json_encode([
        "status" => "sucesso",
        "usuario" => [
            "id" => $usuario["id"],
            "nome" => $usuario["nome"],
            "tipo" => $usuario["tipo"]
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["erro" => "Credenciais inválidas"]);
}

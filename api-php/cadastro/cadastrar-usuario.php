<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include '../config/conexao.php';
include '../funcoes/validadores.php';

$data = json_decode(file_get_contents("php://input"), true);

// Campos obrigatórios
$nome = trim($data['nome'] ?? '');
$email = trim($data['email'] ?? '');
$senha = trim($data['senha'] ?? '');
$tipo = strtolower(trim($data['tipo'] ?? ''));
$documento = preg_replace('/[^0-9]/', '', $data['documento'] ?? '');
$endereco = trim($data['endereco'] ?? '');

// Validações iniciais
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

if ($tipo === 'asilos') {
    if (!validarCNPJ($documento)) {
        echo json_encode(["erro" => "CNPJ inválido"]);
        exit;
    }

    if (!$endereco) {
        echo json_encode(["erro" => "Endereço é obrigatório para asilos"]);
        exit;
    }

    // Geolocalização usando Nominatim (OpenStreetMap)
    $encodedAddress = urlencode($endereco);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddress}";

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: HappyIdosos/1.0"
        ]
    ];
    $context = stream_context_create($opts);
    $geoData = json_decode(file_get_contents($url, false, $context), true);

    if (!$geoData || !isset($geoData[0]['lat'], $geoData[0]['lon'])) {
        echo json_encode(["erro" => "Não foi possível localizar a latitude/longitude do endereço fornecido"]);
        exit;
    }

    $latitude = (float) $geoData[0]['lat'];
    $longitude = (float) $geoData[0]['lon'];
} else {
    // Para voluntários, endereço e geolocalização não são necessários
    $endereco = null;
    $latitude = null;
    $longitude = null;
}

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Query preparada
$query = "INSERT INTO usuarios (nome, email, senha, tipo, documento, endereco, latitude, longitude)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro na preparação da query: " . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssdd", $nome, $email, $senhaHash, $tipo, $documento, $endereco, $latitude, $longitude);

// Executa e retorna resposta
if ($stmt->execute()) {
    echo json_encode(["status" => "sucesso", "mensagem" => "Usuário cadastrado com sucesso"]);
    exit;
}

if ($stmt->errno === 1062) {
    echo json_encode(["erro" => "Email já está em uso"]);
    exit;
}

http_response_code(500);
echo json_encode(["erro" => "Erro ao cadastrar: " . $stmt->error]);

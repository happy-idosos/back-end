<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include '../config/conexao.php';

// Lê o corpo da requisição JSON
$data = json_decode(file_get_contents("php://input"), true);

// Captura latitude e longitude do usuário
$latUser = floatval($data['latitude'] ?? 0);
$lonUser = floatval($data['longitude'] ?? 0);

// Verificação mínima de segurança
if (!$latUser || !$lonUser) {
    http_response_code(400);
    echo json_encode(["erro" => "Coordenadas inválidas"]);
    exit;
}

/*
 * Cálculo da distância usando fórmula Haversine diretamente no MySQL.
 * Retorna asilos em ordem crescente de distância (limite opcional).
 */
$query = "
    SELECT 
        id, nome, email, endereco, latitude, longitude,
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) * 
            COS(RADIANS(longitude) - RADIANS(?)) + 
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        )) AS distancia_km
    FROM usuarios
    WHERE tipo = 'asilos' AND latitude IS NOT NULL AND longitude IS NOT NULL
    ORDER BY distancia_km ASC
    LIMIT 20
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ddd", $latUser, $lonUser, $latUser);
$stmt->execute();
$result = $stmt->get_result();

$asilos = [];

while ($row = $result->fetch_assoc()) {
    $asilos[] = [
        "id" => $row["id"],
        "nome" => $row["nome"],
        "email" => $row["email"],
        "endereco" => $row["endereco"], // ADICIONE ESTA LINHA
        "latitude" => $row["latitude"],
        "longitude" => $row["longitude"],
        "distancia_km" => round($row["distancia_km"], 2)
    ];
}

echo json_encode($asilos);

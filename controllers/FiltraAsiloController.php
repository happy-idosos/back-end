<?php
class FiltraAsiloController {
    private $conn;
    private $geoApiKey = "ad23e416f0cc4c67a34f3aae72635f07";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Função para pegar coordenadas da cidade via Geoapify
    private function getCoordenadasCidade($cidade, $estado) {
        $query = urlencode("$cidade, $estado, Brasil");
        $url = "https://api.geoapify.com/v1/geocode/search?text={$query}&apiKey={$this->geoApiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (!empty($data['features'][0]['geometry']['coordinates'])) {
            return [
                'lon' => $data['features'][0]['geometry']['coordinates'][0],
                'lat' => $data['features'][0]['geometry']['coordinates'][1]
            ];
        }
        return null;
    }

    // Cálculo da distância (Haversine)
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
        $raioTerra = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $raioTerra * $c;
    }

    // Método principal que sua rota está chamando
    public function filtrar($dados) {
        if (empty($dados['cidade']) || empty($dados['estado'])) {
            return ["status" => 400, "message" => "Cidade e estado são obrigatórios."];
        }

        $distanciaMaximaKm = $dados['distancia'] ?? 10;

        // 1. Coordenadas da cidade
        $coordenadas = $this->getCoordenadasCidade($dados['cidade'], $dados['estado']);
        if (!$coordenadas) {
            return ["status" => 404, "message" => "Não foi possível localizar a cidade."];
        }

        $latUser = $coordenadas['lat'];
        $lonUser = $coordenadas['lon'];

        // 2. Buscar todos os asilos no banco
        $sql = "SELECT id_asilo, nome, endereco, cidade, estado, latitude, longitude, telefone, email
                FROM asilos";
        $stmt = $this->conn->query($sql);
        $asilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Filtrar por distância
        $resultado = [];
        foreach ($asilos as $asilo) {
            if ($asilo['latitude'] && $asilo['longitude']) {
                $distancia = $this->calcularDistancia($latUser, $lonUser, $asilo['latitude'], $asilo['longitude']);

                if ($distancia <= $distanciaMaximaKm) {
                    $asilo['distancia_km'] = round($distancia, 2);
                    $resultado[] = $asilo;
                }
            }
        }

        return ["status" => 200, "asilos" => $resultado];
    }
}

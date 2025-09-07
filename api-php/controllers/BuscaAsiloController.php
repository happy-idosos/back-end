<?php

require_once __DIR__ . '/../config/connection.php';

class BuscaAsiloController
{
    private $pdo;
    private $geoapifyKey = "ad23e416f0cc4c67a34f3aae72635f07"; 

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Buscar asilos recomendados do banco (campo recomendado=1)
    public function getRecomendados()
    {
        $sql = "SELECT id, nome, endereco, telefone, latitude, longitude 
                FROM asilos 
                WHERE recomendado = 1 
                ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $asilos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($asilos, JSON_UNESCAPED_UNICODE);
    }

    // Buscar asilos próximos via Geoapify API
    public function getProximos($lat, $lng, $raio = 5000)
    {
        // Endpoint Geoapify Places API
        $url = "https://api.geoapify.com/v2/places?categories=healthcare.elderly&filter=circle:$lng,$lat,$raio&bias=proximity:$lng,$lat&limit=10&apiKey=ad23e416f0cc4c67a34f3aae72635f07" . $this->geoapifyKey;

        $response = file_get_contents($url);

        if ($response === FALSE) {
            echo json_encode(["error" => "Erro ao consultar Geoapify"]);
            return;
        }

        $data = json_decode($response, true);



        // Simplificar retorno
        $asilos = [];
        if (isset($data['features'])) {
            foreach ($data['features'] as $f) {
                $asilos[] = [
                    "nome"     => $f['properties']['name'] ?? "Asilo não identificado",
                    "endereco" => $f['properties']['formatted'] ?? null,
                    "latitude" => $f['geometry']['coordinates'][1],
                    "longitude"=> $f['geometry']['coordinates'][0],
                    "distancia"=> $f['properties']['distance'] ?? null
                ];
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($asilos, JSON_UNESCAPED_UNICODE);
    }
}

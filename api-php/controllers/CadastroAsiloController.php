<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';

class CadastroAsiloController
{
    private $conn;
    private $geoApiKey = "ad23e416f0cc4c67a34f3aae72635f07"; // sua API Key do Geoapify

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function cadastrar($dados)
    {
        // Validações básicas
        if (empty($dados['cnpj']) || empty($dados['nome']) || empty($dados['email']) || empty($dados['senha']) || empty($dados['endereco']) || empty($dados['cidade']) || empty($dados['estado'])) {
            return ["status" => 400, "message" => "Dados obrigatórios não preenchidos."];
        }

        if (!validarCNPJ($dados['cnpj'])) {
            return ["status" => 400, "message" => "CNPJ inválido."];
        }

        if (!validarNome($dados['nome'])) {
            return ["status" => 400, "message" => "Nome inválido."];
        }

        if (!empty($dados['telefone']) && !validarTelefone($dados['telefone'])) {
            return ["status" => 400, "message" => "Telefone inválido."];
        }

        if (!validarSenha($dados['senha'])) {
            return ["status" => 400, "message" => "Senha não atende aos requisitos de segurança."];
        }

        // Monta endereço completo para geolocalização
        $enderecoCompleto = $dados['endereco'] . ", " . $dados['cidade'] . " - " . $dados['estado'] . ", Brasil";
        $encoded = urlencode($enderecoCompleto);
        $url = "https://api.geoapify.com/v1/geocode/search?text={$encoded}&apiKey={$this->geoApiKey}";

        $response = file_get_contents($url);
        $geoData = json_decode($response, true);

        $latitude = null;
        $longitude = null;

        if (!empty($geoData['features'][0]['geometry']['coordinates'])) {
            $longitude = $geoData['features'][0]['geometry']['coordinates'][0];
            $latitude = $geoData['features'][0]['geometry']['coordinates'][1];
        }

        // Insert SQL
        $sql = "INSERT INTO asilos (cnpj, nome, endereco, cidade, estado, telefone, email, senha, latitude, longitude)
                VALUES (:cnpj, :nome, :endereco, :cidade, :estado, :telefone, :email, :senha, :latitude, :longitude)";
        $stmt = $this->conn->prepare($sql);

        $hashSenha = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $stmt->bindParam(":cnpj", $dados['cnpj']);
        $stmt->bindParam(":nome", $dados['nome']);
        $stmt->bindParam(":endereco", $dados['endereco']);
        $stmt->bindParam(":cidade", $dados['cidade']);
        $stmt->bindParam(":estado", $dados['estado']);
        $stmt->bindParam(":telefone", $dados['telefone']);
        $stmt->bindParam(":email", $dados['email']);
        $stmt->bindParam(":senha", $hashSenha);
        $stmt->bindParam(":latitude", $latitude);
        $stmt->bindParam(":longitude", $longitude);

        if ($stmt->execute()) {
            return ["status" => 201, "message" => "Asilo cadastrado com sucesso."];
        } else {
            return ["status" => 500, "message" => "Erro ao cadastrar asilo."];
        }
    }
}

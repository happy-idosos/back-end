<?php
require_once __DIR__ . '/connection.php';

class ConfigController {
    public function testarConexaoDB() {
        global $conn; // ✅ Use a conexão existente
        
        try {
            // Testa a conexão executando uma query simples
            $stmt = $conn->query("SELECT 1");
            echo json_encode(["success" => true, "message" => "Conexão com o banco de dados estabelecida com sucesso!"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Erro ao conectar ao banco de dados: " . $e->getMessage()]);
        }
    }
}
?>
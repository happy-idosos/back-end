<?php

require_once __DIR__ . '/../config/connection.php';

class ListagemController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function listarUsuarios()
    {
        try {
            $query = "SELECT id_usuario, nome, email, telefone, data_nascimento FROM usuarios";
            $stmt = $this->conn->query($query);

            $usuarios = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usuarios[] = $row;
            }

            return [
                "status" => 200,
                "message" => "Usuários listados com sucesso.",
                "data" => $usuarios
            ];
        } catch (Exception $e) {
            return [
                "status" => 500,
                "message" => "Erro ao listar usuários.",
                "error" => $e->getMessage()
            ];
        }
    }

    public function listarAsilos()
    {
        try {
            $query = "SELECT id_asilo, nome, email, telefone, endereco FROM asilos";
            $stmt = $this->conn->query($query);

            $asilos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $asilos[] = $row;
            }

            return [
                "status" => 200,
                "message" => "Asilos listados com sucesso.",
                "data" => $asilos
            ];
        } catch (Exception $e) {
            return [
                "status" => 500,
                "message" => "Erro ao listar asilos.",
                "error" => $e->getMessage()
            ];
        }
    }
}

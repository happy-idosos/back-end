<?php
class EventoController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Criação de evento (apenas asilo)
    public function criarEvento($id_asilo, $titulo, $descricao, $data_evento)
    {
        if (!$this->isAsilo($id_asilo)) {
            return ['status' => 403, 'message' => 'Somente asilos podem criar eventos'];
        }

        if (empty($titulo) || empty($data_evento)) {
            return ['status' => 400, 'message' => 'Título e data são obrigatórios'];
        }

        try {
            $sql = "INSERT INTO eventos (titulo, descricao, data_evento, id_asilo)
                    VALUES (:titulo,:descricao,:data_evento,:id_asilo)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':data_evento', $data_evento);
            $stmt->bindParam(':id_asilo', $id_asilo);
            $stmt->execute();

            return ['status' => 201, 'message' => 'Evento criado com sucesso', 'id_evento' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    // Listar todos eventos
    public function listarEventos()
    {
        $sql = "SELECT e.*, a.nome AS nome_asilo 
                FROM eventos e
                JOIN asilos a ON e.id_asilo = a.id_asilo
                ORDER BY e.data_evento ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['status' => 200, 'eventos' => $eventos];
    }

    // Verifica se o ID é de um asilo
    private function isAsilo($id_asilo)
    {
        $sql = "SELECT id_asilo FROM asilos WHERE id_asilo = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id_asilo);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}

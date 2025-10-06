<?php
require_once __DIR__ . '/../config/connection.php';

class ParticipacaoController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Voluntário participa de um evento
     */
    public function participarEvento($id_usuario, $id_evento)
    {
        if (empty($id_usuario) || empty($id_evento)) {
            return ['status' => 400, 'message' => 'ID do usuário e ID do evento são obrigatórios'];
        }

        // Verifica se usuário existe
        if (!$this->existeUsuario($id_usuario)) {
            return ['status' => 404, 'message' => 'Usuário não encontrado'];
        }

        // Verifica se evento existe
        if (!$this->existeEvento($id_evento)) {
            return ['status' => 404, 'message' => 'Evento não encontrado'];
        }

        // Verifica se já está inscrito
        if ($this->jaParticipa($id_usuario, $id_evento)) {
            return ['status' => 409, 'message' => 'Usuário já inscrito neste evento'];
        }

        try {
            $sql = "INSERT INTO participacoes (id_usuario, id_evento) VALUES (:id_usuario, :id_evento)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_evento', $id_evento);
            $stmt->execute();

            return [
                'status' => 201,
                'message' => 'Inscrição realizada com sucesso',
                'data' => [
                    'id_participacao' => $this->conn->lastInsertId(),
                    'id_usuario' => $id_usuario,
                    'id_evento' => $id_evento
                ]
            ];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao registrar participação', 'error' => $e->getMessage()];
        }
    }

    /**
     * Lista todos os eventos em que o usuário está inscrito
     */
    public function listarParticipacoes($id_usuario)
    {
        if (empty($id_usuario)) {
            return ['status' => 400, 'message' => 'ID do usuário é obrigatório'];
        }

        try {
            $sql = "SELECT e.id_evento, e.titulo, e.descricao, e.data_evento, a.nome AS nome_asilo
                    FROM participacoes p
                    JOIN eventos e ON p.id_evento = e.id_evento
                    JOIN asilos a ON e.id_asilo = a.id_asilo
                    WHERE p.id_usuario = :id_usuario
                    ORDER BY e.data_evento ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();

            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 200, 'participacoes' => $eventos];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao listar participações', 'error' => $e->getMessage()];
        }
    }

    // -------- Métodos auxiliares internos --------
    private function existeUsuario($id_usuario)
    {
        $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id_usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function existeEvento($id_evento)
    {
        $sql = "SELECT id_evento FROM eventos WHERE id_evento = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id_evento);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function jaParticipa($id_usuario, $id_evento)
    {
        $sql = "SELECT id_participacao FROM participacoes WHERE id_usuario = :u AND id_evento = :e";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':u', $id_usuario);
        $stmt->bindParam(':e', $id_evento);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}

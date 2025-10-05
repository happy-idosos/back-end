<?php
class ParticipacaoController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Participar de evento (apenas usuário/voluntário)
    public function participarEvento($id_usuario, $id_evento)
    {
        if (!$this->isUsuario($id_usuario)) {
            return ['status' => 403, 'message' => 'Somente usuários podem participar'];
        }

        // Verifica se evento existe
        $stmtEvento = $this->conn->prepare("SELECT * FROM eventos WHERE id_evento=:id_evento");
        $stmtEvento->bindParam(':id_evento', $id_evento);
        $stmtEvento->execute();
        if ($stmtEvento->rowCount() == 0) {
            return ['status' => 404, 'message' => 'Evento não encontrado'];
        }

        // Verifica se já está inscrito
        $stmtCheck = $this->conn->prepare("SELECT * FROM participacoes WHERE id_usuario=:id_usuario AND id_evento=:id_evento");
        $stmtCheck->bindParam(':id_usuario', $id_usuario);
        $stmtCheck->bindParam(':id_evento', $id_evento);
        $stmtCheck->execute();
        if ($stmtCheck->rowCount() > 0) {
            return ['status' => 400, 'message' => 'Usuário já inscrito nesse evento'];
        }

        // Insere participação
        $stmt = $this->conn->prepare("INSERT INTO participacoes (id_usuario,id_evento) VALUES (:id_usuario,:id_evento)");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':id_evento', $id_evento);
        $stmt->execute();

        return ['status' => 201, 'message' => 'Inscrição realizada com sucesso'];
    }

    // Verifica se o ID é de usuário
    private function isUsuario($id_usuario)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario=:id");
        $stmt->bindParam(':id', $id_usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Listar eventos que usuário participa
    public function listarParticipacoes($id_usuario)
    {
        $stmt = $this->conn->prepare("
            SELECT e.*, a.nome AS nome_asilo
            FROM participacoes p
            JOIN eventos e ON p.id_evento = e.id_evento
            JOIN asilos a ON e.id_asilo = a.id_asilo
            WHERE p.id_usuario = :id_usuario
            ORDER BY e.data_evento ASC
        ");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['status' => 200, 'eventos' => $eventos];
    }
}

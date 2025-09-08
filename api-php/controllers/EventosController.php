<?php
require_once __DIR__ . '/../config/connection.php';

class EventosController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Listar eventos
    public function listarEventos()
    {
        $stmt = $this->conn->query("SELECT e.*, a.nome AS asilo_nome FROM eventos e JOIN asilos a ON e.id_asilo = a.id_asilo ORDER BY data_evento ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalhes de um evento
    public function verEvento($id_evento)
    {
        $stmt = $this->conn->prepare("SELECT e.*, a.nome AS asilo_nome FROM eventos e JOIN asilos a ON e.id_asilo = a.id_asilo WHERE e.id_evento = :id");
        $stmt->execute([':id' => $id_evento]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Criar evento (somente Asilo)
    public function criarEvento($id_asilo, $titulo, $descricao, $data_evento)
    {
        $stmt = $this->conn->prepare("INSERT INTO eventos (id_asilo, titulo, descricao, data_evento) VALUES (:id_asilo, :titulo, :descricao, :data_evento)");
        $stmt->execute([
            ':id_asilo' => $id_asilo,
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':data_evento' => $data_evento
        ]);
        return ['success' => true, 'message' => 'Evento criado com sucesso', 'id_evento' => $this->conn->lastInsertId()];
    }

    //  Participar de evento (somente usuário)
    public function participarEvento($id_usuario, $id_evento)
    {
        // Verifica se já está inscrito
        $check = $this->conn->prepare("SELECT * FROM participacoes WHERE id_usuario = :id_usuario AND id_evento = :id_evento");
        $check->execute([':id_usuario' => $id_usuario, ':id_evento' => $id_evento]);
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Usuário já inscrito neste evento.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO participacoes (id_usuario, id_evento) VALUES (:id_usuario, :id_evento)");
        $stmt->execute([':id_usuario' => $id_usuario, ':id_evento' => $id_evento]);
        return ['success' => true, 'message' => 'Inscrição realizada com sucesso'];
    }

    // Listar participantes (somente Asilo dono do evento)
    public function listarParticipantes($id_evento)
    {
        $stmt = $this->conn->prepare("
            SELECT u.id_usuario, u.nome, u.email, u.telefone, p.data_inscricao 
            FROM participacoes p 
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_evento = :id_evento
        ");
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

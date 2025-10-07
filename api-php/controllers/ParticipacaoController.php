<?php
class ParticipacaoController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Participar de evento (apenas usuário autenticado)
     * CORREÇÃO: Agora recebe array em vez de objeto
     */
    public function participarEvento($user, $id_evento)
    {
        error_log("🎫 PARTICIPAÇÃO DEBUG - Usuário recebido: " . print_r($user, true));
        
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo']) || $user['tipo'] !== 'usuario') {
            error_log("🎫 PARTICIPAÇÃO DEBUG - ERRO: Tipo incorreto ou não definido");
            return ['status' => 403, 'message' => 'Somente usuários podem participar de eventos'];
        }

        // CORREÇÃO: Acessar como array
        $id_usuario = $user['id_usuario'] ?? $user['id'];
        error_log("🎫 PARTICIPAÇÃO DEBUG - ID usuário: " . $id_usuario);

        try {
            // Verifica se evento existe
            $stmtEvento = $this->conn->prepare("SELECT * FROM eventos WHERE id_evento = :id_evento");
            $stmtEvento->bindParam(':id_evento', $id_evento);
            $stmtEvento->execute();
            
            if ($stmtEvento->rowCount() == 0) {
                return ['status' => 404, 'message' => 'Evento não encontrado'];
            }

            // Verifica se já está inscrito
            $stmtCheck = $this->conn->prepare(
                "SELECT * FROM participacoes WHERE id_usuario = :id_usuario AND id_evento = :id_evento"
            );
            $stmtCheck->bindParam(':id_usuario', $id_usuario);
            $stmtCheck->bindParam(':id_evento', $id_evento);
            $stmtCheck->execute();
            
            if ($stmtCheck->rowCount() > 0) {
                return ['status' => 400, 'message' => 'Você já está inscrito neste evento'];
            }

            // Insere participação
            $stmt = $this->conn->prepare(
                "INSERT INTO participacoes (id_usuario, id_evento) VALUES (:id_usuario, :id_evento)"
            );
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_evento', $id_evento);
            $stmt->execute();

            return ['status' => 201, 'message' => 'Inscrição realizada com sucesso'];
        } catch (PDOException $e) {
            error_log("🎫 PARTICIPAÇÃO DEBUG - Erro PDO: " . $e->getMessage());
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cancelar participação em evento
     */
    public function cancelarParticipacao($user, $id_evento)
    {
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo']) || $user['tipo'] !== 'usuario') {
            return ['status' => 403, 'message' => 'Somente usuários podem cancelar participações'];
        }

        // CORREÇÃO: Acessar como array
        $id_usuario = $user['id_usuario'] ?? $user['id'];

        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM participacoes WHERE id_usuario = :id_usuario AND id_evento = :id_evento"
            );
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_evento', $id_evento);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                return ['status' => 404, 'message' => 'Participação não encontrada'];
            }

            return ['status' => 200, 'message' => 'Participação cancelada com sucesso'];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar eventos que o usuário autenticado participa
     */
    public function listarMinhasParticipacoes($user)
    {
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo']) || $user['tipo'] !== 'usuario') {
            return ['status' => 403, 'message' => 'Apenas usuários podem listar suas participações'];
        }

        // CORREÇÃO: Acessar como array
        $id_usuario = $user['id_usuario'] ?? $user['id'];

        try {
            $stmt = $this->conn->prepare("
                SELECT e.*, a.nome AS nome_asilo, a.email AS email_asilo
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
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar participantes de um evento (para asilos)
     */
    public function listarParticipantes($user, $id_evento)
    {
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo']) || $user['tipo'] !== 'asilo') {
            return ['status' => 403, 'message' => 'Apenas asilos podem ver participantes'];
        }

        try {
            // Verifica se o evento pertence ao asilo
            $stmtEvento = $this->conn->prepare(
                "SELECT * FROM eventos WHERE id_evento = :id_evento AND id_asilo = :id_asilo"
            );
            $stmtEvento->bindParam(':id_evento', $id_evento);
            
            // CORREÇÃO: Acessar como array
            $id_asilo = $user['id_asilo'] ?? $user['id'];
            $stmtEvento->bindParam(':id_asilo', $id_asilo);
            $stmtEvento->execute();

            if ($stmtEvento->rowCount() == 0) {
                return ['status' => 404, 'message' => 'Evento não encontrado ou não pertence a você'];
            }

            // Lista participantes
            $stmt = $this->conn->prepare("
                SELECT u.id_usuario, u.nome, u.email
                FROM participacoes p
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_evento = :id_evento
                ORDER BY u.nome ASC
            ");
            $stmt->bindParam(':id_evento', $id_evento);
            $stmt->execute();
            $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['status' => 200, 'participantes' => $participantes];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
?>
<?php
class EventoController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Criação de evento (apenas asilo autenticado)
     * CORREÇÃO: Agora recebe array em vez de objeto
     */
    public function criarEvento($user, $titulo, $descricao, $data_evento)
    {
        error_log("🎪 EVENTO DEBUG - Usuário recebido: " . print_r($user, true));
        error_log("🎪 EVENTO DEBUG - Tipo do usuário: " . ($user['tipo'] ?? 'NÃO DEFINIDO'));
        
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo']) || $user['tipo'] !== 'asilo') {
            error_log("🎪 EVENTO DEBUG - ERRO: Tipo incorreto ou não definido");
            return ['status' => 403, 'message' => 'Somente asilos podem criar eventos'];
        }

        if (empty($titulo) || empty($data_evento)) {
            return ['status' => 400, 'message' => 'Título e data são obrigatórios'];
        }

        try {
            $sql = "INSERT INTO eventos (titulo, descricao, data_evento, id_asilo)
                    VALUES (:titulo, :descricao, :data_evento, :id_asilo)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':data_evento', $data_evento);
            
            // CORREÇÃO: Acessar como array
            $id_asilo = $user['id_asilo'] ?? $user['id'];
            $stmt->bindParam(':id_asilo', $id_asilo);
            
            error_log("🎪 EVENTO DEBUG - Inserindo evento para asilo ID: " . $id_asilo);
            
            $stmt->execute();

            return [
                'status' => 201, 
                'message' => 'Evento criado com sucesso', 
                'id_evento' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("🎪 EVENTO DEBUG - Erro PDO: " . $e->getMessage());
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar todos eventos (público)
     */
    public function listarEventos()
    {
        try {
            $sql = "SELECT e.*, a.nome AS nome_asilo 
                    FROM eventos e
                    JOIN asilos a ON e.id_asilo = a.id_asilo
                    ORDER BY e.data_evento ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 200, 'eventos' => $eventos];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Buscar evento por ID
     */
    public function buscarEvento($id_evento)
    {
        try {
            $sql = "SELECT e.*, a.nome AS nome_asilo, a.email AS email_asilo
                    FROM eventos e
                    JOIN asilos a ON e.id_asilo = a.id_asilo
                    WHERE e.id_evento = :id_evento";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_evento', $id_evento);
            $stmt->execute();
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$evento) {
                return ['status' => 404, 'message' => 'Evento não encontrado'];
            }

            return ['status' => 200, 'evento' => $evento];
        } catch (PDOException $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
?>
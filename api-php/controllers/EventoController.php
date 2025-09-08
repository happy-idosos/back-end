<?php
class EventoController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function criar($data)
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO eventos (titulo, descricao, data_evento, id_asilo)
                VALUES (:titulo, :descricao, :data_evento, :id_asilo)
            ");
            $stmt->execute([
                ':titulo' => $data['titulo'],
                ':descricao' => $data['descricao'],
                ':data_evento' => $data['data_evento'],
                ':id_asilo' => $data['id_asilo']
            ]);

            return ["status" => 201, "message" => "Evento criado com sucesso"];
        } catch (Exception $e) {
            return ["status" => 500, "message" => $e->getMessage()];
        }
    }

    public function listar()
    {
        $stmt = $this->conn->query("SELECT * FROM eventos ORDER BY data_evento ASC");
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ["status" => 200, "eventos" => $eventos];
    }

    public function detalhar($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM eventos WHERE id_evento = :id");
        $stmt->execute([':id' => $id]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evento) {
            return ["status" => 200, "evento" => $evento];
        } else {
            return ["status" => 404, "message" => "Evento não encontrado"];
        }
    }
}

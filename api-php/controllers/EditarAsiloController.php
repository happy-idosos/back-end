<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class EditarAsiloController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Edita perfil básico do asilo (dados do cadastro)
     */
    public function editarPerfilAsilo($input) {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'asilo') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas asilos podem acessar'];
        }

        $id_asilo = $user['id_asilo'];
        $errors = [];

        // Validações
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        if (isset($input['cnpj']) && !$this->validarCNPJ($input['cnpj'])) {
            $errors[] = 'CNPJ inválido';
        }

        if (!empty($errors)) {
            return ['status' => 400, 'errors' => $errors];
        }

        try {
            $this->conn->beginTransaction();

            // Campos do cadastro (UPDATE) - CORRIGIDO com campos reais
            $camposPermitidos = ['cnpj', 'nome', 'telefone', 'email', 'endereco', 'cidade', 'estado', 'cep', 'capacidade'];
            $updates = [];
            $params = [':id_asilo' => $id_asilo];

            foreach ($camposPermitidos as $campo) {
                if (isset($input[$campo])) {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $input[$campo];
                }
            }

            if (!empty($updates)) {
                $sql = "UPDATE asilos SET " . implode(', ', $updates) . ", atualizado_em = NOW() WHERE id_asilo = :id_asilo";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
            }

            $this->conn->commit();

            $asiloAtualizado = $this->buscarAsilo($id_asilo);
            return [
                'status' => 200,
                'message' => 'Perfil do asilo atualizado com sucesso',
                'data' => $asiloAtualizado
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cnpj') !== false) {
                    return ['status' => 400, 'message' => 'CNPJ já cadastrado'];
                }
                if (strpos($e->getMessage(), 'email') !== false) {
                    return ['status' => 400, 'message' => 'Email já cadastrado'];
                }
            }
            
            return ['status' => 500, 'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
        }
    }

    /**
     * Adiciona ou atualiza campos do perfil asilo (estão na própria tabela asilos)
     */
    public function editarPerfilAsiloDetalhes($input) {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'asilo') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas asilos podem acessar'];
        }

        $id_asilo = $user['id_asilo'];

        try {
            $this->conn->beginTransaction();

            // Campos do perfil asilo - CORRIGIDO: estão na tabela asilos
            $camposPermitidos = ['descricao', 'logo'];
            $updates = [];
            $params = [':id_asilo' => $id_asilo];

            foreach ($camposPermitidos as $campo) {
                if (isset($input[$campo])) {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $input[$campo];
                }
            }

            if (empty($updates)) {
                return ['status' => 400, 'message' => 'Nenhum campo válido para atualização'];
            }

            // SEMPRE UPDATE - campos estão na tabela asilos
            $sql = "UPDATE asilos SET " . implode(', ', $updates) . ", atualizado_em = NOW() WHERE id_asilo = :id_asilo";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->conn->commit();

            $perfilAtualizado = $this->buscarPerfilAsilo($id_asilo);
            return [
                'status' => 200,
                'message' => 'Perfil do asilo atualizado com sucesso',
                'data' => $perfilAtualizado
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 500, 'message' => 'Erro ao atualizar perfil do asilo: ' . $e->getMessage()];
        }
    }

    /**
     * Busca perfil completo do asilo
     */
    public function buscarPerfilAsilo() {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'asilo') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas asilos podem acessar'];
        }

        $id_asilo = $user['id_asilo'];

        try {
            $asilo = $this->buscarAsilo($id_asilo);
            $perfilAsilo = $this->buscarPerfilAsiloDetalhes($id_asilo);

            return [
                'status' => 200,
                'data' => [
                    'asilo' => $asilo,
                    'perfil_asilo' => $perfilAsilo
                ]
            ];

        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao buscar perfil do asilo: ' . $e->getMessage()];
        }
    }

    private function buscarAsilo($id_asilo) {
        $stmt = $this->conn->prepare("
            SELECT id_asilo, cnpj, nome, telefone, email, endereco, cidade, estado, cep, capacidade, criado_em, atualizado_em 
            FROM asilos 
            WHERE id_asilo = :id_asilo
        ");
        $stmt->bindParam(':id_asilo', $id_asilo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function buscarPerfilAsiloDetalhes($id_asilo) {
        $stmt = $this->conn->prepare("
            SELECT descricao, logo
            FROM asilos 
            WHERE id_asilo = :id_asilo
        ");
        $stmt->bindParam(':id_asilo', $id_asilo);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return strlen($cnpj) === 14;
    }
}
?>
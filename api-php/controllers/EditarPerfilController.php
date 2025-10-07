<?php

class EditarPerfilController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Edita o perfil básico do voluntário
     */
    public function editarPerfil($input) {
        // Verifica autenticação
        $user = AuthMiddleware::requireType('usuario');
        
        if (!isset($user['id_usuario'])) {
            return ['status' => 401, 'message' => 'Não autorizado - ID de usuário não encontrado'];
        }

        $id_usuario = $user['id_usuario'];
        $errors = [];

        // Validações básicas
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }

        if (isset($input['cpf']) && !$this->validarCPF($input['cpf'])) {
            $errors[] = 'CPF inválido';
        }

        if (isset($input['data_nascimento']) && !$this->validarDataNascimento($input['data_nascimento'])) {
            $errors[] = 'Data de nascimento inválida';
        }

        if (!empty($errors)) {
            return ['status' => 400, 'errors' => $errors];
        }

        try {
            $this->conn->beginTransaction();

            // Campos permitidos para atualização
            $camposPermitidos = ['cpf', 'nome', 'telefone', 'data_nascimento', 'email'];
            $updates = [];
            $params = [':id_usuario' => $id_usuario];

            // Constrói a query dinamicamente
            foreach ($camposPermitidos as $campo) {
                if (isset($input[$campo])) {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $input[$campo];
                }
            }

            if (!empty($updates)) {
                $sql = "UPDATE usuarios SET " . implode(', ', $updates) . ", atualizado_em = NOW() WHERE id_usuario = :id_usuario";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
            }

            $this->conn->commit();

            // Busca dados atualizados
            $usuarioAtualizado = $this->buscarUsuario($id_usuario);
            return [
                'status' => 200,
                'message' => 'Perfil atualizado com sucesso',
                'data' => $usuarioAtualizado
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            
            // Verifica se é erro de duplicidade
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'cpf') !== false) {
                    return ['status' => 400, 'message' => 'CPF já cadastrado'];
                }
                if (strpos($e->getMessage(), 'email') !== false) {
                    return ['status' => 400, 'message' => 'Email já cadastrado'];
                }
            }
            
            return ['status' => 500, 'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
        }
    }

    /**
     * Adiciona ou atualiza campos opcionais do perfil voluntário
     */
    public function editarPerfilVoluntario($input) {
        // Verifica autenticação
        $user = AuthMiddleware::requireType('usuario');
        
        if (!isset($user['id_usuario'])) {
            return ['status' => 401, 'message' => 'Não autorizado - ID de usuário não encontrado'];
        }

        $id_usuario = $user['id_usuario'];

        try {
            $this->conn->beginTransaction();

            // Verifica se já existe perfil
            $stmt = $this->conn->prepare("SELECT id_perfil FROM perfil_voluntario WHERE id_usuario = :id_usuario");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            $perfilExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            $camposPermitidos = ['habilidades', 'competencias', 'disponibilidade', 'sobre_voce', 'foto_perfil'];
            $updates = [];
            $params = [':id_usuario' => $id_usuario];

            // Constrói a query dinamicamente
            foreach ($camposPermitidos as $campo) {
                if (isset($input[$campo])) {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $input[$campo];
                }
            }

            if (empty($updates)) {
                return ['status' => 400, 'message' => 'Nenhum campo válido para atualização'];
            }

            if ($perfilExistente) {
                // Update
                $sql = "UPDATE perfil_voluntario SET " . implode(', ', $updates) . ", atualizado_em = NOW() WHERE id_usuario = :id_usuario";
            } else {
                // Insert - inclui o id_usuario
                $campos = ['id_usuario'];
                $placeholders = [':id_usuario'];
                
                foreach ($camposPermitidos as $campo) {
                    if (isset($input[$campo])) {
                        $campos[] = $campo;
                        $placeholders[] = ":$campo";
                    }
                }
                
                $sql = "INSERT INTO perfil_voluntario (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->conn->commit();

            // Busca perfil atualizado
            $perfilAtualizado = $this->buscarPerfilVoluntario($id_usuario);
            return [
                'status' => 200,
                'message' => 'Perfil voluntário atualizado com sucesso',
                'data' => $perfilAtualizado
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 500, 'message' => 'Erro ao atualizar perfil voluntário: ' . $e->getMessage()];
        }
    }

    /**
     * Busca perfil completo do usuário
     */
    public function buscarPerfil() {
        $user = AuthMiddleware::requireType('usuario');
        
        if (!isset($user['id_usuario'])) {
            return ['status' => 401, 'message' => 'Não autorizado - ID de usuário não encontrado'];
        }

        $id_usuario = $user['id_usuario'];

        try {
            $usuario = $this->buscarUsuario($id_usuario);
            $perfilVoluntario = $this->buscarPerfilVoluntario($id_usuario);

            return [
                'status' => 200,
                'data' => [
                    'usuario' => $usuario,
                    'perfil_voluntario' => $perfilVoluntario
                ]
            ];

        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao buscar perfil: ' . $e->getMessage()];
        }
    }

    /**
     * Busca dados básicos do usuário
     */
    private function buscarUsuario($id_usuario) {
        $stmt = $this->conn->prepare("
            SELECT id_usuario, cpf, nome, telefone, data_nascimento, email, criado_em, atualizado_em 
            FROM usuarios 
            WHERE id_usuario = :id_usuario
        ");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca perfil voluntário
     */
    private function buscarPerfilVoluntario($id_usuario) {
        $stmt = $this->conn->prepare("
            SELECT habilidades, competencias, disponibilidade, sobre_voce, foto_perfil, criado_em, atualizado_em 
            FROM perfil_voluntario 
            WHERE id_usuario = :id_usuario
        ");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Valida CPF
     */
    private function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Cálculo dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valida data de nascimento
     */
    private function validarDataNascimento($data) {
        $dataObj = DateTime::createFromFormat('Y-m-d', $data);
        $hoje = new DateTime();
        
        if (!$dataObj || $dataObj->format('Y-m-d') !== $data) {
            return false;
        }

        // Verifica se a data não é futura e se a pessoa tem pelo menos 16 anos
        $idade = $dataObj->diff($hoje)->y;
        return $idade >= 16 && $idade <= 120;
    }
}
?>
<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class EditarVoluntarioController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Edita o perfil básico do voluntário
     */
    public function editarPerfil($input) {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'usuario') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas usuários podem acessar'];
        }

        $id_usuario = $user['id_usuario'];
        $errors = [];

        // Validações
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

            // Campos do cadastro básico
            $camposPermitidos = ['cpf', 'nome', 'telefone', 'data_nascimento', 'email', 'endereco', 'cidade', 'estado', 'cep'];
            $updates = [];
            $params = [':id_usuario' => $id_usuario];

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

            $usuarioAtualizado = $this->buscarUsuario($id_usuario);
            return [
                'status' => 200,
                'message' => 'Perfil atualizado com sucesso',
                'data' => $usuarioAtualizado
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            
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
     * Adiciona ou atualiza campos do perfil voluntário
     */
    public function editarPerfilVoluntario($input) {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'usuario') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas usuários podem acessar'];
        }

        $id_usuario = $user['id_usuario'];

        try {
            $this->conn->beginTransaction();

            // Campos do perfil voluntário
            $camposPermitidos = ['habilidades', 'disponibilidade', 'sobre_voce', 'foto_perfil'];
            $updates = [];
            $params = [':id_usuario' => $id_usuario];

            foreach ($camposPermitidos as $campo) {
                if (isset($input[$campo])) {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $input[$campo];
                }
            }

            if (empty($updates)) {
                return ['status' => 400, 'message' => 'Nenhum campo válido para atualização'];
            }

            $sql = "UPDATE usuarios SET " . implode(', ', $updates) . ", atualizado_em = NOW() WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->conn->commit();

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
     * Upload de foto de perfil do voluntário
     */
    public function uploadFotoPerfil($foto) {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'usuario') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas usuários podem acessar'];
        }

        $id_usuario = $user['id_usuario'];

        if (!$foto || $foto['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 400, 'message' => 'Nenhuma foto enviada ou erro no upload'];
        }

        // Validações da imagem
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($foto['type'], $allowedTypes)) {
            return ['status' => 400, 'message' => 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP'];
        }

        if ($foto['size'] > $maxSize) {
            return ['status' => 400, 'message' => 'Arquivo muito grande. Tamanho máximo: 5MB'];
        }

        try {
            // Criar diretório de uploads se não existir
            $uploadDir = __DIR__ . '/../../uploads/voluntarios/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Gerar nome único para o arquivo
            $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $nomeArquivo = 'voluntario_' . $id_usuario . '_' . uniqid() . '.' . $extensao;
            $caminhoCompleto = $uploadDir . $nomeArquivo;

            // Mover arquivo
            if (!move_uploaded_file($foto['tmp_name'], $caminhoCompleto)) {
                return ['status' => 500, 'message' => 'Erro ao salvar a imagem'];
            }

            // Caminho relativo para salvar no banco
            $caminhoRelativo = '/uploads/voluntarios/' . $nomeArquivo;

            // Atualizar no banco
            $stmt = $this->conn->prepare("
                UPDATE usuarios 
                SET foto_perfil = :foto_perfil, atualizado_em = NOW() 
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindParam(':foto_perfil', $caminhoRelativo);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();

            return [
                'status' => 200,
                'message' => 'Foto de perfil atualizada com sucesso',
                'data' => [
                    'foto_perfil' => $caminhoRelativo
                ]
            ];

        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao atualizar foto de perfil: ' . $e->getMessage()];
        }
    }

    /**
     * Busca perfil completo do voluntário
     */
    public function buscarPerfil() {
        $user = AuthMiddleware::verifyAuth();
        if (!$user || $user['tipo'] !== 'usuario') {
            return ['status' => 401, 'message' => 'Não autorizado - Apenas usuários podem acessar'];
        }

        $id_usuario = $user['id_usuario'];

        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    id_usuario, cpf, nome, telefone, data_nascimento, email,
                    endereco, cidade, estado, cep,
                    habilidades, disponibilidade, sobre_voce, foto_perfil,
                    criado_em, atualizado_em
                FROM usuarios 
                WHERE id_usuario = :id_usuario
            ");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$perfil) {
                return ['status' => 404, 'message' => 'Perfil não encontrado'];
            }

            return [
                'status' => 200,
                'data' => $perfil
            ];

        } catch (PDOException $e) {
            return ['status' => 500, 'message' => 'Erro ao buscar perfil: ' . $e->getMessage()];
        }
    }

    private function buscarUsuario($id_usuario) {
        $stmt = $this->conn->prepare("
            SELECT id_usuario, cpf, nome, telefone, data_nascimento, email, 
                   endereco, cidade, estado, cep, criado_em, atualizado_em 
            FROM usuarios 
            WHERE id_usuario = :id_usuario
        ");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function buscarPerfilVoluntario($id_usuario) {
        $stmt = $this->conn->prepare("
            SELECT habilidades, disponibilidade, sobre_voce, foto_perfil
            FROM usuarios 
            WHERE id_usuario = :id_usuario
        ");
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    private function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

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

    private function validarDataNascimento($data) {
        $dataObj = DateTime::createFromFormat('Y-m-d', $data);
        $hoje = new DateTime();
        
        if (!$dataObj || $dataObj->format('Y-m-d') !== $data) {
            return false;
        }

        $idade = $dataObj->diff($hoje)->y;
        return $idade >= 16 && $idade <= 120;
    }
}
?>
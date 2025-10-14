<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class EditarPerfilController
{
    private $conn;
    private $uploadDir = __DIR__ . '/../uploads/perfis/';

    public function __construct($db)
    {
        $this->conn = $db;

        // Cria diretório de uploads se não existir
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function editarPerfil($dados, $arquivos = null)
    {
        try {
            $user = AuthMiddleware::requireAuth();
            error_log(" DEBUG - Usuário autenticado: " . print_r($user, true));
            error_log(" DEBUG - Dados recebidos: " . print_r($dados, true));
            error_log(" DEBUG - Arquivos recebidos: " . print_r($arquivos, true));

            // Processa upload de foto se existir
            $fotoNome = null;
            if (!empty($arquivos['foto_perfil'])) {
                $fotoNome = $this->processarUploadFoto($arquivos['foto_perfil']);
                if (!$fotoNome) {
                    return ["status" => 400, "message" => "Erro no upload da foto"];
                }
            }

            // Validações básicas para campos obrigatórios
            if (empty($dados['nome']) || empty($dados['email'])) {
                return ["status" => 400, "message" => "Nome e e-mail são obrigatórios"];
            }

            if (!validarNome($dados['nome'])) {
                return ["status" => 400, "message" => "Nome inválido"];
            }

            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return ["status" => 400, "message" => "E-mail inválido"];
            }

            // Determina tabela e campos
            if ($user['tipo'] === 'usuario') {
                $sqlCheck = "SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id";
                $idField = 'id_usuario';
                $table = 'usuarios';
                $idValue = $user['id_usuario'] ?? $user['id'] ?? null;
                $campoFoto = 'foto_perfil';

                $camposPermitidos = [
                    'nome',
                    'email',
                    'telefone',
                    'data_nascimento',
                    'endereco',
                    'cidade',
                    'estado',
                    'cep',
                    'cpf',
                    'habilidades',
                    'disponibilidade',
                    'sobre_voce'
                ];

                if (!empty($dados['cpf']) && !validarCPF($dados['cpf'])) {
                    return ["status" => 400, "message" => "CPF inválido"];
                }
            } else {
                $sqlCheck = "SELECT id_asilo FROM asilos WHERE email = :email AND id_asilo != :id";
                $idField = 'id_asilo';
                $table = 'asilos';
                $idValue = $user['id_asilo'] ?? $user['id'] ?? null;
                $campoFoto = 'logo';

                $camposPermitidos = [
                    'nome',
                    'email',
                    'telefone',
                    'endereco',
                    'cidade',
                    'estado',
                    'cep',
                    'cnpj',
                    'responsavel_legal',
                    'capacidade',
                    'tipo_instituicao',
                    'descricao',
                    'necessidades_voluntariado',
                    'site',
                    'redes_sociais'
                ];

                if (!empty($dados['cnpj']) && !validarCNPJ($dados['cnpj'])) {
                    return ["status" => 400, "message" => "CNPJ inválido"];
                }
            }

            // Verifica se o email já existe para outro usuário
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindParam(":email", $dados['email']);
            $stmtCheck->bindParam(":id", $idValue);

            if (!$stmtCheck->execute()) {
                return ["status" => 500, "message" => "Erro ao verificar email"];
            }

            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
                return ["status" => 400, "message" => "Este e-mail já está em uso por outro usuário"];
            }

            // Prepara SQL para atualização
            $setParts = [];
            $params = [':id' => $idValue];

            // Adiciona campos normais
            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo])) {
                    // Validações específicas
                    if ($campo === 'telefone' && !empty($dados[$campo]) && !validarTelefone($dados[$campo])) {
                        return ["status" => 400, "message" => "Telefone inválido"];
                    }

                    if ($campo === 'capacidade' && !empty($dados[$campo]) && !is_numeric($dados[$campo])) {
                        return ["status" => 400, "message" => "Capacidade deve ser um número"];
                    }

                    if ($campo === 'habilidades' && strlen($dados[$campo]) > 64) {
                        return ["status" => 400, "message" => "Habilidades deve ter no máximo 64 caracteres"];
                    }

                    if ($campo === 'sobre_voce' && strlen($dados[$campo]) > 128) {
                        return ["status" => 400, "message" => "Biografia deve ter no máximo 128 caracteres"];
                    }

                    $setParts[] = "$campo = :$campo";
                    $params[":$campo"] = $dados[$campo] !== '' ? $dados[$campo] : null;
                }
            }

            // Adiciona foto se foi feito upload
            if ($fotoNome) {
                $setParts[] = "$campoFoto = :foto";
                $params[':foto'] = $fotoNome;
            }

            if (empty($setParts)) {
                return ["status" => 400, "message" => "Nenhum dado válido para atualização"];
            }

            $sql = "UPDATE $table SET " . implode(', ', $setParts) . ", atualizado_em = CURRENT_TIMESTAMP WHERE $idField = :id";
            error_log(" DEBUG - SQL: " . $sql);

            $stmt = $this->conn->prepare($sql);

            if ($stmt->execute($params)) {
                $rowCount = $stmt->rowCount();
                return ["status" => 200, "message" => "Perfil atualizado com sucesso", "rows_affected" => $rowCount, "foto" => $fotoNome];
            } else {
                $errorInfo = $stmt->errorInfo();
                return ["status" => 500, "message" => "Erro ao atualizar perfil", "error" => $errorInfo[2]];
            }
        } catch (Exception $e) {
            error_log(" DEBUG - Exceção: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro interno: " . $e->getMessage()];
        }
    }

    private function processarUploadFoto($arquivo)
    {
        try {
            // Verifica se é um arquivo de imagem válido
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tamanhoMaximo = 5 * 1024 * 1024; // 5MB

            if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                error_log(" DEBUG - Erro no upload: " . $arquivo['error']);
                return null;
            }

            if ($arquivo['size'] > $tamanhoMaximo) {
                error_log(" DEBUG - Arquivo muito grande: " . $arquivo['size']);
                return null;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $tiposPermitidos)) {
                error_log(" DEBUG - Tipo de arquivo não permitido: " . $mimeType);
                return null;
            }

            // Gera nome único para o arquivo
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $nomeArquivo = uniqid() . '_' . time() . '.' . $extensao;
            $caminhoCompleto = $this->uploadDir . $nomeArquivo;

            // Move o arquivo
            if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                error_log(" DEBUG - Upload realizado: " . $nomeArquivo);
                return $nomeArquivo;
            } else {
                error_log(" DEBUG - Erro ao mover arquivo");
                return null;
            }
        } catch (Exception $e) {
            error_log(" DEBUG - Exceção no upload: " . $e->getMessage());
            return null;
        }
    }

    public function buscarPerfil()
    {
        try {
            $user = AuthMiddleware::requireAuth();

            if ($user['tipo'] === 'usuario') {
                $sql = "SELECT 
                    id_usuario as id,
                    cpf,
                    nome, 
                    telefone, 
                    data_nascimento, 
                    email, 
                    endereco, 
                    cidade, 
                    estado, 
                    cep,
                    habilidades,
                    disponibilidade,
                    sobre_voce,
                    foto_perfil,
                    CONCAT('/uploads/perfis/', foto_perfil) as foto_url
                FROM usuarios WHERE id_usuario = :id";
                $id = $user['id_usuario'] ?? $user['id'] ?? null;
            } else {
                $sql = "SELECT 
                    id_asilo as id,
                    cnpj,
                    nome, 
                    telefone, 
                    email, 
                    endereco, 
                    cidade, 
                    estado, 
                    cep,
                    responsavel_legal,
                    capacidade,
                    tipo_instituicao,
                    descricao,
                    necessidades_voluntariado,
                    site,
                    redes_sociais,
                    logo as foto_perfil,
                    CONCAT('/uploads/perfis/', logo) as foto_url
                FROM asilos WHERE id_asilo = :id";
                $id = $user['id_asilo'] ?? $user['id'] ?? null;
            }

            if (!$id) {
                return ["status" => 401, "message" => "ID do usuário não encontrado"];
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($perfil) {
                return [
                    "status" => 200,
                    "tipo" => $user['tipo'],
                    "perfil" => $perfil
                ];
            } else {
                return ["status" => 404, "message" => "Perfil não encontrado"];
            }
        } catch (Exception $e) {
            error_log(" DEBUG - Exceção ao buscar perfil: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro interno: " . $e->getMessage()];
        }
    }

    // Método específico apenas para trocar foto
    public function atualizarFoto($arquivos)
    {
        try {
            $user = AuthMiddleware::requireAuth();

            if (empty($arquivos['foto_perfil'])) {
                return ["status" => 400, "message" => "Nenhuma foto enviada"];
            }

            $fotoNome = $this->processarUploadFoto($arquivos['foto_perfil']);
            if (!$fotoNome) {
                return ["status" => 400, "message" => "Erro no upload da foto"];
            }

            // Determina tabela e campo da foto
            if ($user['tipo'] === 'usuario') {
                $table = 'usuarios';
                $idField = 'id_usuario';
                $campoFoto = 'foto_perfil';
                $idValue = $user['id_usuario'] ?? $user['id'] ?? null;
            } else {
                $table = 'asilos';
                $idField = 'id_asilo';
                $campoFoto = 'logo';
                $idValue = $user['id_asilo'] ?? $user['id'] ?? null;
            }

            $sql = "UPDATE $table SET $campoFoto = :foto, atualizado_em = CURRENT_TIMESTAMP WHERE $idField = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":foto", $fotoNome);
            $stmt->bindParam(":id", $idValue);

            if ($stmt->execute()) {
                return [
                    "status" => 200,
                    "message" => "Foto atualizada com sucesso",
                    "foto" => $fotoNome,
                    "foto_url" => "/uploads/perfis/" . $fotoNome
                ];
            } else {
                return ["status" => 500, "message" => "Erro ao atualizar foto"];
            }
        } catch (Exception $e) {
            error_log(" DEBUG - Exceção ao atualizar foto: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro interno: " . $e->getMessage()];
        }
    }
}

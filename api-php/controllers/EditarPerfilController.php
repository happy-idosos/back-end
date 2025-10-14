<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class EditarPerfilController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function editarPerfil($dados)
    {
        try {
            // Verifica autenticação com debug
            error_log("🔐 DEBUG - Iniciando edição de perfil");
            $user = AuthMiddleware::requireAuth();
            error_log("🔐 DEBUG - Usuário autenticado: " . print_r($user, true));

            // Debug dos dados recebidos
            error_log("📝 DEBUG - Dados recebidos: " . print_r($dados, true));

            // Validações básicas para campos obrigatórios
            if (empty($dados['nome']) || empty($dados['email'])) {
                error_log("❌ DEBUG - Campos obrigatórios faltando: nome=" . ($dados['nome'] ?? 'vazio') . ", email=" . ($dados['email'] ?? 'vazio'));
                return ["status" => 400, "message" => "Nome e e-mail são obrigatórios"];
            }

            if (!validarNome($dados['nome'])) {
                return ["status" => 400, "message" => "Nome inválido"];
            }

            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return ["status" => 400, "message" => "E-mail inválido"];
            }

            // Determina tabela e campos baseado no tipo de usuário
            if ($user['tipo'] === 'usuario') {
                $sqlCheck = "SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id";
                $idField = 'id_usuario';
                $table = 'usuarios';
                $idValue = $user['id_usuario'] ?? $user['id'] ?? null;

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

            error_log("🔍 DEBUG - Tipo: {$user['tipo']}, ID: {$idValue}, Tabela: {$table}");

            // Verifica se o ID foi encontrado
            if (!$idValue) {
                error_log("❌ DEBUG - ID do usuário não encontrado no token");
                return ["status" => 401, "message" => "Token inválido - ID não encontrado"];
            }

            // Verifica se o email já existe para outro usuário
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindParam(":email", $dados['email']);
            $stmtCheck->bindParam(":id", $idValue);

            if (!$stmtCheck->execute()) {
                error_log("❌ DEBUG - Erro ao verificar email: " . print_r($stmtCheck->errorInfo(), true));
                return ["status" => 500, "message" => "Erro ao verificar email"];
            }

            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
                return ["status" => 400, "message" => "Este e-mail já está em uso por outro usuário"];
            }

            // Prepara SQL para atualização
            $setParts = [];
            $params = [':id' => $idValue];

            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo])) {
                    // Validações específicas
                    if ($campo === 'telefone' && !empty($dados[$campo]) && !validarTelefone($dados[$campo])) {
                        return ["status" => 400, "message" => "Telefone inválido"];
                    }

                    if ($campo === 'capacidade' && !empty($dados[$campo]) && !is_numeric($dados[$campo])) {
                        return ["status" => 400, "message" => "Capacidade deve ser um número"];
                    }

                    // Validações de tamanho
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

            if (empty($setParts)) {
                return ["status" => 400, "message" => "Nenhum dado válido para atualização"];
            }

            $sql = "UPDATE $table SET " . implode(', ', $setParts) . ", atualizado_em = CURRENT_TIMESTAMP WHERE $idField = :id";
            error_log("📝 DEBUG - SQL: " . $sql);
            error_log("📝 DEBUG - Params: " . print_r($params, true));

            $stmt = $this->conn->prepare($sql);

            if ($stmt->execute($params)) {
                $rowCount = $stmt->rowCount();
                error_log("✅ DEBUG - Perfil atualizado com sucesso. Linhas afetadas: " . $rowCount);
                return ["status" => 200, "message" => "Perfil atualizado com sucesso", "rows_affected" => $rowCount];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ DEBUG - Erro SQL: " . print_r($errorInfo, true));
                return ["status" => 500, "message" => "Erro ao atualizar perfil", "error" => $errorInfo[2]];
            }
        } catch (Exception $e) {
            error_log("💥 DEBUG - Exceção: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro interno: " . $e->getMessage()];
        }
    }

    public function buscarPerfil()
    {
        try {
            $user = AuthMiddleware::requireAuth();
            error_log("🔍 DEBUG - Buscar perfil para: " . print_r($user, true));

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
                    foto_perfil
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
                    logo as foto_perfil
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
            error_log("💥 DEBUG - Exceção ao buscar perfil: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro interno: " . $e->getMessage()];
        }
    }
}

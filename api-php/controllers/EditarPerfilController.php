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
        // Verifica autenticação
        $user = AuthMiddleware::requireAuth();
        
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

        // Verifica se o email já existe para outro usuário
        if ($user['tipo'] === 'usuario') {
            $sqlCheck = "SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id";
            $idField = 'id_usuario';
            $table = 'usuarios';
            $idValue = $user['id_usuario'];
            
            // Todos os campos do usuário em uma única operação
            $camposPermitidos = [
                'nome', 'email', 'telefone', 'data_nascimento', 'endereco', 
                'cidade', 'estado', 'cep', 'cpf', 'habilidades', 
                'disponibilidade', 'sobre_voce'
            ];
            
            // Valida CPF se fornecido
            if (!empty($dados['cpf'])) {
                if (!validarCPF($dados['cpf'])) {
                    return ["status" => 400, "message" => "CPF inválido"];
                }
            }
            
        } else {
            $sqlCheck = "SELECT id_asilo FROM asilos WHERE email = :email AND id_asilo != :id";
            $idField = 'id_asilo';
            $table = 'asilos';
            $idValue = $user['id_asilo'];
            
            // Todos os campos do asilo em uma única operação
            $camposPermitidos = [
                'nome', 'email', 'telefone', 'endereco', 'cidade', 'estado', 'cep',
                'cnpj', 'responsavel_legal', 'capacidade', 'tipo_instituicao', 
                'descricao', 'necessidades_voluntariado', 'site', 'redes_sociais'
            ];
            
            // Valida CNPJ se fornecido
            if (!empty($dados['cnpj'])) {
                if (!validarCNPJ($dados['cnpj'])) {
                    return ["status" => 400, "message" => "CNPJ inválido"];
                }
            }
        }

        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(":email", $dados['email']);
        $stmtCheck->bindParam(":id", $idValue);
        $stmtCheck->execute();

        if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            return ["status" => 400, "message" => "Este e-mail já está em uso por outro usuário"];
        }

        // Prepara SQL para atualização
        $setParts = [];
        $params = [':id' => $idValue];

        foreach ($camposPermitidos as $campo) {
            if (isset($dados[$campo])) {
                // Validações específicas por campo
                if ($campo === 'nome' && !validarNome($dados[$campo])) {
                    return ["status" => 400, "message" => "Nome inválido"];
                }
                
                if ($campo === 'telefone' && !empty($dados[$campo]) && !validarTelefone($dados[$campo])) {
                    return ["status" => 400, "message" => "Telefone inválido"];
                }
                
                if ($campo === 'capacidade' && !empty($dados[$campo]) && !is_numeric($dados[$campo])) {
                    return ["status" => 400, "message" => "Capacidade deve ser um número"];
                }
                
                // Validações de tamanho para campos de texto
                if ($campo === 'habilidades' && strlen($dados[$campo]) > 64) {
                    return ["status" => 400, "message" => "Habilidades deve ter no máximo 64 caracteres"];
                }
                
                if ($campo === 'sobre_voce' && strlen($dados[$campo]) > 128) {
                    return ["status" => 400, "message" => "Biografia deve ter no máximo 128 caracteres"];
                }
                
                if ($campo === 'necessidades_voluntariado' && strlen($dados[$campo]) > 255) {
                    return ["status" => 400, "message" => "Necessidades de voluntariado deve ter no máximo 255 caracteres"];
                }
                
                $setParts[] = "$campo = :$campo";
                $params[":$campo"] = $dados[$campo] !== '' ? $dados[$campo] : null;
            }
        }

        if (empty($setParts)) {
            return ["status" => 400, "message" => "Nenhum dado válido para atualização"];
        }

        $sql = "UPDATE $table SET " . implode(', ', $setParts) . ", atualizado_em = CURRENT_TIMESTAMP WHERE $idField = :id";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            return ["status" => 200, "message" => "Perfil atualizado com sucesso"];
        } else {
            error_log("Erro ao atualizar perfil: " . print_r($stmt->errorInfo(), true));
            return ["status" => 500, "message" => "Erro ao atualizar perfil"];
        }
    }

    public function buscarPerfil()
    {
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
                foto_perfil
            FROM usuarios WHERE id_usuario = :id";
            $id = $user['id_usuario'];
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
            $id = $user['id_asilo'];
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
    }
}
?>
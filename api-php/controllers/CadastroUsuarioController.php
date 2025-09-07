<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';

class CadastroUsuarioController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function cadastrar($dados)
    {
        if (empty($dados['cpf']) || empty($dados['nome']) || empty($dados['email']) || empty($dados['senha'])) {
            return ["status" => 400, "message" => "Dados obrigatórios não preenchidos."];
        }

        if (!validarCPF($dados['cpf'])) {
            return ["status" => 400, "message" => "CPF inválido."];
        }

        if (!validarNome($dados['nome'])) {
            return ["status" => 400, "message" => "Nome inválido."];
        }

        if (!validarTelefone($dados['telefone'])) {
            return ["status" => 400, "message" => "Telefone inválido."];
        }
        if (!validarSenha($dados['senha'])) {
            return ["status" => 400, "message" => "Senha não atende aos requisitos de segurança."];
        }

        $sql = "INSERT INTO usuarios (cpf, nome, telefone, data_nascimento, email, senha)
                VALUES (:cpf, :nome, :telefone, :data_nascimento, :email, :senha)";
        $stmt = $this->conn->prepare($sql);

        $hashSenha = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $stmt->bindParam(":cpf", $dados['cpf']);
        $stmt->bindParam(":nome", $dados['nome']);
        $stmt->bindParam(":telefone", $dados['telefone']);
        $stmt->bindParam(":data_nascimento", $dados['data_nascimento']);
        $stmt->bindParam(":email", $dados['email']);
        $stmt->bindParam(":senha", $hashSenha);

        if ($stmt->execute()) {
            return ["status" => 201, "message" => "Usuário cadastrado com sucesso."];
        } else {
            return ["status" => 500, "message" => "Erro ao cadastrar usuário."];
        }
    }
}

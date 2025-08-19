<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';

class CadastroAsiloController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function cadastrar($dados)
    {
        if (empty($dados['cnpj']) || empty($dados['nome']) || empty($dados['email']) || empty($dados['senha'])) {
            return ["status" => 400, "message" => "Dados obrigatórios não preenchidos."];
        }

        if (!validarCNPJ($dados['cnpj'])) {
            return ["status" => 400, "message" => "CNPJ inválido."];
        }

        $sql = "INSERT INTO asilos (cnpj, nome, endereco, telefone, email, senha)
                VALUES (:cnpj, :nome, :endereco, :telefone, :email, :senha)";
        $stmt = $this->conn->prepare($sql);

        $hashSenha = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $stmt->bindParam(":cnpj", $dados['cnpj']);
        $stmt->bindParam(":nome", $dados['nome']);
        $stmt->bindParam(":endereco", $dados['endereco']);
        $stmt->bindParam(":telefone", $dados['telefone']);
        $stmt->bindParam(":email", $dados['email']);
        $stmt->bindParam(":senha", $hashSenha);

        if ($stmt->execute()) {
            return ["status" => 201, "message" => "Asilo cadastrado com sucesso."];
        } else {
            return ["status" => 500, "message" => "Erro ao cadastrar asilo."];
        }
    }
}

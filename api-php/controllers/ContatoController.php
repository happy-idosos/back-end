<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';

class ContatoController
{
    private $conn;
    private $uploadDir = __DIR__ . '/../uploads/'; // pasta para arquivos enviados

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function enviar($dados, $arquivo = null)
    {
        if (empty($dados['nome']) || empty($dados['email']) || empty($dados['telefone']) || empty($dados['mensagem'])) {
            return ["status" => 400, "message" => "Todos os campos obrigatórios devem ser preenchidos."];
        }

        if (!validarNome($dados['nome'])) {
            return ["status" => 400, "message" => "Nome inválido."];
        }

        if (!validarEmail($dados['email'])) {
            return ["status" => 400, "message" => "Email inválido."];
        }

        if (!validarTelefone($dados['telefone'])) {
            return ["status" => 400, "message" => "Telefone inválido."];
        }

        // Upload do arquivo
        $arquivoNome = null;
        if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
            $arquivoNome = uniqid() . '_' . basename($arquivo['name']);
            $destino = $this->uploadDir . $arquivoNome;
            if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                return ["status" => 500, "message" => "Erro ao enviar arquivo."];
            }
        }

        $sql = "INSERT INTO contatos (nome, email, telefone, mensagem, arquivo)
                VALUES (:nome, :email, :telefone, :mensagem, :arquivo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':mensagem', $dados['mensagem']);
        $stmt->bindParam(':arquivo', $arquivoNome);

        if ($stmt->execute()) {
            return ["status" => 201, "message" => "Mensagem enviada com sucesso."];
        } else {
            return ["status" => 500, "message" => "Erro ao enviar mensagem."];
        }
    }
}

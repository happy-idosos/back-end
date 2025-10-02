<?php

require_once __DIR__ . '/../config/connection.php';

class LoginController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function login($data)
    {
        if (empty($data['email']) || empty($data['senha'])) {
            return [
                "status" => 400,
                "message" => "Email e senha são obrigatórios."
            ];
        }

        $email = $data['email'];
        $senha = $data['senha'];

        try {
            // Verifica primeiro em usuarios
            $queryUsuario = "SELECT id_usuario, nome, email, senha FROM usuarios WHERE email = :email";
            $stmtUsuario = $this->conn->prepare($queryUsuario);
            $stmtUsuario->bindParam(':email', $email);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                return [
                    "status" => 200,
                    "message" => "Login de usuário realizado com sucesso.",
                    "data" => [
                        "id" => $usuario['id_usuario'],
                        "nome" => $usuario['nome'],
                        "email" => $usuario['email'],
                        "tipo" => "usuario"
                    ]
                ];
            }

            // Verifica em asilos
            $queryAsilo = "SELECT id_asilo, nome, email, senha FROM asilos WHERE email = :email";
            $stmtAsilo = $this->conn->prepare($queryAsilo);
            $stmtAsilo->bindParam(':email', $email);
            $stmtAsilo->execute();
            $asilo = $stmtAsilo->fetch(PDO::FETCH_ASSOC);

            if ($asilo && password_verify($senha, $asilo['senha'])) {
                return [
                    "status" => 200,
                    "message" => "Login de asilo realizado com sucesso.",
                    "data" => [
                        "id" => $asilo['id_asilo'],
                        "nome" => $asilo['nome'],
                        "email" => $asilo['email'],
                        "tipo" => "asilo"
                    ]
                ];
            }

            // Se não encontrou em nenhum
            return [
                "status" => 401,
                "message" => "Credenciais inválidas."
            ];
        } catch (Exception $e) {
            return [
                "status" => 500,
                "message" => "Erro interno no servidor.",
                "error" => $e->getMessage()
            ];
        }
    }
}

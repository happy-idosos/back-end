<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;

class LoginController
{
    private $conn;
    private $jwtSecret;

    public function __construct($conn)
    {
        $this->conn = $conn;

        // Pega direto do $_ENV carregado no index.php
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? null;

        if (!$this->jwtSecret) {
            throw new Exception("JWT_SECRET não definido no ambiente.");
        }
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
                $token = $this->gerarToken($usuario['id_usuario'], $usuario['nome'], $usuario['email'], "usuario");
                return $this->respostaSucesso($usuario['id_usuario'], $usuario['nome'], $usuario['email'], "usuario", $token);
            }

            // Verifica em asilos
            $queryAsilo = "SELECT id_asilo, nome, email, senha FROM asilos WHERE email = :email";
            $stmtAsilo = $this->conn->prepare($queryAsilo);
            $stmtAsilo->bindParam(':email', $email);
            $stmtAsilo->execute();
            $asilo = $stmtAsilo->fetch(PDO::FETCH_ASSOC);

            if ($asilo && password_verify($senha, $asilo['senha'])) {
                $token = $this->gerarToken($asilo['id_asilo'], $asilo['nome'], $asilo['email'], "asilo");
                return $this->respostaSucesso($asilo['id_asilo'], $asilo['nome'], $asilo['email'], "asilo", $token);
            }

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

    private function gerarToken($id, $nome, $email, $tipo)
    {
        $payload = [
            "iss" => "happy_idosos_api",
            "iat" => time(),
            "exp" => time() + (60*60*24),
            "data" => [
                "id" => $id,
                "nome" => $nome,
                "email" => $email,
                "tipo" => $tipo
            ]
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function respostaSucesso($id, $nome, $email, $tipo, $token)
    {
        return [
            "status" => 200,
            "message" => $tipo === "usuario" ? "Login de usuário realizado com sucesso." : "Login de asilo realizado com sucesso.",
            "data" => [
                "id" => $id,
                "nome" => $nome,
                "email" => $email,
                "tipo" => $tipo,
                "token" => $token
            ]
        ];
    }
}

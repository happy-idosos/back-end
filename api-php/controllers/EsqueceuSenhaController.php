<?php
// filepath: c:\xampp\htdocs\back-end\api-php\controllers\EsqueceuSenhaController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

class EsqueceuSenhaController
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function solicitarReset(string $email): array
    {
        // Verifica se o email existe
        $sql = "SELECT id_usuario, nome FROM usuarios WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return ["status" => 404, "message" => "Email não encontrado."];
        }

        // Gera token único e expiração
        $token = bin2hex(random_bytes(16));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Salva token no banco
        $sqlToken = "INSERT INTO reset_senha (id_usuario, token, expira_em) 
                     VALUES (:id_usuario, :token, :expira)";
        $stmtToken = $this->conn->prepare($sqlToken);
        $stmtToken->bindParam(":id_usuario", $usuario['id_usuario']);
        $stmtToken->bindParam(":token", $token);
        $stmtToken->bindParam(":expira", $expira);
        $stmtToken->execute();

        // Carrega variáveis do .env
        $env = parse_ini_file(__DIR__ . '/../.env');

        // Envia email via PHPMailer (instalado pelo Composer)
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USERNAME'];
            $mail->Password   = $env['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $env['SMTP_PORT'];

            $mail->setFrom($env['SMTP_FROM_EMAIL'], $env['SMTP_FROM_NAME']);
            $mail->addAddress($email, $usuario['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Redefina sua senha - Happy Idosos';

            $link = "http://localhost/back-end/api-php/api/reset-senha?token=$token";
            $mail->Body = "
                <p>Olá <strong>{$usuario['nome']}</strong>,</p>
                <p>Para redefinir sua senha, acesse o link abaixo:</p>
                <p><a href=\"$link\">Redefinir senha</a></p>
                <p>Ou insira o seguinte token no formulário:</p>
                <p><b>$token</b></p>
                <p><small>Este link expira em 1 hora.</small></p>
            ";

            $mail->send();
            return ["status" => 200, "message" => "Email de redefinição enviado com sucesso."];
        } catch (Exception $e) {
            return ["status" => 500, "message" => "Erro ao enviar email.", "error" => $mail->ErrorInfo];
        }
    }

    public function redefinirSenha(string $token, string $novaSenha): array
    {
        // Verifica se o token é válido e não expirou
        $sql = "SELECT id_usuario FROM reset_senha WHERE token = :token AND expira_em > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ["status" => 400, "message" => "Token inválido ou expirado."];
        }

        // Atualiza a senha do usuário
        $hashSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sqlUpdate = "UPDATE usuarios SET senha = :senha WHERE id_usuario = :id_usuario";
        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(":senha", $hashSenha);
        $stmtUpdate->bindParam(":id_usuario", $row['id_usuario']);
        $stmtUpdate->execute();

        // Remove o token usado
        $sqlDelete = "DELETE FROM reset_senha WHERE token = :token";
        $stmtDelete = $this->conn->prepare($sqlDelete);
        $stmtDelete->bindParam(":token", $token);
        $stmtDelete->execute();

        return ["status" => 200, "message" => "Senha redefinida com sucesso."];
    }
}

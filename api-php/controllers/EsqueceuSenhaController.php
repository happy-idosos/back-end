<?php
// filepath: c:\xampp\htdocs\back-end\api-php\controllers\EsqueceuSenhaController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

class EsqueceuSenhaController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function solicitarReset($email)
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

        // Gera token único
        $token = bin2hex(random_bytes(16));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Salva token no banco
        $sqlToken = "INSERT INTO reset_senha (id_usuario, token, expira_em) VALUES (:id_usuario, :token, :expira)";
        $stmtToken = $this->conn->prepare($sqlToken);
        $stmtToken->bindParam(":id_usuario", $usuario['id_usuario']);
        $stmtToken->bindParam(":token", $token);
        $stmtToken->bindParam(":expira", $expira);
        $stmtToken->execute();

        // Envia email com link de redefinição usando Gmail SMTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'pedromedeirosetec02@gmail.com';
            $mail->Password   = 'qpwj ekmy jsia afnk';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('pedromedeirosetec02@gmail.com', 'Happy Idosos');
            $mail->addAddress($email, $usuario['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de senha';
            $mail->Body = "Olá {$usuario['nome']},<br>Para redefinir sua senha, acesse:<br>
                <a href='http://localhost/back-end/api-php/reset_senha.html?token=$token'>Redefinir senha</a><br>
                Este link expira em 1 hora."; // Ajustar link para meu domínio de produção

            $mail->send();
            return ["status" => 200, "message" => "Email de redefinição enviado."];
        } catch (Exception $e) {
            return ["status" => 500, "message" => "Erro ao enviar email.", "error" => $mail->ErrorInfo];
        }
    }

    public function redefinirSenha($token, $novaSenha)
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

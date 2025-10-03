<?php
// filepath: controllers/EsqueceuSenhaController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

class EsqueceuSenhaController
{
    private $conn;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFrom;
    private $smtpFromName;

    public function __construct($db)
    {
        $this->conn = $db;

        // Carrega variáveis do ambiente
        $this->smtpHost = getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? '');
        $this->smtpPort = getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587);
        $this->smtpUser = getenv('SMTP_USERNAME') ?: ($_ENV['SMTP_USERNAME'] ?? '');
        $this->smtpPass = getenv('SMTP_PASSWORD') ?: ($_ENV['SMTP_PASSWORD'] ?? '');
        $this->smtpFrom = getenv('SMTP_FROM_EMAIL') ?: ($_ENV['SMTP_FROM_EMAIL'] ?? '');
        $this->smtpFromName = getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? 'Happy Idosos');

        if (!$this->smtpHost || !$this->smtpUser || !$this->smtpPass || !$this->smtpFrom) {
            throw new Exception("Variáveis SMTP não definidas no ambiente.");
        }
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

        // Envia email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;

            $mail->setFrom($this->smtpFrom, $this->smtpFromName);
            $mail->addAddress($email, $usuario['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Redefina sua senha - Happy Idosos';
            $mail->Body = "
                Olá {$usuario['nome']},<br><br>
                Para redefinir sua senha, acesse o link abaixo:<br>
                <a href=\"http://localhost/back-end/api-php/api/reset-senha?token=$token\">Redefinir senha</a><br><br>
                Insira no formulário o seu token para realizar a troca:<br>
                <b>$token</b><br><br>
                Este link expira em 1 hora.
            ";

            $mail->send();
            return ["status" => 200, "message" => "Email de redefinição enviado."];
        } catch (Exception $e) {
            return ["status" => 500, "message" => "Erro ao enviar email.", "error" => $mail->ErrorInfo];
        }
    }

    public function redefinirSenha($token, $novaSenha)
    {
        $sql = "SELECT id_usuario FROM reset_senha WHERE token = :token AND expira_em > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ["status" => 400, "message" => "Token inválido ou expirado."];
        }

        $hashSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sqlUpdate = "UPDATE usuarios SET senha = :senha WHERE id_usuario = :id_usuario";
        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(":senha", $hashSenha);
        $stmtUpdate->bindParam(":id_usuario", $row['id_usuario']);
        $stmtUpdate->execute();

        $sqlDelete = "DELETE FROM reset_senha WHERE token = :token";
        $stmtDelete = $this->conn->prepare($sqlDelete);
        $stmtDelete->bindParam(":token", $token);
        $stmtDelete->execute();

        return ["status" => 200, "message" => "Senha redefinida com sucesso."];
    }
}

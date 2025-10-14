<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EsqueceuSenhaController
{
    private $conn;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFrom;
    private $smtpFromName;
    private $smtpSecure;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->carregarConfigSMTP();
    }

    private function carregarConfigSMTP()
    {
        // Carrega .env se existir
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }

        // Configurações SMTP do .env
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587;
        $this->smtpUser = $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?? 'happyidosos@gmail.com';
        $this->smtpPass = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? '';
        $this->smtpFrom = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?? $this->smtpUser;
        $this->smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'Happy Idosos';
        $this->smtpSecure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?? 'tls';

        if (!$this->smtpHost || !$this->smtpUser || !$this->smtpPass) {
            throw new Exception("Variáveis SMTP não definidas no ambiente.");
        }
    }

    public function solicitarReset(string $email): array
    {
        if (empty($email)) {
            return ["status" => 400, "message" => "Email é obrigatório."];
        }

        // Verifica se o email existe em USUARIOS
        $sqlUsuario = "SELECT id_usuario, nome FROM usuarios WHERE email = :email";
        $stmtUsuario = $this->conn->prepare($sqlUsuario);
        $stmtUsuario->bindParam(":email", $email);
        $stmtUsuario->execute();
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        // Verifica se o email existe em ASILOS
        $sqlAsilo = "SELECT id_asilo, nome FROM asilos WHERE email = :email";
        $stmtAsilo = $this->conn->prepare($sqlAsilo);
        $stmtAsilo->bindParam(":email", $email);
        $stmtAsilo->execute();
        $asilo = $stmtAsilo->fetch(PDO::FETCH_ASSOC);

        if (!$usuario && !$asilo) {
            return ["status" => 404, "message" => "Email não encontrado."];
        }

        // Gera token único e expiração
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Determina tipo de usuário e ID
        if ($usuario) {
            $tipo = 'usuario';
            $id = $usuario['id_usuario'];
            $nome = $usuario['nome'];
            $idUsuario = $id;
            $idAsilo = null;
        } else {
            $tipo = 'asilo';
            $id = $asilo['id_asilo'];
            $nome = $asilo['nome'];
            $idUsuario = null;
            $idAsilo = $id;
        }

        $sqlDelete = "DELETE FROM reset_senha WHERE (id_usuario = :id_usuario AND tipo_usuario = 'usuario') OR (id_asilo = :id_asilo AND tipo_usuario = 'asilo')";
        $stmtDelete = $this->conn->prepare($sqlDelete);
        $stmtDelete->bindParam(":id_usuario", $idUsuario, PDO::PARAM_INT);
        $stmtDelete->bindParam(":id_asilo", $idAsilo, PDO::PARAM_INT);
        $stmtDelete->execute();

        // Salva token no banco
        $sqlToken = "INSERT INTO reset_senha (id_usuario, id_asilo, tipo_usuario, token, expira_em) 
                     VALUES (:id_usuario, :id_asilo, :tipo_usuario, :token, :expira)";
        $stmtToken = $this->conn->prepare($sqlToken);
        $stmtToken->bindParam(":id_usuario", $idUsuario, PDO::PARAM_INT);
        $stmtToken->bindParam(":id_asilo", $idAsilo, PDO::PARAM_INT);
        $stmtToken->bindParam(":tipo_usuario", $tipo);
        $stmtToken->bindParam(":token", $token);
        $stmtToken->bindParam(":expira", $expira);
        
        if (!$stmtToken->execute()) {
            return ["status" => 500, "message" => "Erro ao gerar token de recuperação."];
        }

        // Envia email via PHPMailer
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->smtpPort;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->smtpFrom, $this->smtpFromName);
            $mail->addAddress($email, $nome);
            $mail->isHTML(true);
            $mail->Subject = 'Redefina sua senha - Happy Idosos';

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #244a96;'>Redefinição de Senha</h2>
                    <p>Olá <strong>{$nome}</strong>,</p>
                    <p>Recebemos uma solicitação para redefinir sua senha. Use o token abaixo para prosseguir:</p>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                        <h3 style='margin: 0; color: #244a96; letter-spacing: 2px;'>{$token}</h3>
                    </div>
                    <p><strong>Instruções:</strong></p>
                    <ol>
                        <li>Copie o token acima</li>
                        <li>Volte à página de redefinição de senha</li>
                        <li>Cole o token no campo indicado</li>
                        <li>Crie sua nova senha</li>
                    </ol>
                    <p><small><em>Este token expira em 1 hora. Se não foi você que solicitou, ignore este email.</em></small></p>
                    <hr>
                    <p style='color: #666; font-size: 12px;'>Happy Idosos &copy; 2025 - Cuidando com amor e respeito</p>
                </div>
            ";

            $mail->AltBody = "Redefina sua senha - Happy Idosos\n\nOlá {$nome},\n\nUse este token para redefinir sua senha: {$token}\n\nO token expira em 1 hora.";

            if ($mail->send()) {
                return ["status" => 200, "message" => "Token enviado com sucesso! Verifique seu email."];
            } else {
                return ["status" => 500, "message" => "Erro ao enviar email."];
            }
        } catch (Exception $e) {
            error_log("ERRO PHPMailer: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro de configuração de email: " . $e->getMessage()];
        }
    }

    public function redefinirSenha(string $token, string $novaSenha): array
    {
        if (empty($token) || empty($novaSenha)) {
            return ["status" => 400, "message" => "Token e nova senha são obrigatórios."];
        }

        $sql = "SELECT id_usuario, id_asilo, tipo_usuario FROM reset_senha WHERE token = :token AND expira_em > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ["status" => 400, "message" => "Token inválido ou expirado."];
        }

        $hashSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
        
        if ($row['tipo_usuario'] === 'usuario') {
            $sqlUpdate = "UPDATE usuarios SET senha = :senha WHERE id_usuario = :id";
            $id = $row['id_usuario'];
        } else {
            $sqlUpdate = "UPDATE asilos SET senha = :senha WHERE id_asilo = :id";
            $id = $row['id_asilo'];
        }

        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(":senha", $hashSenha);
        $stmtUpdate->bindParam(":id", $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Remove o token usado
        $sqlDelete = "DELETE FROM reset_senha WHERE token = :token";
        $stmtDelete = $this->conn->prepare($sqlDelete);
        $stmtDelete->bindParam(":token", $token);
        $stmtDelete->execute();

        return ["status" => 200, "message" => "Senha redefinida com sucesso!"];
    }

    public function validarToken(string $token): array
    {
        if (empty($token)) {
            return ["status" => 400, "message" => "Token é obrigatório."];
        }

        $sql = "SELECT token, expira_em, tipo_usuario FROM reset_senha 
                WHERE token = :token AND expira_em > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ["status" => 400, "message" => "Token inválido ou expirado."];
        }

        return [
            "status" => 200, 
            "message" => "Token válido.", 
            "token" => $row['token'],
            "tipo_usuario" => $row['tipo_usuario']
        ];
    }
}

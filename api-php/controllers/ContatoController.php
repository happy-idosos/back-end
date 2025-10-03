<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContatoController
{
    private $conn;
    private $uploadDir = __DIR__ . '/../uploads/';
    private $emailDestino = "contato@happyidosos.org";

    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFrom;
    private $smtpFromName;

    public function __construct($db)
    {
        $this->conn = $db;

        // Carrega .env se existir
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }

        // Configura SMTP com fallback
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = getenv('SMTP_PORT') ?: 587;
        $this->smtpUser = getenv('SMTP_USERNAME') ?: 'pedromedeirosetec02@gmail.com';
        $this->smtpPass = getenv('SMTP_PASSWORD') ?: 'qpwj ekmy jsia afnk';
        $this->smtpFrom = getenv('SMTP_FROM_EMAIL') ?: $this->smtpUser;
        $this->smtpFromName = getenv('SMTP_FROM_NAME') ?: 'Happy Idosos';
    }

    public function enviar($dados, $arquivo = null)
    {
        // Validação dos campos do usuário
        if (empty($dados['nome']) || empty($dados['email']) || empty($dados['telefone']) || empty($dados['mensagem'])) {
            return ["status" => 400, "message" => "Todos os campos obrigatórios devem ser preenchidos."];
        }

        if (!validarNome($dados['nome'])) return ["status" => 400, "message" => "Nome inválido."];
        if (!validarEmail($dados['email'])) return ["status" => 400, "message" => "Email inválido."];
        if (!validarTelefone($dados['telefone'])) return ["status" => 400, "message" => "Telefone inválido."];

        $mail = new PHPMailer(true);

        try {
            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            $mail->Port = $this->smtpPort;

            // De/Para
            $mail->setFrom($this->smtpFrom, $this->smtpFromName);
            $mail->addAddress($this->emailDestino);
            $mail->addReplyTo($dados['email'], $dados['nome']);

            // Anexo
            if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
                $arquivoNome = uniqid() . '_' . basename($arquivo['name']);
                $destino = $this->uploadDir . $arquivoNome;
                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    return ["status" => 500, "message" => "Erro ao enviar arquivo."];
                }
                $mail->addAttachment($destino, $arquivo['name']);
            }

            // Corpo do email
            $mail->Subject = "Nova mensagem de contato de {$dados['nome']}";
            $mail->Body = "Nome: {$dados['nome']}\nEmail: {$dados['email']}\nTelefone: {$dados['telefone']}\n\nMensagem:\n{$dados['mensagem']}";

            $mail->send();
            return ["status" => 200, "message" => "Mensagem enviada com sucesso."];
        } catch (Exception $e) {
            return ["status" => 500, "message" => "Erro ao enviar mensagem: {$mail->ErrorInfo}"];
        }
    }
}

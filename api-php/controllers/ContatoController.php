<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../utils/validators.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContatoController
{
    private $conn;
    private $uploadDir;
    private $emailDestino = "pedromedeirosetec02@gmail.com";

    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFrom;
    private $smtpFromName;

    public function __construct($db)
    {
        $this->conn = $db;

        $this->uploadDir = __DIR__ . '/../uploads/contato';
        
        // Cria a pasta se não existir
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // Carrega .env 
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }

        // Configura SMTP com fallback
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = getenv('SMTP_PORT') ?: 587;
        $this->smtpUser = getenv('SMTP_USERNAME') ?: 'happyidosos@gmail.com';
        $this->smtpPass = getenv('SMTP_PASSWORD') ?: 'HappyIdosos123';
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

            $arquivoNome = null;
            if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
                
                // Verifica se a pasta existe e tem permissão
                if (!is_writable($this->uploadDir)) {
                    throw new Exception("Pasta de uploads sem permissão de escrita");
                }
                
                // Valida tamanho do arquivo (10MB máximo)
                if ($arquivo['size'] > 10 * 1024 * 1024) {
                    return ["status" => 400, "message" => "Arquivo muito grande. Tamanho máximo: 10MB"];
                }
                
                // Valida tipo de arquivo
                $tiposPermitidos = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                if (!in_array($extensao, $tiposPermitidos)) {
                    return ["status" => 400, "message" => "Tipo de arquivo não permitido. Use: JPG, PNG, PDF, DOC"];
                }
                
                $arquivoNome = uniqid() . '_' . basename($arquivo['name']);
                $destino = $this->uploadDir . $arquivoNome;
                
                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    return ["status" => 500, "message" => "Erro ao salvar arquivo no servidor."];
                }
                
                $mail->addAttachment($destino, $arquivo['name']);
            }

            // Corpo do email
            $mail->Subject = "Nova mensagem de contato de {$dados['nome']}";
            $mail->Body = "Nome: {$dados['nome']}\nEmail: {$dados['email']}\nTelefone: {$dados['telefone']}\n\nMensagem:\n{$dados['mensagem']}";

            $mail->send();
            
            $mensagemSucesso = "Mensagem enviada com sucesso.";
            if ($arquivoNome) {
                $mensagemSucesso .= " Arquivo recebido: " . $arquivo['name'];
            }
            
            return ["status" => 200, "message" => $mensagemSucesso];
            
        } catch (Exception $e) {
            return ["status" => 500, "message" => "Erro ao enviar mensagem: " . $e->getMessage()];
        }
    }
}
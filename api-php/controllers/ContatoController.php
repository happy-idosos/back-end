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
    private $emailDestino;
    
    // Configurações SMTP
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFrom;
    private $smtpFromName;
    private $smtpSecure;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->uploadDir = __DIR__ . '/../uploads/contato';
        
        // Cria pasta de uploads
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // Carrega configurações do .env
        $this->carregarConfigSMTP();
        
        // Email destino do .env
        $this->emailDestino = $_ENV['CONTATO_EMAIL'] ?? getenv('CONTATO_EMAIL') ?? "happyidosos@gmail.com";
    }

    private function carregarConfigSMTP()
    {
        // Carrega .env se existir - COM TRY/CATCH
        try {
            if (file_exists(__DIR__ . '/../.env')) {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
                $dotenv->load();
            }
        } catch (Exception $e) {
            error_log("Aviso: Não foi possível carregar .env: " . $e->getMessage());
        }

        // Configurações SMTP do .env com fallback
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587;
        $this->smtpUser = $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?? 'happyidosos@gmail.com';
        $this->smtpPass = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? 'gohh igdk sajp qpvl';
        $this->smtpFrom = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?? $this->smtpUser;
        $this->smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?? 'Happy Idosos';
        $this->smtpSecure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?? 'tls';

        // Remove aspas se existirem (problema comum com .env)
        $this->smtpPass = trim($this->smtpPass, '"\'');
        $this->smtpFromName = trim($this->smtpFromName, '"\'');

        // Valida configurações essenciais
        if (!$this->smtpHost || !$this->smtpUser || !$this->smtpPass) {
            error_log("Configurações SMTP incompletas. Host: {$this->smtpHost}, User: {$this->smtpUser}");
        }
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
            $mail->SMTPSecure = $this->smtpSecure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';

            // Debug SMTP (opcional - remover em produção)
            $mail->SMTPDebug = 0; // 0 = off, 1 = client, 2 = client and server

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
                $destino = $this->uploadDir . '/' . $arquivoNome;
                
                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    return ["status" => 500, "message" => "Erro ao salvar arquivo no servidor."];
                }
                
                $mail->addAttachment($destino, $arquivo['name']);
            }

            // Assunto e corpo do email
            $assunto = $dados['assunto'] ?? "Nova mensagem de contato";
            $mail->Subject = $assunto . " - " . $dados['nome'];
            
            // Corpo HTML do email
            $mail->isHTML(true);
            $mail->Body = $this->criarCorpoEmailHTML($dados, $arquivo);
            $mail->AltBody = $this->criarCorpoEmailTexto($dados, $arquivo);

            if ($mail->send()) {
                $mensagemSucesso = "Mensagem enviada com sucesso! Responderemos em breve.";
                if ($arquivoNome) {
                    $mensagemSucesso .= " Arquivo recebido: " . $arquivo['name'];
                }
                return ["status" => 200, "message" => $mensagemSucesso];
            } else {
                return ["status" => 500, "message" => "Erro ao enviar email. Tente novamente."];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email de contato: " . $e->getMessage());
            return ["status" => 500, "message" => "Erro ao enviar mensagem: " . $e->getMessage()];
        }
    }

    private function criarCorpoEmailHTML($dados, $arquivo)
    {
        $assunto = $dados['assunto'] ?? "Não informado";
        $arquivoInfo = $arquivo ? "Sim (" . $arquivo['name'] . ")" : "Não";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
                .header { background: #244a96; color: white; padding: 20px; border-radius: 5px; text-align: center; }
                .content { margin: 20px 0; }
                .field { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
                .label { font-weight: bold; color: #244a96; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 Nova Mensagem de Contato</h1>
                </div>
                
                <div class='content'>
                    <div class='field'>
                        <span class='label'>Assunto:</span> {$assunto}
                    </div>
                    <div class='field'>
                        <span class='label'>Nome:</span> {$dados['nome']}
                    </div>
                    <div class='field'>
                        <span class='label'>Email:</span> {$dados['email']}
                    </div>
                    <div class='field'>
                        <span class='label'>Telefone:</span> {$dados['telefone']}
                    </div>
                    <div class='field'>
                        <span class='label'>Arquivo Anexado:</span> {$arquivoInfo}
                    </div>
                    <div class='field'>
                        <span class='label'>Mensagem:</span><br>
                        <div style='margin-top: 10px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px;'>
                            " . nl2br(htmlspecialchars($dados['mensagem'])) . "
                        </div>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>💙 <strong>Happy Idosos</strong> - Cuidando com amor e respeito</p>
                    <p>Este email foi enviado através do formulário de contato do site.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function criarCorpoEmailTexto($dados, $arquivo)
    {
        $assunto = $dados['assunto'] ?? "Não informado";
        $arquivoInfo = $arquivo ? "Sim (" . $arquivo['name'] . ")" : "Não";
        
        return "
NOVA MENSAGEM DE CONTATO - HAPPY IDOSOS

Assunto: {$assunto}
Nome: {$dados['nome']}
Email: {$dados['email']}
Telefone: {$dados['telefone']}
Arquivo Anexado: {$arquivoInfo}

Mensagem:
{$dados['mensagem']}

---
💙 Happy Idosos - Cuidando com amor e respeito
Este email foi enviado através do formulário de contato do site.
        ";
    }
}
<?php

require_once __DIR__ . '/../config/connection.php';

class VideoController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Upload de vídeo (campo: video, titulo, descricao)
     * Retorna: arquivo, titulo, descricao
     */
    public function uploadVideo($files, $data)
    {
        // Verificar se o arquivo foi enviado
        if (!isset($files['video']) || $files['video']['error'] !== UPLOAD_ERR_OK) {
            return [
                "status" => 400,
                "message" => "Nenhum vídeo enviado ou erro no upload."
            ];
        }

        $video = $files['video'];
        
        // Validar tipo de arquivo
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/quicktime', 'video/mov'];
        $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));

        if (!in_array($video['type'], $allowedTypes) && !in_array($ext, ['mp4', 'webm', 'ogg', 'avi', 'mov'])) {
            return [
                "status" => 400,
                "message" => "Formato de vídeo inválido. Permitidos: MP4, WEBM, OGG, AVI, MOV."
            ];
        }

        // CORREÇÃO: Criar diretório de upload na raiz do projeto
        $uploadDir = __DIR__ . '/../../uploads/videos/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                return [
                    "status" => 500,
                    "message" => "Erro ao criar diretório de upload."
                ];
            }
        }

        // Gerar nome único para o arquivo
        $fileName = uniqid('video_', true) . "." . $ext;
        $filePath = $uploadDir . $fileName;

        // Mover arquivo
        if (!move_uploaded_file($video['tmp_name'], $filePath)) {
            return [
                "status" => 500,
                "message" => "Erro ao salvar o vídeo no servidor."
            ];
        }

        // CORREÇÃO: Usar estrutura da tabela midias
        $url = "uploads/videos/" . $fileName;
        $nome_midia = $data['titulo'] ?? pathinfo($video['name'], PATHINFO_FILENAME);
        $descricao = $data['descricao'] ?? null;

        // IDs opcionais para relacionamento
        $id_usuario = $data['id_usuario'] ?? 1; // Temporário - ajuste conforme autenticação
        $id_asilo = $data['id_asilo'] ?? null;
        $id_evento = $data['id_evento'] ?? null;

        try {
            // CORREÇÃO: Usar tabela midias com colunas corretas
            $sql = "INSERT INTO midias (nome_midia, descricao, url, id_usuario, id_asilo, id_evento)
                    VALUES (:nome_midia, :descricao, :url, :id_usuario, :id_asilo, :id_evento)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nome_midia', $nome_midia);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindValue(':id_asilo', $id_asilo, PDO::PARAM_INT);
            $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            
            $stmt->execute();

            $id_midia = $this->conn->lastInsertId();

            return [
                "status" => 201,
                "message" => "Vídeo enviado com sucesso.",
                "data" => [
                    "id_midia" => $id_midia,
                    "nome_midia" => $nome_midia,
                    "descricao" => $descricao,
                    "url" => $url,
                    "id_usuario" => $id_usuario
                ]
            ];
        } catch (Exception $e) {
            // Remove o arquivo se houve erro no banco
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return [
                "status" => 500,
                "message" => "Erro ao salvar vídeo no banco de dados.",
                "error" => $e->getMessage()
            ];
        }
    }

    /**
     * Listar todos os vídeos (midias do tipo vídeo)
     */
    public function listarVideos()
    {
        try {
            // CORREÇÃO: Usar tabela midias com colunas corretas
            // Como não temos coluna 'tipo', vamos buscar todas as midias
            // Você pode adicionar um campo 'tipo' depois se necessário
            $query = "SELECT 
                        id_midia,
                        nome_midia, 
                        descricao, 
                        url,
                        id_usuario,
                        id_asilo,
                        id_evento
                      FROM midias 
                      ORDER BY id_midia DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => 200,
                "message" => "Vídeos listados com sucesso.",
                "data" => $videos
            ];
        } catch (PDOException $e) {
            return [
                "status" => 500,
                "message" => "Erro ao buscar vídeos.",
                "error" => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar vídeo por ID
     */
    public function buscarVideo($id_video)
    {
        try {
            // CORREÇÃO: Usar tabela midias
            $query = "SELECT 
                        id_midia,
                        nome_midia, 
                        descricao, 
                        url,
                        id_usuario,
                        id_asilo,
                        id_evento
                      FROM midias 
                      WHERE id_midia = :id_midia";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_midia', $id_video, PDO::PARAM_INT);
            $stmt->execute();
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                return [
                    "status" => 404,
                    "message" => "Vídeo não encontrado."
                ];
            }

            return [
                "status" => 200,
                "message" => "Vídeo encontrado com sucesso.",
                "data" => $video
            ];
        } catch (PDOException $e) {
            return [
                "status" => 500,
                "message" => "Erro ao buscar vídeo.",
                "error" => $e->getMessage()
            ];
        }
    }

    /**
     * Deletar vídeo
     */
    public function deletarVideo($id_video)
    {
        try {
            // CORREÇÃO: Primeiro busca o caminho do arquivo da tabela midias
            $query = "SELECT url FROM midias WHERE id_midia = :id_midia";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_midia', $id_video, PDO::PARAM_INT);
            $stmt->execute();
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                return [
                    "status" => 404,
                    "message" => "Vídeo não encontrado."
                ];
            }

            // Deleta do banco
            $query = "DELETE FROM midias WHERE id_midia = :id_midia";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_midia', $id_video, PDO::PARAM_INT);
            $stmt->execute();

            // Remove o arquivo físico
            $filePath = __DIR__ . '/../../' . $video['url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return [
                "status" => 200,
                "message" => "Vídeo deletado com sucesso."
            ];
        } catch (PDOException $e) {
            return [
                "status" => 500,
                "message" => "Erro ao deletar vídeo.",
                "error" => $e->getMessage()
            ];
        }
    }

    /**
     * Método auxiliar para servir arquivos de vídeo
     */
    public function servirVideo($filename)
    {
        $filePath = __DIR__ . '/../../uploads/videos/' . $filename;
        
        if (!file_exists($filePath)) {
            return [
                "status" => 404,
                "message" => "Arquivo de vídeo não encontrado."
            ];
        }

        // Define headers para streaming de vídeo
        header('Content-Type: video/mp4');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        
        readfile($filePath);
        exit;
    }
}
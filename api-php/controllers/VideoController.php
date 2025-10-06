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
     * Upload de vídeo (campo: video, descricao)
     * Retorna: arquivo, descricao
     */
    public function uploadVideo($files, $data)
    {
        if (!isset($files['video']) || $files['video']['error'] !== UPLOAD_ERR_OK) {
            return [
                "status" => 400,
                "message" => "Nenhum vídeo enviado ou erro no upload."
            ];
        }

        $video = $files['video'];
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/quicktime', 'video/mov'];
        $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));

        if (!in_array($video['type'], $allowedTypes) && !in_array($ext, ['mp4', 'webm', 'ogg', 'avi', 'mov'])) {
            return [
                "status" => 400,
                "message" => "Formato de vídeo inválido. Permitidos: MP4, WEBM, OGG, AVI, MOV."
            ];
        }

        // Cria diretório de upload, se não existir
        $uploadDir = __DIR__ . '/../uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('video_', true) . "." . $ext;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($video['tmp_name'], $filePath)) {
            return [
                "status" => 500,
                "message" => "Erro ao salvar o vídeo no servidor."
            ];
        }

        $url = "uploads/videos/" . $fileName;
        $descricao = $data['descricao'] ?? null;

        try {
            $sql = "INSERT INTO midias (nome_midia, descricao, url, data_criacao)
                    VALUES (:nome, :descricao, :url, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nome', $fileName);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':url', $url);
            $stmt->execute();

            return [
                "status" => 201,
                "message" => "Vídeo enviado com sucesso.",
                "data" => [
                    "arquivo" => $url,
                    "descricao" => $descricao
                ]
            ];
        } catch (Exception $e) {
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

    public function listarVideos()
{
    try {
        $query = "SELECT id, titulo, descricao, caminho, data_upload FROM videos ORDER BY data_upload DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => 200,
            "message" => "Vídeos listados com sucesso.",
            "data" => $videos
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "status" => 500,
            "message" => "Erro ao buscar vídeos.",
            "error" => $e->getMessage()
        ]);
    }
}


}

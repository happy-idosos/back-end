<?php

require_once __DIR__ . '/../config/connection.php';

class VideoController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Upload de vídeo
    public function uploadVideo($files, $data)
    {
        if (!isset($files['video']) || $files['video']['error'] !== UPLOAD_ERR_OK) {
            return [
                "status" => 400,
                "message" => "Nenhum vídeo enviado ou erro no upload."
            ];
        }

        $video = $files['video'];
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];

        if (!in_array($video['type'], $allowedTypes)) {
            return [
                "status" => 400,
                "message" => "Formato de vídeo inválido. Permitidos: MP4, WEBM, OGG."
            ];
        }

        // Define diretório de upload
        $uploadDir = __DIR__ . '/../uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Nome único para evitar conflito
        $fileName = uniqid('video_', true) . "." . pathinfo($video['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($video['tmp_name'], $filePath)) {
            return [
                "status" => 500,
                "message" => "Erro ao salvar o vídeo no servidor."
            ];
        }

        // Caminho relativo para salvar no banco
        $url = "uploads/videos/" . $fileName;
        $descricao = $data['descricao'] ?? null;

        try {
            $sql = "INSERT INTO midias (nome_midia, descricao, url) VALUES (:nome, :descricao, :url)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nome', $fileName);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':url', $url);
            $stmt->execute();

            return [
                "status" => 201,
                "message" => "Vídeo enviado com sucesso.",
                "data" => [
                    "nome" => $fileName,
                    "url" => $url,
                    "descricao" => $descricao
                ]
            ];
        } catch (Exception $e) {
            return [
                "status" => 500,
                "message" => "Erro ao salvar vídeo no banco de dados.",
                "error" => $e->getMessage()
            ];
        }
    }

    // Listagem do feed de vídeos
    public function listarVideos()
    {
        try {
            $sql = "SELECT id_midia, nome_midia, descricao, url, data FROM midias WHERE url LIKE 'uploads/videos/%' ORDER BY id_midia DESC";
            $stmt = $this->conn->query($sql);

            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => 200,
                "message" => "Vídeos listados com sucesso.",
                "data" => $videos
            ];
        } catch (Exception $e) {
            return [
                "status" => 500,
                "message" => "Erro ao buscar vídeos.",
                "error" => $e->getMessage()
            ];
        }
    }
}

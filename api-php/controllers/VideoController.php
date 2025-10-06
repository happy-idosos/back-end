<?php

require_once __DIR__ . '/../config/connection.php';

class VideoController
{
    private $conn;
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->uploadDir = __DIR__ . '/../uploads/videos/';
        $this->maxFileSize = 100 * 1024 * 1024; // 100MB
        $this->allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

        // Cria diretório se não existir
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Upload de vídeo (requer autenticação)
     */
    public function uploadVideo($user, $files, $data)
    {
        // Verifica se há arquivo enviado
        if (!isset($files['video']) || $files['video']['error'] !== UPLOAD_ERR_OK) {
            return $this->handleUploadError($files['video']['error'] ?? null);
        }

        $video = $files['video'];

        // Validação de tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $video['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                "status" => 400,
                "message" => "Formato de vídeo inválido. Permitidos: MP4, WEBM, OGG, MOV."
            ];
        }

        // Validação de tamanho
        if ($video['size'] > $this->maxFileSize) {
            return [
                "status" => 400,
                "message" => "Arquivo muito grande. Tamanho máximo: 100MB."
            ];
        }

        // Gera nome único
        $extension = pathinfo($video['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('video_', true) . '.' . $extension;
        $filePath = $this->uploadDir . $fileName;

        // Move arquivo
        if (!move_uploaded_file($video['tmp_name'], $filePath)) {
            return [
                "status" => 500,
                "message" => "Erro ao salvar o vídeo no servidor."
            ];
        }

        // Salva no banco
        $url = "uploads/videos/" . $fileName;
        $descricao = $data['descricao'] ?? null;
        $titulo = $data['titulo'] ?? 'Vídeo sem título';

        try {
            $sql = "INSERT INTO midias (nome_midia, descricao, url, id_usuario, id_asilo) 
                    VALUES (:nome, :descricao, :url, :id_usuario, :id_asilo)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nome', $titulo);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':url', $url);
            
            // Define quem enviou o vídeo
            if ($user->tipo === 'usuario') {
                $stmt->bindValue(':id_usuario', $user->id);
                $stmt->bindValue(':id_asilo', null);
            } else {
                $stmt->bindValue(':id_usuario', null);
                $stmt->bindValue(':id_asilo', $user->id);
            }
            
            $stmt->execute();

            return [
                "status" => 201,
                "message" => "Vídeo enviado com sucesso.",
                "data" => [
                    "id_midia" => $this->conn->lastInsertId(),
                    "nome" => $titulo,
                    "url" => $url,
                    "descricao" => $descricao,
                    "enviado_por" => $user->nome
                ]
            ];
        } catch (Exception $e) {
            // Remove arquivo se falhar ao salvar no banco
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
     * Listagem do feed de vídeos (público)
     */
    public function listarVideos()
    {
        try {
            $sql = "SELECT 
                        m.id_midia, 
                        m.nome_midia, 
                        m.descricao, 
                        m.url, 
                        m.data,
                        COALESCE(u.nome, a.nome) as autor_nome,
                        CASE 
                            WHEN m.id_usuario IS NOT NULL THEN 'usuario'
                            ELSE 'asilo'
                        END as autor_tipo
                    FROM midias m
                    LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
                    LEFT JOIN asilos a ON m.id_asilo = a.id_asilo
                    WHERE m.url LIKE 'uploads/videos/%'
                    ORDER BY m.data DESC";
            
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

    /**
     * Deletar vídeo (apenas o autor)
     */
    public function deletarVideo($user, $id_midia)
    {
        try {
            // Busca o vídeo
            $sql = "SELECT * FROM midias WHERE id_midia = :id_midia";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_midia', $id_midia);
            $stmt->execute();
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                return ['status' => 404, 'message' => 'Vídeo não encontrado'];
            }

            // Verifica se é o autor
            $isAutor = false;
            if ($user->tipo === 'usuario' && $video['id_usuario'] == $user->id) {
                $isAutor = true;
            } elseif ($user->tipo === 'asilo' && $video['id_asilo'] == $user->id) {
                $isAutor = true;
            }

            if (!$isAutor) {
                return ['status' => 403, 'message' => 'Você não tem permissão para deletar este vídeo'];
            }

            // Deleta do banco
            $sqlDelete = "DELETE FROM midias WHERE id_midia = :id_midia";
            $stmtDelete = $this->conn->prepare($sqlDelete);
            $stmtDelete->bindParam(':id_midia', $id_midia);
            $stmtDelete->execute();

            // Deleta arquivo físico
            $filePath = __DIR__ . '/../' . $video['url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return ['status' => 200, 'message' => 'Vídeo deletado com sucesso'];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => 'Erro ao deletar vídeo',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Trata erros de upload
     */
    private function handleUploadError($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'O arquivo foi enviado parcialmente.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco.',
            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload.'
        ];

        $message = $errors[$errorCode] ?? 'Erro desconhecido no upload.';

        return [
            "status" => 400,
            "message" => $message
        ];
    }
}

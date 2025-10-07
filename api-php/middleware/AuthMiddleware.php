<?php
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AuthMiddleware {
    
    private static $jwtSecret;
    
    private static function getJwtSecret() {
        if (!self::$jwtSecret) {
            self::$jwtSecret = $_ENV['JWT_SECRET'] ?? null;
            if (!self::$jwtSecret) {
                throw new Exception("JWT_SECRET não definido no ambiente.");
            }
        }
        return self::$jwtSecret;
    }
    
    public static function verifyAuth() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$token) {
            return null;
        }
        
        // Remove "Bearer " se presente
        $token = str_replace('Bearer ', '', $token);
        
        // Decodifica o token JWT usando a biblioteca Firebase JWT
        $user = self::decodeJWT($token);
        
        if (!$user) {
            return null;
        }
        
        return $user;
    }
    
    public static function requireAuth() {
        $user = self::verifyAuth();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Token de autenticação não fornecido ou inválido']);
            exit;
        }
        
        return $user;
    }
    
    public static function requireType($type) {
        $user = self::requireAuth();
        
        // Verifica se o tipo do usuário corresponde ao esperado
        if ($user['tipo'] !== $type) {
            http_response_code(403);
            echo json_encode(['status' => 403, 'message' => "Acesso permitido apenas para {$type}s"]);
            exit;
        }
        
        return $user;
    }
    
    private static function decodeJWT($token) {
        try {
            // Decodifica o token usando a biblioteca Firebase JWT
            $secret = self::getJwtSecret();
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            // Converte para array
            $decodedArray = (array) $decoded;
            $data = (array) $decodedArray['data'];
            
            // Retorna no formato esperado pelos controllers
            return [
                'id' => $data['id'],
                'nome' => $data['nome'],
                'email' => $data['email'],
                'tipo' => $data['tipo'],
                // Adiciona campos específicos para compatibilidade
                'id_usuario' => $data['tipo'] === 'usuario' ? $data['id'] : null,
                'id_asilo' => $data['tipo'] === 'asilo' ? $data['id'] : null
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao decodificar JWT: " . $e->getMessage());
            return null;
        }
    }
}
?>
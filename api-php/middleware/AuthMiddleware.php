<?php

class AuthMiddleware {
    
    public static function requireAuth() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Token de autenticação não fornecido']);
            exit;
        }
        
        // Remove "Bearer " se presente
        $token = str_replace('Bearer ', '', $token);
        
        // Decodifica o token JWT (você precisa ter uma função para decodificar JWT)
        $user = self::decodeJWT($token);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Token inválido ou expirado']);
            exit;
        }
        
        return $user;
    }
    
    public static function requireType($type) {
        $user = self::requireAuth();
        
        // Verifica se o tipo do usuário corresponde ao esperado
        if ($type === 'usuario' && !isset($user['id_usuario'])) {
            http_response_code(403);
            echo json_encode(['status' => 403, 'message' => 'Acesso permitido apenas para usuários']);
            exit;
        }
        
        if ($type === 'asilo' && !isset($user['id_asilo'])) {
            http_response_code(403);
            echo json_encode(['status' => 403, 'message' => 'Acesso permitido apenas para asilos']);
            exit;
        }
        
        return $user;
    }
    
    private static function decodeJWT($token) {
        // Implementação básica - você deve usar uma biblioteca JWT real
        // Esta é uma implementação simplificada para demonstração
        
        try {
            // Divide o token JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            // Decodifica o payload
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
                return null;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
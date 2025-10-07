<?php

class AuthMiddleware {
    
    public static function requireAuth() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        // Debug
        error_log("🔐 AUTH DEBUG - Token recebido: " . ($token ? "PRESENTE" : "AUSENTE"));
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Token de autenticação não fornecido']);
            exit;
        }
        
        // Remove "Bearer " se presente
        $token = str_replace('Bearer ', '', $token);
        error_log("🔐 AUTH DEBUG - Token limpo: " . substr($token, 0, 20) . "...");
        
        // Decodifica o token
        $user = self::decodeJWT($token);
        
        if (!$user) {
            error_log("🔐 AUTH DEBUG - Token inválido ou expirado");
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Token inválido ou expirado']);
            exit;
        }
        
        error_log("🔐 AUTH DEBUG - Usuário autenticado: " . print_r($user, true));
        return $user;
    }
    
    public static function requireType($type) {
        $user = self::requireAuth();
        
        error_log("🔐 AUTH DEBUG - Verificando tipo: esperado {$type}, usuário: " . print_r($user, true));
        
        // CORREÇÃO: Acessar como array
        if (!isset($user['tipo'])) {
            error_log("🔐 AUTH DEBUG - Tipo de usuário não definido no token");
            http_response_code(403);
            echo json_encode(['status' => 403, 'message' => 'Tipo de usuário não definido']);
            exit;
        }
        
        // CORREÇÃO: Acessar como array
        if ($user['tipo'] !== $type) {
            error_log("🔐 AUTH DEBUG - Tipo incorreto: esperado {$type}, recebido {$user['tipo']}");
            http_response_code(403);
            echo json_encode(['status' => 403, 'message' => "Acesso permitido apenas para {$type}s"]);
            exit;
        }
        
        return $user;
    }
    
    private static function decodeJWT($token) {
        try {
            error_log("🔐 AUTH DEBUG - Decodificando token JWT...");
            
            // Divide o token JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                error_log("🔐 AUTH DEBUG - Token não tem 3 partes");
                return null;
            }
            
            // Decodifica o payload - JWT usa base64url
            $payload = $parts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $mod4 = strlen($payload) % 4;
            if ($mod4) {
                $payload .= str_repeat('=', 4 - $mod4);
            }
            
            $payloadDecoded = base64_decode($payload);
            $payloadData = json_decode($payloadDecoded, true);
            
            error_log("🔐 AUTH DEBUG - Payload decodificado: " . print_r($payloadData, true));
            
            if (!$payloadData) {
                error_log("🔐 AUTH DEBUG - Payload vazio ou inválido");
                return null;
            }
            
            // Verifica expiração
            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                error_log("🔐 AUTH DEBUG - Token expirado");
                return null;
            }
            
            // CORREÇÃO: Extrair dados da estrutura 'data' do seu LoginController
            $userData = [];
            
            if (isset($payloadData['data'])) {
                // Se existe estrutura 'data', extrai dela
                $userData = [
                    'id' => $payloadData['data']['id'] ?? null,
                    'tipo' => $payloadData['data']['tipo'] ?? null,
                    'nome' => $payloadData['data']['nome'] ?? 'Usuário',
                    'email' => $payloadData['data']['email'] ?? null
                ];
                error_log("🔐 AUTH DEBUG - Estrutura 'data' encontrada no token");
            } else {
                // Se não existe estrutura 'data', usa campos diretos (para compatibilidade)
                $userData = [
                    'id' => $payloadData['id'] ?? $payloadData['user_id'] ?? null,
                    'tipo' => $payloadData['tipo'] ?? $payloadData['type'] ?? null,
                    'nome' => $payloadData['nome'] ?? $payloadData['name'] ?? 'Usuário',
                    'email' => $payloadData['email'] ?? null
                ];
                error_log("🔐 AUTH DEBUG - Usando campos diretos do payload");
            }
            
            // Para compatibilidade com código existente
            if ($userData['tipo'] === 'usuario') {
                $userData['id_usuario'] = $userData['id'];
            } elseif ($userData['tipo'] === 'asilo') {
                $userData['id_asilo'] = $userData['id'];
            }
            
            error_log("🔐 AUTH DEBUG - Estrutura final do usuário: " . print_r($userData, true));
            
            return $userData;
            
        } catch (Exception $e) {
            error_log("🔐 AUTH DEBUG - Exceção ao decodificar token: " . $e->getMessage());
            return null;
        }

    }
    // Adicione esta função no AuthMiddleware como método público
public static function debugToken($token) {
    return self::decodeJWT($token);
}
}
?>
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AuthMiddleware
{
    private static $jwtSecret;

    public static function init()
    {
        self::$jwtSecret = $_ENV['JWT_SECRET'] ?? null;
        
        if (!self::$jwtSecret) {
            throw new Exception("JWT_SECRET não definido no ambiente.");
        }
    }

    /**
     * Valida o token JWT do header Authorization
     * Retorna os dados do usuário decodificados ou null se inválido
     */
    public static function authenticate()
    {
        self::init();

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        // Formato esperado: "Bearer {token}"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(self::$jwtSecret, 'HS256'));
            return $decoded->data ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Middleware que exige autenticação
     * Retorna erro 401 se não autenticado
     */
    public static function requireAuth()
    {
        $user = self::authenticate();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'status' => 401,
                'message' => 'Não autorizado. Token inválido ou ausente.'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Middleware que exige tipo específico de usuário
     */
    public static function requireType($tipo)
    {
        $user = self::requireAuth();
        
        if ($user->tipo !== $tipo) {
            http_response_code(403);
            echo json_encode([
                'status' => 403,
                'message' => "Acesso negado. Apenas {$tipo}s podem acessar este recurso."
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Middleware opcional - retorna usuário se autenticado, null caso contrário
     */
    public static function optionalAuth()
    {
        return self::authenticate();
    }
}

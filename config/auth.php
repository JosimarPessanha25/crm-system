<?php

declare(strict_types=1);

/**
 * JWT Authentication System for CRM
 */

class JWTAuth {
    private $secretKey;
    private $algorithm = 'HS256';
    private $expiration = 3600; // 1 hour
    
    public function __construct($secretKey = null) {
        $this->secretKey = $secretKey ?: 'crm_secret_key_' . date('Y');
    }
    
    /**
     * Generate JWT Token
     */
    public function generateToken($userId, $email, $role = 'user') {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + $this->expiration
        ]);
        
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    /**
     * Validate JWT Token
     */
    public function validateToken($token) {
        if (!$token) return false;
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) return false;
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        if (!$payload) return false;
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) return false;
        
        return $payload;
    }
    
    /**
     * Extract token from Authorization header
     */
    public function extractToken($authHeader) {
        if (!$authHeader) return null;
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

/**
 * Authentication middleware for Slim Framework
 */
class AuthMiddleware {
    private $jwt;
    private $publicPaths;
    
    public function __construct(JWTAuth $jwt, array $publicPaths = []) {
        $this->jwt = $jwt;
        $this->publicPaths = array_merge([
            '/api/auth/login',
            '/api/health',
            '/app',
            '/dashboard',
            '/'
        ], $publicPaths);
    }
    
    public function __invoke($request, $handler) {
        $uri = $request->getUri()->getPath();
        
        // Allow public paths
        foreach ($this->publicPaths as $path) {
            if (strpos($uri, $path) === 0) {
                return $handler->handle($request);
            }
        }
        
        // Check for token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = $this->jwt->extractToken($authHeader);
        
        if (!$token) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $payload = $this->jwt->validateToken($token);
        if (!$payload) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Add user info to request attributes
        $request = $request->withAttribute('user', $payload);
        
        return $handler->handle($request);
    }
}

/**
 * User Authentication Service
 */
class UserService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Authenticate user by email and password
     */
    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        // In a real app, use password_verify() with hashed passwords
        // For demo purposes, we'll accept any password for existing users
        return $user;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        $sql = "INSERT INTO usuarios (nome, email, password_hash, role, ativo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        
        // In production, hash the password properly
        $passwordHash = password_hash($data['password'] ?? 'demo123', PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $data['nome'],
            $data['email'],
            $passwordHash,
            $data['role'] ?? 'user'
        ]);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT id, nome, email, role, ativo, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * Initialize authentication system
 */
function initializeAuth($container) {
    // Create JWT instance
    $jwt = new JWTAuth(getenv('JWT_SECRET') ?: 'crm_demo_secret_2024');
    $container->set('jwt', $jwt);
    
    // Create user service
    $userService = new UserService($container->get('database'));
    $container->set('userService', $userService);
    
    // Create auth middleware
    $authMiddleware = new AuthMiddleware($jwt);
    $container->set('authMiddleware', $authMiddleware);
    
    return $container;
}
<?php

declare(strict_types=1);

/**
 * Unified CRM System Bootstrap
 * Complete integration of database, API, auth, and frontend
 */

// System constants
define('CRM_VERSION', '1.0.0');
define('CRM_BASE_DIR', dirname(__DIR__));
define('CRM_PUBLIC_DIR', __DIR__);
define('CRM_CONFIG_DIR', CRM_BASE_DIR . '/config');

/**
 * System initialization with comprehensive error handling
 */
function initializeCRMSystem() {
    try {
        // 1. Initialize database with auto-migrations
        require_once CRM_CONFIG_DIR . '/database.php';
        $migrationRunner = new MigrationRunner();
        $migrationRunner->runAllMigrations();
        
        // 2. Setup authentication
        require_once CRM_CONFIG_DIR . '/auth.php';
        
        // 3. Initialize container
        $container = new CRMContainer();
        $container->set('database', $migrationRunner->getDatabase());
        
        // Add auth services
        $container = initializeAuth($container);
        
        return $container;
        
    } catch (Exception $e) {
        error_log("CRM System initialization failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Simple dependency injection container
 */
class CRMContainer {
    private $services = [];
    
    public function set(string $name, $service) {
        $this->services[$name] = $service;
    }
    
    public function get(string $name) {
        return $this->services[$name] ?? null;
    }
    
    public function has(string $name): bool {
        return isset($this->services[$name]);
    }
}

/**
 * Unified router for both API and frontend
 */
function handleRequest($uri, $method = 'GET') {
    $container = initializeCRMSystem();
    
    if (!$container) {
        return errorResponse('System initialization failed', 500);
    }
    
    // Frontend routes
    if ($uri === '/' || $uri === '/dashboard') {
        return redirect('/app');
    }
    
    if ($uri === '/app') {
        $appFile = CRM_PUBLIC_DIR . '/app.html';
        if (file_exists($appFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($appFile);
            return;
        }
        return errorResponse('Dashboard not found', 404);
    }
    
    // API routes
    if (strpos($uri, '/api/') === 0) {
        header('Content-Type: application/json');
        
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle OPTIONS preflight
        if ($method === 'OPTIONS') {
            http_response_code(200);
            return;
        }
        
        return handleAPIRoute($uri, $method, $container);
    }
    
    // Static files
    $filePath = CRM_PUBLIC_DIR . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $mimeType = getMimeType($filePath);
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
        return;
    }
    
    return errorResponse('Route not found', 404);
}

/**
 * Handle API routes
 */
function handleAPIRoute($uri, $method, $container) {
    $db = $container->get('database');
    
    // Health check
    if ($uri === '/api/health') {
        return jsonResponse([
            'status' => 'healthy',
            'version' => CRM_VERSION,
            'timestamp' => date('c'),
            'database' => $db ? 'connected' : 'disconnected'
        ]);
    }
    
    // Authentication
    if ($uri === '/api/auth/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (!$email || !$password) {
            return jsonResponse(['success' => false, 'message' => 'Email and password required'], 400);
        }
        
        // Simple demo authentication - any user with demo@test.com / demo123
        if ($email === 'demo@test.com' && $password === 'demo123') {
            $jwt = $container->get('jwt');
            $token = $jwt ? $jwt->generateToken(1, $email, 'admin') : 'demo_token_' . time();
            
            return jsonResponse([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => 1,
                    'nome' => 'Demo User',
                    'email' => $email,
                    'role' => 'admin'
                ]
            ]);
        }
        
        return jsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
    }
    
    // CRUD endpoints
    if (preg_match('#^/api/(usuarios|empresas|contatos|oportunidades)$#', $uri, $matches)) {
        $entity = $matches[1];
        
        if ($method === 'GET') {
            $data = fetchEntityData($db, $entity);
            return jsonResponse($data);
        }
        
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = createEntity($db, $entity, $data);
            return jsonResponse(['success' => true, 'id' => $id]);
        }
    }
    
    // Dashboard stats
    if ($uri === '/api/dashboard/stats') {
        $stats = [
            'contatos' => countRecords($db, 'contatos'),
            'empresas' => countRecords($db, 'empresas'),
            'oportunidades_abertas' => countRecords($db, 'oportunidades', "WHERE estagio NOT IN ('closed_won', 'closed_lost')"),
            'valor_pipeline' => calculatePipeline($db)
        ];
        return jsonResponse($stats);
    }
    
    return jsonResponse(['error' => 'Endpoint not found'], 404);
}

/**
 * Fetch entity data from database
 */
function fetchEntityData($db, $entity) {
    if (!$db) return [];
    
    try {
        $sql = "SELECT * FROM {$entity} ORDER BY created_at DESC LIMIT 100";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching {$entity}: " . $e->getMessage());
        return [];
    }
}

/**
 * Create new entity record
 */
function createEntity($db, $entity, $data) {
    if (!$db || !$data) return false;
    
    try {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$entity} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log("Error creating {$entity}: " . $e->getMessage());
        return false;
    }
}

/**
 * Count records in table
 */
function countRecords($db, $table, $where = '') {
    if (!$db) return 0;
    
    try {
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        $stmt = $db->query($sql);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Calculate pipeline value
 */
function calculatePipeline($db) {
    if (!$db) return 0;
    
    try {
        $sql = "SELECT SUM(valor_estimado) FROM oportunidades WHERE estagio NOT IN ('closed_won', 'closed_lost')";
        $stmt = $db->query($sql);
        return (float) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Helper functions
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
}

function errorResponse($message, $code = 500) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
}

function redirect($location) {
    header("Location: {$location}");
    http_response_code(302);
}

function getMimeType($file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'html' => 'text/html',
        'htm' => 'text/html'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}

// Auto-execute if accessed directly
if (basename($_SERVER['SCRIPT_NAME']) === 'bootstrap.php') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    handleRequest($uri, $method);
}
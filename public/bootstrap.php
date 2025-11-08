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
        $databaseFile = CRM_CONFIG_DIR . '/database.php';
        if (file_exists($databaseFile)) {
            require_once $databaseFile;
            if (class_exists('MigrationRunner')) {
                $migrationRunner = new MigrationRunner();
                $migrationRunner->runAllMigrations();
                $database = $migrationRunner->getDatabase();
            } else {
                $database = createSimpleDatabase();
            }
        } else {
            $database = createSimpleDatabase();
        }
        
        // 2. Initialize container
        $container = new CRMContainer();
        $container->set('database', $database);
        
        // 3. Setup authentication (simple version)
        $authFile = CRM_CONFIG_DIR . '/auth.php';
        if (file_exists($authFile)) {
            require_once $authFile;
            if (function_exists('initializeAuth')) {
                $container = initializeAuth($container);
            }
        }
        
        // Add simple JWT if auth system not available
        if (!$container->has('jwt')) {
            $container->set('jwt', new SimpleJWT());
        }
        
        return $container;
        
    } catch (Exception $e) {
        error_log("CRM System initialization failed: " . $e->getMessage());
        return createFallbackContainer();
    }
}

/**
 * Create simple database with demo data
 */
function createSimpleDatabase() {
    try {
        $dbPath = CRM_BASE_DIR . '/database/crm.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables with demo data
        createDemoTables($pdo);
        insertDemoData($pdo);
        
        return $pdo;
    } catch (Exception $e) {
        error_log("Database creation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Create demo tables
 */
function createDemoTables($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT,
            role TEXT DEFAULT 'user',
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS empresas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            cnpj_cpf TEXT,
            setor TEXT,
            telefone TEXT,
            email TEXT,
            website TEXT,
            endereco TEXT,
            cidade TEXT,
            estado TEXT,
            cep TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS contatos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT,
            telefone TEXT,
            cargo TEXT,
            empresa_id INTEGER,
            status TEXT DEFAULT 'ativo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS oportunidades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT,
            valor_estimado DECIMAL(15,2) DEFAULT 0,
            estagio TEXT DEFAULT 'prospecting',
            probabilidade INTEGER DEFAULT 50,
            data_fechamento_prevista DATE,
            contato_id INTEGER,
            empresa_id INTEGER,
            usuario_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contato_id) REFERENCES contatos(id),
            FOREIGN KEY (empresa_id) REFERENCES empresas(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}

/**
 * Insert comprehensive demo data
 */
function insertDemoData($pdo) {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() > 0) {
        return; // Data already exists
    }
    
    // Demo users
    $usuarios = [
        ['Demo Admin', 'demo@test.com', password_hash('demo123', PASSWORD_DEFAULT), 'admin'],
        ['João Silva', 'joao@empresa.com', password_hash('123456', PASSWORD_DEFAULT), 'user'],
        ['Maria Santos', 'maria@vendas.com', password_hash('123456', PASSWORD_DEFAULT), 'user'],
        ['Pedro Costa', 'pedro@marketing.com', password_hash('123456', PASSWORD_DEFAULT), 'user']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, password_hash, role) VALUES (?, ?, ?, ?)");
    foreach ($usuarios as $user) {
        $stmt->execute($user);
    }
    
    // Demo empresas
    $empresas = [
        ['TechCorp Ltda', '12.345.678/0001-90', 'Tecnologia', '(11) 99999-9999', 'contato@techcorp.com', 'https://techcorp.com', 'Av. Paulista, 1000', 'São Paulo', 'SP', '01310-100'],
        ['Inovação S.A.', '98.765.432/0001-10', 'Consultoria', '(21) 88888-8888', 'info@inovacao.com', 'https://inovacao.com', 'Rua das Flores, 200', 'Rio de Janeiro', 'RJ', '20040-020'],
        ['StartupX', '11.222.333/0001-44', 'Software', '(11) 77777-7777', 'hello@startupx.com', 'https://startupx.com', 'Rua Augusta, 500', 'São Paulo', 'SP', '01305-000'],
        ['Digital Plus', '55.666.777/0001-88', 'Marketing Digital', '(85) 66666-6666', 'contato@digitalplus.com', 'https://digitalplus.com', 'Av. Beira Mar, 300', 'Fortaleza', 'CE', '60165-121'],
        ['CloudSoft', '99.111.222/0001-33', 'Cloud Computing', '(31) 55555-5555', 'info@cloudsoft.com', 'https://cloudsoft.com', 'Rua da Bahia, 800', 'Belo Horizonte', 'MG', '30160-012']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO empresas (nome, cnpj_cpf, setor, telefone, email, website, endereco, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($empresas as $empresa) {
        $stmt->execute($empresa);
    }
    
    // Demo contatos
    $contatos = [
        ['Carlos Mendes', 'carlos@techcorp.com', '(11) 91234-5678', 'CTO', 1],
        ['Ana Paula', 'ana@techcorp.com', '(11) 91234-5679', 'Gerente de Vendas', 1],
        ['Roberto Lima', 'roberto@inovacao.com', '(21) 98765-4321', 'Diretor', 2],
        ['Fernanda Souza', 'fernanda@inovacao.com', '(21) 98765-4322', 'Coordenadora', 2],
        ['Lucas Oliveira', 'lucas@startupx.com', '(11) 95555-1111', 'CEO', 3],
        ['Juliana Pereira', 'juliana@startupx.com', '(11) 95555-2222', 'Product Manager', 3],
        ['Rafael Santos', 'rafael@digitalplus.com', '(85) 94444-3333', 'Diretor de Marketing', 4],
        ['Camila Silva', 'camila@digitalplus.com', '(85) 94444-4444', 'Analista', 4],
        ['Bruno Costa', 'bruno@cloudsoft.com', '(31) 93333-5555', 'Arquiteto de Soluções', 5],
        ['Patricia Rocha', 'patricia@cloudsoft.com', '(31) 93333-6666', 'Gerente Comercial', 5]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO contatos (nome, email, telefone, cargo, empresa_id) VALUES (?, ?, ?, ?, ?)");
    foreach ($contatos as $contato) {
        $stmt->execute($contato);
    }
    
    // Demo oportunidades
    $oportunidades = [
        ['Sistema de CRM Personalizado', 'Desenvolvimento de sistema CRM para gestão de vendas', 85000.00, 'proposal', 80, '2025-12-15', 1, 1, 1],
        ['Consultoria em Digital Transformation', 'Projeto de transformação digital completa', 120000.00, 'negotiation', 75, '2025-11-30', 3, 2, 2],
        ['Aplicativo Mobile Inovador', 'Desenvolvimento de app para gestão interna', 65000.00, 'qualification', 60, '2025-12-30', 5, 3, 1],
        ['Campanha de Marketing Digital', 'Estratégia completa de marketing digital', 45000.00, 'proposal', 70, '2025-11-25', 7, 4, 3],
        ['Migração para Cloud', 'Migração completa da infraestrutura para cloud', 150000.00, 'prospecting', 40, '2026-01-15', 9, 5, 2],
        ['E-commerce Platform', 'Plataforma de e-commerce personalizada', 95000.00, 'qualification', 65, '2025-12-20', 2, 1, 1],
        ['Data Analytics Solution', 'Solução completa de análise de dados', 78000.00, 'proposal', 85, '2025-11-28', 4, 2, 2],
        ['Mobile App Redesign', 'Redesign completo do aplicativo mobile', 52000.00, 'negotiation', 90, '2025-11-22', 6, 3, 3],
        ['SEO & Content Strategy', 'Estratégia completa de SEO e conteúdo', 38000.00, 'closed_won', 100, '2025-11-15', 8, 4, 4],
        ['Infrastructure Modernization', 'Modernização da infraestrutura de TI', 180000.00, 'prospecting', 35, '2026-02-01', 10, 5, 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO oportunidades (titulo, descricao, valor_estimado, estagio, probabilidade, data_fechamento_prevista, contato_id, empresa_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($oportunidades as $oportunidade) {
        $stmt->execute($oportunidade);
    }
}

/**
 * Create fallback container
 */
function createFallbackContainer() {
    $container = new CRMContainer();
    $container->set('database', null);
    $container->set('jwt', new SimpleJWT());
    return $container;
}

/**
 * Simple JWT implementation
 */
class SimpleJWT {
    public function generateToken($userId, $email, $role = 'user') {
        return base64_encode(json_encode([
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
            'exp' => time() + 3600
        ]));
    }
    
    public function validateToken($token) {
        try {
            $payload = json_decode(base64_decode($token), true);
            if ($payload && isset($payload['exp']) && $payload['exp'] > time()) {
                return $payload;
            }
        } catch (Exception $e) {
            // Invalid token
        }
        return false;
    }
    
    public function extractToken($authHeader) {
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
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
 * Fetch entity data from database with relationships
 */
function fetchEntityData($db, $entity) {
    if (!$db) return [];
    
    try {
        switch ($entity) {
            case 'contatos':
                $sql = "SELECT c.*, e.nome as empresa_nome 
                       FROM contatos c 
                       LEFT JOIN empresas e ON c.empresa_id = e.id 
                       ORDER BY c.created_at DESC LIMIT 100";
                break;
                
            case 'oportunidades':
                $sql = "SELECT o.*, c.nome as contato_nome, e.nome as empresa_nome, u.nome as usuario_nome
                       FROM oportunidades o 
                       LEFT JOIN contatos c ON o.contato_id = c.id
                       LEFT JOIN empresas e ON o.empresa_id = e.id
                       LEFT JOIN usuarios u ON o.usuario_id = u.id
                       ORDER BY o.created_at DESC LIMIT 100";
                break;
                
            default:
                $sql = "SELECT * FROM {$entity} ORDER BY created_at DESC LIMIT 100";
        }
        
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
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'bootstrap.php') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    handleRequest($uri, $method);
}
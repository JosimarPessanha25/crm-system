<?php

declare(strict_types=1);

/**
 * CRM System - Unified Entry Point
 * Routes to bootstrap system for complete functionality
 */

// Check if bootstrap exists and delegate to it
$bootstrapFile = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrapFile)) {
    require_once $bootstrapFile;
    
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    handleRequest($uri, $method);
    exit;
}

/**
 * CRM System Entry Point
 * Robust initialization with fallbacks
 */

// Try to load Composer autoloader first
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
$composerLoaded = false;

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    $composerLoaded = true;
    
    // Load environment variables with Composer dependencies
    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        } catch (Exception $e) {
            error_log('Failed to load .env file: ' . $e->getMessage());
        }
    }
} else {
    // Fallback to manual autoloader
    $manualAutoloader = __DIR__ . '/../config/autoloader.php';
    if (file_exists($manualAutoloader)) {
        require_once $manualAutoloader;
        error_log('Running with manual autoloader - Composer not available');
    }
}

// Create container based on available dependencies
if ($composerLoaded && class_exists('DI\Container')) {
    $container = new \DI\Container();
} else {
    // Simple container for fallback
    $container = new class {
        private array $services = [];
        
        public function set(string $name, $value): void {
            $this->services[$name] = $value;
        }
        
        public function get(string $name) {
            return $this->services[$name] ?? null;
        }
        
        public function has(string $name): bool {
            return array_key_exists($name, $this->services);
        }
    };
}

// Load services with fallbacks
try {
    // Logger setup
    $loggerPaths = [
        __DIR__ . '/logger.php',
        __DIR__ . '/../config/logger.php',
        __DIR__ . '/../app/config/logger.php'
    ];
    
    $logger = null;
    foreach ($loggerPaths as $path) {
        if (file_exists($path)) {
            $logger = require $path;
            break;
        }
    }
    
    if (!$logger) {
        $logger = new class {
            public function info($msg) { error_log("INFO: $msg"); }
            public function error($msg) { error_log("ERROR: $msg"); }
            public function debug($msg) { error_log("DEBUG: $msg"); }
        };
    }
    
    $container->set('logger', $logger);
    
    // Database setup
    $databasePaths = [
        __DIR__ . '/database.php',
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../app/config/database.php'
    ];
    
    $database = null;
    foreach ($databasePaths as $path) {
        if (file_exists($path)) {
            $database = require $path;
            break;
        }
    }
    
    $container->set('database', $database);
    
    // Bootstrap database if available
    if ($database && method_exists($database, 'setAsGlobal')) {
        $database->setAsGlobal();
        $database->bootEloquent();
    }

    // Route handling
    handleRequest($composerLoaded, $container);

} catch (Exception $e) {
    showError($e, $composerLoaded ?? false);
}

function handleRequest(bool $composerLoaded, $container): void {
    if ($composerLoaded && class_exists('Slim\Factory\AppFactory')) {
        // Try full Slim Framework
        try {
            $app = \Slim\Factory\AppFactory::create();
            
            // Basic health check route
            $app->get('/api/health', function ($request, $response) use ($composerLoaded) {
                $response->getBody()->write(json_encode([
                    'status' => 'healthy',
                    'timestamp' => date('c'),
                    'version' => '1.0.0',
                    'environment' => $_ENV['APP_ENV'] ?? 'production',
                    'composer' => $composerLoaded
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            });
            
            // Serve frontend
            $app->get('/', function ($request, $response) use ($composerLoaded) {
                $response->getBody()->write(getStatusPage($composerLoaded));
                return $response->withHeader('Content-Type', 'text/html');
            });
            
            $app->run();
            return;
            
        } catch (Exception $e) {
            error_log('Slim error: ' . $e->getMessage());
        }
    }
    
    // Simple fallback routing
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (strpos($uri, '/api/health') === 0 && $method === 'GET') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'composer_loaded' => $composerLoaded,
            'routing' => 'fallback'
        ]);
    } else {
        header('Content-Type: text/html');
        echo getStatusPage($composerLoaded);
    }
}

function getStatusPage(bool $composerLoaded): string {
    return '<!DOCTYPE html>
<html>
<head>
    <title>CRM System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0; padding: 40px; background: #f5f5f5; 
        }
        .container { max-width: 800px; margin: 0 auto; }
        .status { 
            background: white; padding: 30px; border-radius: 12px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .header { color: #2563eb; margin-bottom: 20px; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .info-item { padding: 15px; background: #f8fafc; border-radius: 8px; }
        .info-item strong { color: #1e293b; }
        .api-section { 
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .endpoint { 
            background: #f1f5f9; padding: 12px; border-radius: 6px; 
            margin: 10px 0; font-family: monospace;
        }
        .endpoint a { color: #2563eb; text-decoration: none; }
        .endpoint a:hover { text-decoration: underline; }
        .status-badge { 
            display: inline-block; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: bold;
        }
        .online { background: #dcfce7; color: #166534; }
        .warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="status">
            <h1 class="header">🚀 CRM System</h1>
            <span class="status-badge online">System Online</span>
            
            <div class="info">
                <div class="info-item">
                    <strong>Version:</strong> 1.0.0<br>
                    <strong>Environment:</strong> ' . ($_ENV['APP_ENV'] ?? 'production') . '
                </div>
                <div class="info-item">
                    <strong>PHP:</strong> ' . PHP_VERSION . '<br>
                    <strong>Composer:</strong> ' . ($composerLoaded ? '✅ Loaded' : '❌ Fallback') . '
                </div>
            </div>
        </div>
        
        <div class="api-section">
            <h2>📡 API Status</h2>
            <p>Basic API endpoints are available:</p>
            
            <div class="endpoint">
                <strong>GET</strong> <a href="/api/health">/api/health</a> - System health check
            </div>
            
            <div class="endpoint">
                <strong>Framework:</strong> ' . ($composerLoaded ? 'Slim Framework' : 'Basic Routing') . '
            </div>
        </div>
    </div>
</body>
</html>';
}

function showError(Exception $e, bool $composerLoaded): void {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Application Error',
        'message' => $e->getMessage(),
        'composer_loaded' => $composerLoaded,
        'timestamp' => date('c')
    ]);
}
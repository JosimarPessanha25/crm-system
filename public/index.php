<?php

declare(strict_types=1);

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
    require_once __DIR__ . '/../config/autoloader.php';
    error_log('Running without Composer - using manual autoloader');
}

// Create container based on available dependencies
if ($composerLoaded && class_exists('DI\Container')) {
    $container = new \DI\Container();
} else {
    // Simple container implementation for fallback
    $container = new class {
        private array $services = [];
        
        public function set(string $name, $value): void {
            $this->services[$name] = $value;
        }
        
        public function get(string $name) {
            return $this->services[$name] ?? null;
        }
    };
}

// Load services
try {
    $container->set('logger', require __DIR__ . '/logger.php');
    $container->set('database', require __DIR__ . '/database.php');

    // Bootstrap Eloquent ORM if available
    $capsule = $container->get('database');
    if ($capsule && method_exists($capsule, 'setAsGlobal')) {
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    // Create Slim app
    if ($composerLoaded && class_exists('Slim\Factory\AppFactory')) {
        \Slim\Factory\AppFactory::setContainer($container);
        $app = \Slim\Factory\AppFactory::create();
        
        // Load routes
        $routeLoader = require __DIR__ . '/../config/routes.php';
        $routeLoader($app, $container);
        
        // Run the application
        $app->run();
    } else {
        // Simple fallback response
        header('Content-Type: text/html');
        echo '<!DOCTYPE html>
<html>
<head>
    <title>CRM System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .status { background: #e7f3ff; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="status">
        <h1>🚀 CRM System</h1>
        <p><strong>Status:</strong> Online</p>
        <p><strong>Version:</strong> 1.0.0</p>
        <p><strong>Environment:</strong> ' . ($_ENV['APP_ENV'] ?? 'production') . '</p>
        <p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>
        <p><strong>Composer:</strong> ' . ($composerLoaded ? '✅ Loaded' : '❌ Not Available') . '</p>
    </div>
</body>
</html>';
    }

} catch (Exception $e) {
    // Fallback error page
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Application initialization failed',
        'message' => $e->getMessage(),
        'debug' => [
            'composer_loaded' => $composerLoaded,
            'autoload_path' => $autoloadPath,
            'autoload_exists' => file_exists($autoloadPath)
        ]
    ]);
}
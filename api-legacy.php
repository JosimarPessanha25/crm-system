<?php
/**
 * CRM System API Entry Point
 * This file serves as the main entry point for all API requests
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json');

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Load autoloader
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Load configuration
    require_once __DIR__ . '/config/bootstrap.php';
    
    // Get container
    $container = require __DIR__ . '/config/container.php';
    
    // Load routes
    $routes = require __DIR__ . '/config/routes.php';
    
    // Simple router implementation
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove /api prefix if present
    $path = preg_replace('#^/api#', '', $path);
    
    // Default route
    if (empty($path) || $path === '/') {
        $response = [
            'status' => 'success',
            'message' => 'CRM System API v1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => '/auth/login, /auth/logout, /auth/refresh',
                'users' => '/users',
                'companies' => '/companies', 
                'contacts' => '/contacts',
                'opportunities' => '/opportunities',
                'activities' => '/activities',
                'dashboard' => '/dashboard/stats, /dashboard/pipeline'
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    // Health check endpoint
    if ($path === '/health') {
        $response = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'database' => 'connected', // This should be checked in real implementation
            'uptime' => 'running'
        ];
        
        echo json_encode($response);
        exit();
    }
    
    // Status endpoint
    if ($path === '/status') {
        $response = [
            'status' => 'operational',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        echo json_encode($response);
        exit();
    }
    
    // Route matching logic
    $routeFound = false;
    
    foreach ($routes as $routePattern => $routeConfig) {
        // Convert route pattern to regex
        $pattern = '#^' . preg_replace('#\{([^}]+)\}#', '([^/]+)', $routePattern) . '$#';
        
        if (preg_match($pattern, $path, $matches)) {
            $routeFound = true;
            
            // Check if method is allowed
            if (!in_array($method, $routeConfig['methods'])) {
                http_response_code(405);
                echo json_encode([
                    'error' => 'Method not allowed',
                    'message' => "Method $method not allowed for this endpoint"
                ]);
                exit();
            }
            
            // Extract controller and action
            list($controllerName, $actionName) = explode('@', $routeConfig['handler']);
            
            // Get controller from container
            if (!$container->has($controllerName)) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Controller not found',
                    'message' => "Controller $controllerName not registered"
                ]);
                exit();
            }
            
            $controller = $container->get($controllerName);
            
            // Check if action exists
            if (!method_exists($controller, $actionName)) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Action not found',
                    'message' => "Action $actionName not found in controller $controllerName"
                ]);
                exit();
            }
            
            // Extract route parameters
            array_shift($matches); // Remove full match
            $params = $matches;
            
            // Call controller action
            $result = call_user_func_array([$controller, $actionName], $params);
            
            // Send response
            if (is_array($result) || is_object($result)) {
                echo json_encode($result);
            } else {
                echo $result;
            }
            
            exit();
        }
    }
    
    // Route not found
    if (!$routeFound) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found',
            'message' => "No route matches $method $path",
            'available_routes' => array_keys($routes)
        ]);
    }
    
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    
    $response = [
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ];
    
    // Add debug info in development
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo json_encode($response);
} catch (Throwable $e) {
    // Handle fatal errors
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage()
    ]);
}
?>
<?php

/**
 * Manual Autoloader for CRM System
 * This is a fallback when Composer is not available
 */

declare(strict_types=1);

// Define base directory
define('APP_ROOT', __DIR__ . '/..');

// Register the autoloader
spl_autoload_register(function (string $className): void {
    // Convert namespace to file path
    $classPath = str_replace(['App\\', '\\'], ['', '/'], $className);
    
    // Convert to lowercase for directory structure
    $parts = explode('/', $classPath);
    $fileName = array_pop($parts);
    $directory = strtolower(implode('/', $parts));
    
    // Build full file path
    $filePath = APP_ROOT . '/app/' . $directory . '/' . $fileName . '.php';
    
    // Include the file if it exists
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// Define basic environment variables if .env not loaded
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        return $_ENV[$key] ?? $default;
    }
}

// Load basic environment variables manually
$_ENV['APP_ENV'] = 'development';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'crm_system';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';
$_ENV['JWT_SECRET'] = 'your-very-secure-jwt-secret-key-256-bits-long-change-this-in-production';
$_ENV['RATE_LIMIT_MAX_REQUESTS'] = '100';
$_ENV['RATE_LIMIT_WINDOW_SECONDS'] = '3600';
$_ENV['REDIS_HOST'] = '127.0.0.1';
$_ENV['REDIS_PORT'] = '6379';
<?php
/**
 * Render.com Startup Script
 * This file ensures the CRM system starts properly on Render
 */

// Set timezone for Brazilian users
date_default_timezone_set('America/Sao_Paulo');

// Configure PHP for production
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Create necessary directories
$dirs = [
    __DIR__ . '/database',
    __DIR__ . '/storage',
    __DIR__ . '/storage/logs'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Set log file
ini_set('error_log', __DIR__ . '/storage/logs/error.log');

// Delegate to bootstrap
require_once __DIR__ . '/public/bootstrap.php';

echo "CRM System initialized successfully for Render.com\n";
?>
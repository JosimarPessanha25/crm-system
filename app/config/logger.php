<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;

$logger = new Logger($_ENV['LOG_CHANNEL'] ?? 'crm');

// Add unique identifier processor
$logger->pushProcessor(new UidProcessor());

// Add request ID processor if available
$logger->pushProcessor(function ($record) {
    if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
        $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'];
    }
    return $record;
});

// Set log level
$logLevel = match (strtolower($_ENV['LOG_LEVEL'] ?? 'info')) {
    'debug' => Logger::DEBUG,
    'info' => Logger::INFO,
    'notice' => Logger::NOTICE,
    'warning' => Logger::WARNING,
    'error' => Logger::ERROR,
    'critical' => Logger::CRITICAL,
    'alert' => Logger::ALERT,
    'emergency' => Logger::EMERGENCY,
    default => Logger::INFO,
};

// Console handler for development
if (($_ENV['APP_ENV'] ?? 'local') === 'local') {
    $consoleHandler = new StreamHandler('php://stderr', $logLevel);
    $consoleHandler->setFormatter(new JsonFormatter());
    $logger->pushHandler($consoleHandler);
}

// File handler
$fileHandler = new RotatingFileHandler(
    __DIR__ . '/../../storage/logs/app.log',
    30,
    $logLevel
);
$fileHandler->setFormatter(new JsonFormatter());
$logger->pushHandler($fileHandler);

return $logger;
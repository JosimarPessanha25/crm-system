<?php

declare(strict_types=1);

/**
 * Simple Logger Implementation
 */
return new class {
    private string $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../storage/logs/app.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory(): void {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }
    
    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }
    
    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }
    
    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context);
    }
    
    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }
    
    private function log(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] $level: $message$contextStr" . PHP_EOL;
        
        // Try to write to file, fallback to error_log
        if (is_writable(dirname($this->logFile)) || file_exists($this->logFile)) {
            @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } else {
            error_log("[$level] $message$contextStr");
        }
    }
};
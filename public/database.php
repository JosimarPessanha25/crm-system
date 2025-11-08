<?php

declare(strict_types=1);

/**
 * Simple Database Connection
 */

// Create storage directory for SQLite
$storageDir = __DIR__ . '/../database';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

$databasePath = $storageDir . '/crm_system.db';

try {
    // Try to create a simple PDO connection
    $pdo = new PDO("sqlite:$databasePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create basic table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS health_check (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            status TEXT DEFAULT 'ok',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Simple database wrapper
    return new class($pdo) {
        private PDO $connection;
        
        public function __construct(PDO $connection) {
            $this->connection = $connection;
        }
        
        public function getConnection(): PDO {
            return $this->connection;
        }
        
        public function setAsGlobal(): void {
            // Placeholder for Eloquent compatibility
        }
        
        public function bootEloquent(): void {
            // Placeholder for Eloquent compatibility
        }
        
        public function query(string $sql, array $params = []): array {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        
        public function execute(string $sql, array $params = []): int {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }
        
        public function lastInsertId(): string {
            return $this->connection->lastInsertId();
        }
    };

} catch (Exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    
    // Return a null database wrapper
    return new class {
        public function setAsGlobal(): void {}
        public function bootEloquent(): void {}
        public function getConnection(): ?PDO { return null; }
        public function query(string $sql, array $params = []): array { return []; }
        public function execute(string $sql, array $params = []): int { return 0; }
        public function lastInsertId(): string { return '0'; }
    };
}
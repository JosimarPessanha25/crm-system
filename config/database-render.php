<?php
/**
 * Database Configuration for Multiple Environments
 */

// Detect environment
$environment = $_ENV['APP_ENV'] ?? 'development';

if ($environment === 'production' || isset($_ENV['RENDER'])) {
    // Render.com production configuration (SQLite)
    $config = [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../database/crm_system.db',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci'
    ];
} elseif (getenv('DATABASE_URL')) {
    // Parse DATABASE_URL (for other cloud providers)
    $url = parse_url(getenv('DATABASE_URL'));
    $config = [
        'driver' => 'mysql',
        'host' => $url['host'],
        'port' => $url['port'] ?? 3306,
        'database' => ltrim($url['path'], '/'),
        'username' => $url['user'],
        'password' => $url['pass'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ];
} else {
    // Local development (MySQL/SQLite)
    $config = [
        'driver' => getenv('DB_TYPE') ?: 'sqlite',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 3306,
        'database' => getenv('DB_NAME') ?: __DIR__ . '/../database/crm_system.db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ];
}

/**
 * Create PDO connection
 */
function getConnection() {
    global $config;
    
    try {
        if ($config['driver'] === 'sqlite') {
            // SQLite connection
            $dsn = "sqlite:" . $config['database'];
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign keys for SQLite
            $pdo->exec('PRAGMA foreign_keys = ON');
            
        } else {
            // MySQL connection
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['charset'] . " COLLATE " . $config['collation']
            ];
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        }
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

return $config;
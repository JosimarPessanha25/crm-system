<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;

return [
    'database' => function (): Capsule {
        $capsule = new Capsule();
        
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'crm_system',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
        
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        return $capsule;
    },
    
    'logger' => function (): Logger {
        return require __DIR__ . '/logger.php';
    },
    
    'redis' => function () {
        if (!extension_loaded('redis')) {
            return null;
        }
        
        try {
            $redis = new \Predis\Client($_ENV['REDIS_URL'] ?? 'redis://127.0.0.1:6379');
            $redis->ping();
            return $redis;
        } catch (Exception) {
            return null;
        }
    },
];
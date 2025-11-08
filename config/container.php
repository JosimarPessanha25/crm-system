<?php

declare(strict_types=1);

use DI\Container;
use Psr\Container\ContainerInterface;
use App\Helpers\JwtHelper;
use App\Services\AuthService;

// Create container factory
return function (): ContainerInterface {
    $container = new Container();
    
    // Configuration settings
    $container->set('settings', function (): array {
        return [
            'database' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_NAME'] ?? 'crm_system',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                ],
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'],
                'algorithm' => 'HS256',
                'access_expiry' => (int) ($_ENV['JWT_ACCESS_EXPIRY'] ?? 3600),
                'refresh_expiry' => (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800),
            ],
            'redis' => [
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            ],
        ];
    });
    
    // Logger service
    $container->set('logger', require __DIR__ . '/logger.php');
    
    // Database service
    $container->set('database', require __DIR__ . '/database.php');
    
    // JWT Helper service
    $container->set('jwtHelper', function (ContainerInterface $container): JwtHelper {
        $settings = $container->get('settings');
        return new JwtHelper(
            $settings['jwt']['secret'],
            $settings['jwt']['access_expiry'],
            $settings['jwt']['refresh_expiry'],
            $settings['jwt']['algorithm']
        );
    });
    
    // Authentication Service
    $container->set('authService', function (ContainerInterface $container): AuthService {
        return new AuthService(
            $container->get('jwtHelper')
        );
    });
    
    // Business Services - TODO: Implement these services in step 10
    $container->set('userService', function (ContainerInterface $container) {
        // Placeholder for UserService - will be implemented in step 10
        return new \stdClass();
    });
    
    $container->set('companyService', function (ContainerInterface $container) {
        // Placeholder for CompanyService - will be implemented in step 10
        return new \stdClass();
    });
    
    $container->set('contactService', function (ContainerInterface $container) {
        // Placeholder for ContactService - will be implemented in step 10
        return new \stdClass();
    });
    
    $container->set('opportunityService', function (ContainerInterface $container) {
        // Placeholder for OpportunityService - will be implemented in step 10
        return new \stdClass();
    });
    
    $container->set('activityService', function (ContainerInterface $container) {
        // Placeholder for ActivityService - will be implemented in step 10
        return new \stdClass();
    });
    
    return $container;
};
<?php

declare(strict_types=1);

namespace App\Config;

use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\RequestIdMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\RateLimitMiddleware;

return function (ContainerInterface $container): App {
    // Set container for app factory
    AppFactory::setContainer($container);
    
    // Create Slim app
    $app = AppFactory::create();
    
    // Add routing middleware
    $app->addRoutingMiddleware();
    
    // Add custom middlewares (order matters - LIFO execution)
    
    // 1. Error handling (first to catch all errors)
    $app->add(new ErrorHandlerMiddleware(
        $container->get('logger'),
        $_ENV['APP_DEBUG'] === 'true'
    ));
    
    // 2. CORS (early to handle preflight requests)
    $app->add(new CorsMiddleware());
    
    // 3. Request ID (for logging correlation)
    $app->add(new RequestIdMiddleware());
    
    // 4. Rate limiting (before auth to prevent brute force)
    $app->add(new RateLimitMiddleware(
        (int) ($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 100),
        (int) ($_ENV['RATE_LIMIT_WINDOW_SECONDS'] ?? 3600),
        $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        (int) ($_ENV['REDIS_PORT'] ?? 6379)
    ));
    
    // 5. Authentication (after rate limit)
    $app->add(new AuthMiddleware($_ENV['JWT_SECRET']));
    
    // Add Slim's built-in error middleware (last line of defense)
    $errorMiddleware = $app->addErrorMiddleware(
        $_ENV['APP_DEBUG'] === 'true',
        true,
        true
    );
    
    return $app;
};
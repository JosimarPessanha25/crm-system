<?php

declare(strict_types=1);

use DI\Container;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create DI Container
$container = new Container();

// Load services
$container->set('logger', require __DIR__ . '/logger.php');
$container->set('database', require __DIR__ . '/database.php');

// Bootstrap Eloquent ORM
$capsule = $container->get('database');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Create and configure Slim app
$app = (require __DIR__ . '/bootstrap.php')($container);

// Load routes
(require __DIR__ . '/routes.php')($app, $container);

return $app;
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        
        // Get allowed origins from environment
        $allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
        $allowedMethods = $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS,PATCH';
        $allowedHeaders = $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Authorization,Content-Type,X-Requested-With,Accept,Origin,X-Request-Id';
        $maxAge = (int) ($_ENV['CORS_MAX_AGE'] ?? '86400');
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withStatus(204);
        }
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigins)
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', (string) $maxAge)
            ->withHeader('Vary', 'Origin');
    }
}
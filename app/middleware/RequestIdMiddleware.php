<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Ramsey\Uuid\Uuid;

class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Generate or extract request ID
        $requestId = $request->getHeaderLine('X-Request-Id') ?: Uuid::uuid4()->toString();
        
        // Add request ID to request attributes
        $request = $request->withAttribute('request_id', $requestId);
        
        // Set in global scope for logging
        $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;
        
        // Process request
        $response = $handler->handle($request);
        
        // Add request ID to response headers
        return $response->withHeader('X-Request-Id', $requestId);
    }
}
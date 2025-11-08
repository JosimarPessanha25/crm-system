<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;
use App\Models\User;

class AuthMiddleware implements MiddlewareInterface
{
    private string $jwtSecret;
    private array $excludedPaths;

    public function __construct(string $jwtSecret, array $excludedPaths = [])
    {
        $this->jwtSecret = $jwtSecret;
        $this->excludedPaths = array_merge([
            '/auth/login',
            '/auth/register',
            '/auth/refresh',
            '/health',
            '/api/docs'
        ], $excludedPaths);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();
        
        // Skip authentication for excluded paths
        if ($this->isExcludedPath($path)) {
            return $handler->handle($request);
        }

        // Extract JWT token from header
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->createUnauthorizedResponse();
        }

        $token = substr($authHeader, 7);
        
        try {
            // Decode and validate JWT token
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Check token expiration
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return $this->createUnauthorizedResponse('Token expired');
            }

            // Load user from database
            $user = User::find($decoded->user_id ?? null);
            if (!$user || !$user->ativo) {
                return $this->createUnauthorizedResponse('Invalid user');
            }

            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('token_data', $decoded);
            
        } catch (ExpiredException $e) {
            return $this->createUnauthorizedResponse('Token expired');
        } catch (SignatureInvalidException $e) {
            return $this->createUnauthorizedResponse('Invalid token signature');
        } catch (Exception $e) {
            return $this->createUnauthorizedResponse('Invalid token');
        }

        return $handler->handle($request);
    }

    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }
        return false;
    }

    private function createUnauthorizedResponse(string $message = 'Unauthorized'): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
            'timestamp' => date('c')
        ]));
        
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
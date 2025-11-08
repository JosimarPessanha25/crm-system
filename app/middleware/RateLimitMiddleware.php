<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $redisHost;
    private int $redisPort;
    private ?\Redis $redis = null;

    public function __construct(
        int $maxRequests = 100,
        int $windowSeconds = 3600,
        string $redisHost = '127.0.0.1',
        int $redisPort = 6379
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Get client identifier (IP + User ID if authenticated)
        $clientId = $this->getClientIdentifier($request);
        
        // Check rate limit
        if ($this->isRateLimited($clientId)) {
            return $this->createRateLimitResponse();
        }

        // Process request
        $response = $handler->handle($request);
        
        // Add rate limit headers to response
        return $this->addRateLimitHeaders($response, $clientId);
    }

    private function getClientIdentifier(Request $request): string
    {
        $ip = $this->getClientIp($request);
        $user = $request->getAttribute('user');
        
        if ($user && isset($user->id)) {
            return "user:{$user->id}";
        }
        
        return "ip:{$ip}";
    }

    private function getClientIp(Request $request): string
    {
        // Check for IP behind proxy
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }
        
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function isRateLimited(string $clientId): bool
    {
        if (!$this->connectRedis()) {
            // If Redis is not available, allow request (fail open)
            return false;
        }

        $key = "rate_limit:{$clientId}";
        $current = $this->redis->get($key);
        
        if ($current === false) {
            // First request in window
            $this->redis->setex($key, $this->windowSeconds, 1);
            return false;
        }
        
        $current = (int) $current;
        if ($current >= $this->maxRequests) {
            return true;
        }
        
        // Increment counter
        $this->redis->incr($key);
        return false;
    }

    private function getRemainingRequests(string $clientId): int
    {
        if (!$this->connectRedis()) {
            return $this->maxRequests;
        }

        $key = "rate_limit:{$clientId}";
        $current = $this->redis->get($key);
        
        if ($current === false) {
            return $this->maxRequests;
        }
        
        return max(0, $this->maxRequests - (int) $current);
    }

    private function getWindowReset(string $clientId): int
    {
        if (!$this->connectRedis()) {
            return time() + $this->windowSeconds;
        }

        $key = "rate_limit:{$clientId}";
        $ttl = $this->redis->ttl($key);
        
        if ($ttl === -1 || $ttl === -2) {
            return time() + $this->windowSeconds;
        }
        
        return time() + $ttl;
    }

    private function connectRedis(): bool
    {
        if ($this->redis !== null) {
            return true;
        }

        try {
            $this->redis = new \Redis();
            $result = $this->redis->connect($this->redisHost, $this->redisPort, 2.0);
            
            if (!$result) {
                return false;
            }

            // Test connection
            $this->redis->ping();
            return true;
            
        } catch (\Exception $e) {
            $this->redis = null;
            return false;
        }
    }

    private function createRateLimitResponse(): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Rate limit exceeded',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $this->windowSeconds,
            'timestamp' => date('c')
        ]));
        
        return $response
            ->withStatus(429)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $this->windowSeconds);
    }

    private function addRateLimitHeaders(Response $response, string $clientId): Response
    {
        $remaining = $this->getRemainingRequests($clientId);
        $reset = $this->getWindowReset($clientId);
        
        return $response
            ->withHeader('X-Rate-Limit-Limit', (string) $this->maxRequests)
            ->withHeader('X-Rate-Limit-Remaining', (string) $remaining)
            ->withHeader('X-Rate-Limit-Reset', (string) $reset)
            ->withHeader('X-Rate-Limit-Window', (string) $this->windowSeconds);
    }
}
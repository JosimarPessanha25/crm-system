<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Exception;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private bool $displayErrorDetails;

    public function __construct(LoggerInterface $logger, bool $displayErrorDetails = false)
    {
        $this->logger = $logger;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($request, $exception);
        }
    }

    private function handleException(Request $request, Throwable $exception): Response
    {
        $requestId = $request->getAttribute('request_id', 'unknown');
        
        // Log the exception
        $this->logger->error('Unhandled exception', [
            'request_id' => $requestId,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'user_agent' => $request->getHeaderLine('User-Agent'),
        ]);

        // Determine status code
        $statusCode = $this->getStatusCode($exception);
        
        // Prepare error response
        $errorData = [
            'success' => false,
            'message' => $this->getErrorMessage($exception),
            'error_code' => $this->getErrorCode($exception),
            'request_id' => $requestId,
            'timestamp' => date('c')
        ];

        // Add debug info in development
        if ($this->displayErrorDetails) {
            $errorData['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        // Create response
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Request-Id', $requestId);
    }

    private function getStatusCode(Throwable $exception): int
    {
        // Handle specific exception types
        switch (true) {
            case method_exists($exception, 'getStatusCode'):
                return $exception->getStatusCode();
            case $exception instanceof \InvalidArgumentException:
                return 400;
            case $exception instanceof \UnauthorizedHttpException:
                return 401;
            case $exception instanceof \ForbiddenHttpException:
                return 403;
            case $exception instanceof \NotFoundHttpException:
                return 404;
            case $exception instanceof \MethodNotAllowedHttpException:
                return 405;
            case $exception instanceof \ConflictHttpException:
                return 409;
            case $exception instanceof \UnprocessableEntityHttpException:
                return 422;
            case $exception instanceof \TooManyRequestsHttpException:
                return 429;
            default:
                return 500;
        }
    }

    private function getErrorMessage(Throwable $exception): string
    {
        // Return generic message for production, detailed for development
        if (!$this->displayErrorDetails) {
            switch ($this->getStatusCode($exception)) {
                case 400:
                    return 'Bad Request';
                case 401:
                    return 'Unauthorized';
                case 403:
                    return 'Forbidden';
                case 404:
                    return 'Not Found';
                case 405:
                    return 'Method Not Allowed';
                case 409:
                    return 'Conflict';
                case 422:
                    return 'Unprocessable Entity';
                case 429:
                    return 'Too Many Requests';
                default:
                    return 'Internal Server Error';
            }
        }
        
        return $exception->getMessage();
    }

    private function getErrorCode(Throwable $exception): string
    {
        $className = (new \ReflectionClass($exception))->getShortName();
        return strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Exception;
use InvalidArgumentException;

class AuthController
{
    private AuthService $authService;
    private LoggerInterface $logger;

    public function __construct(AuthService $authService, LoggerInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    /**
     * User login endpoint
     * POST /auth/login
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $rememberMe = $data['remember_me'] ?? false;

            // Log login attempt
            $this->logger->info('Login attempt', [
                'email' => $email,
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);

            $result = $this->authService->login($email, $password);

            // Log successful login
            $this->logger->info('Login successful', [
                'user_id' => $result['user']['id'],
                'email' => $email,
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            $this->logger->warning('Login failed', [
                'email' => $email ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp($request)
            ]);

            return $this->errorResponse($response, $e->getMessage(), 'AUTHENTICATION_FAILED', $e->getCode() ?: 401);
        }
    }

    /**
     * User registration endpoint
     * POST /auth/register
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            // Log registration attempt
            $this->logger->info('Registration attempt', [
                'email' => $data['email'] ?? 'unknown',
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);

            $result = $this->authService->register($data);

            // Log successful registration
            $this->logger->info('Registration successful', [
                'user_id' => $result['user']['id'],
                'email' => $result['user']['email'],
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 201);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            $this->logger->error('Registration failed', [
                'error' => $e->getMessage(),
                'data' => $data ?? [],
                'ip' => $this->getClientIp($request)
            ]);

            return $this->errorResponse($response, $e->getMessage(), 'REGISTRATION_FAILED', $e->getCode() ?: 500);
        }
    }

    /**
     * Refresh token endpoint
     * POST /auth/refresh
     */
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $refreshToken = $data['refresh_token'] ?? '';
            
            if (empty($refreshToken)) {
                throw new InvalidArgumentException('Refresh token is required');
            }

            $result = $this->authService->refreshToken($refreshToken);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            $this->logger->warning('Token refresh failed', [
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp($request)
            ]);

            return $this->errorResponse($response, $e->getMessage(), 'TOKEN_REFRESH_FAILED', 401);
        }
    }

    /**
     * User logout endpoint
     * POST /auth/logout
     */
    public function logout(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                throw new Exception('User not authenticated', 401);
            }

            $result = $this->authService->logout($user->id);

            // Log logout
            $this->logger->info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 'LOGOUT_FAILED', $e->getCode() ?: 500);
        }
    }

    /**
     * Get user profile
     * GET /auth/profile
     */
    public function getProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                throw new Exception('User not authenticated', 401);
            }

            $result = $this->authService->getProfile($user->id);

            return $this->jsonResponse($response, $result, 200);

        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 'PROFILE_FETCH_FAILED', $e->getCode() ?: 500);
        }
    }

    /**
     * Update user profile
     * PUT /auth/profile
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                throw new Exception('User not authenticated', 401);
            }

            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $result = $this->authService->updateProfile($user->id, $data);

            // Log profile update
            $this->logger->info('Profile updated', [
                'user_id' => $user->id,
                'fields' => array_keys($data),
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 'PROFILE_UPDATE_FAILED', $e->getCode() ?: 500);
        }
    }

    /**
     * Change password
     * POST /auth/change-password
     */
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                throw new Exception('User not authenticated', 401);
            }

            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $currentPassword = $data['current_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                throw new InvalidArgumentException('Current password and new password are required');
            }

            $result = $this->authService->changePassword($user->id, $currentPassword, $newPassword);

            // Log password change
            $this->logger->info('Password changed', [
                'user_id' => $user->id,
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            $this->logger->warning('Password change failed', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp($request)
            ]);

            return $this->errorResponse($response, $e->getMessage(), 'PASSWORD_CHANGE_FAILED', $e->getCode() ?: 400);
        }
    }

    /**
     * Request password reset
     * POST /auth/forgot-password
     */
    public function forgotPassword(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $email = $data['email'] ?? '';
            
            if (empty($email)) {
                throw new InvalidArgumentException('Email is required');
            }

            $result = $this->authService->requestPasswordReset($email);

            // Log password reset request
            $this->logger->info('Password reset requested', [
                'email' => $email,
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 'PASSWORD_RESET_REQUEST_FAILED', 500);
        }
    }

    /**
     * Reset password with token
     * POST /auth/reset-password
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON format');
            }

            $token = $data['token'] ?? '';
            $newPassword = $data['new_password'] ?? '';
            
            if (empty($token) || empty($newPassword)) {
                throw new InvalidArgumentException('Token and new password are required');
            }

            $result = $this->authService->resetPassword($token, $newPassword);

            // Log password reset
            $this->logger->info('Password reset completed', [
                'ip' => $this->getClientIp($request)
            ]);

            return $this->jsonResponse($response, $result, 200);

        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'VALIDATION_ERROR', 400);
        } catch (Exception $e) {
            $this->logger->warning('Password reset failed', [
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp($request)
            ]);

            return $this->errorResponse($response, $e->getMessage(), 'PASSWORD_RESET_FAILED', 400);
        }
    }

    /**
     * Create standardized JSON response
     */
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $data['timestamp'] = date('c');
        $data['request_id'] = $response->getHeaderLine('X-Request-Id') ?: 'unknown';

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create standardized error response
     */
    private function errorResponse(Response $response, string $message, string $errorCode, int $statusCode): Response
    {
        $errorData = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'timestamp' => date('c'),
            'request_id' => $response->getHeaderLine('X-Request-Id') ?: 'unknown'
        ];

        $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Check for IP behind proxy
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }
        
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * UserController - CRUD operations for users
 * 
 * Handles all user management operations following thin controller pattern.
 * Business logic is delegated to UserService.
 */
class UserController
{
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(UserService $userService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * List all users with pagination and filtering
     * GET /api/users
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            // Extract pagination and filter parameters
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);
            $search = $params['search'] ?? null;
            $status = $params['status'] ?? null;
            $role = $params['role'] ?? null;
            $sortBy = $params['sort_by'] ?? 'created_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            $this->logger->info('User list requested', [
                'user_id' => $request->getAttribute('user_id'),
                'page' => $page,
                'limit' => $limit,
                'search' => $search
            ]);

            $result = $this->userService->list($page, $limit, [
                'search' => $search,
                'status' => $status,
                'role' => $role,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]);

            $responseData = [
                'success' => true,
                'data' => $result['data'],
                'meta' => [
                    'current_page' => $result['current_page'],
                    'total_pages' => $result['total_pages'],
                    'total_items' => $result['total'],
                    'per_page' => $result['per_page']
                ]
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error listing users', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get a specific user by ID
     * GET /api/users/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];
            
            $this->logger->info('User detail requested', [
                'requested_user_id' => $userId,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $user = $this->userService->findById($userId);

            if (!$user) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $user
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving user', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Create a new user
     * POST /api/users
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('User creation requested', [
                'email' => $data['email'] ?? null,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $user = $this->userService->create($data);

            $responseData = [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('User creation validation failed', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [$e->getMessage()]
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);

        } catch (\Exception $e) {
            $this->logger->error('Error creating user', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update an existing user
     * PUT /api/users/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('User update requested', [
                'user_id' => $userId,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $user = $this->userService->update($userId, $data);

            if (!$user) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('User update validation failed', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [$e->getMessage()]
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);

        } catch (\Exception $e) {
            $this->logger->error('Error updating user', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Delete a user (soft delete)
     * DELETE /api/users/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $this->logger->info('User deletion requested', [
                'user_id' => $userId,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->userService->delete($userId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not found or already deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'User deleted successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting user', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Restore a soft-deleted user
     * POST /api/users/{id}/restore
     */
    public function restore(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $this->logger->info('User restoration requested', [
                'user_id' => $userId,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->userService->restore($userId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not found or not deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'User restored successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error restoring user', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to restore user',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get user statistics
     * GET /api/users/{id}/stats
     */
    public function stats(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $this->logger->info('User stats requested', [
                'user_id' => $userId,
                'requester_id' => $request->getAttribute('user_id')
            ]);

            $stats = $this->userService->getStats($userId);

            if (!$stats) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $stats
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving user stats', [
                'error' => $e->getMessage(),
                'user_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve user statistics',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
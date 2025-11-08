<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CompanyService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * CompanyController - CRUD operations for companies
 * 
 * Handles all company management operations following thin controller pattern.
 * Business logic is delegated to CompanyService.
 */
class CompanyController
{
    private CompanyService $companyService;
    private LoggerInterface $logger;

    public function __construct(CompanyService $companyService, LoggerInterface $logger)
    {
        $this->companyService = $companyService;
        $this->logger = $logger;
    }

    /**
     * List all companies with pagination and filtering
     * GET /api/companies
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
            $sector = $params['sector'] ?? null;
            $size = $params['size'] ?? null;
            $sortBy = $params['sort_by'] ?? 'created_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            $this->logger->info('Company list requested', [
                'user_id' => $request->getAttribute('user_id'),
                'page' => $page,
                'limit' => $limit,
                'search' => $search
            ]);

            $result = $this->companyService->list($page, $limit, [
                'search' => $search,
                'status' => $status,
                'sector' => $sector,
                'size' => $size,
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
            $this->logger->error('Error listing companies', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve companies',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get a specific company by ID
     * GET /api/companies/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];
            
            $this->logger->info('Company detail requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $company = $this->companyService->findById($companyId);

            if (!$company) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $company
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving company', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve company',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Create a new company
     * POST /api/companies
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Company creation requested', [
                'name' => $data['nome'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $company = $this->companyService->create($data);

            $responseData = [
                'success' => true,
                'message' => 'Company created successfully',
                'data' => $company
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Company creation validation failed', [
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
            $this->logger->error('Error creating company', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to create company',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update an existing company
     * PUT /api/companies/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Company update requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $company = $this->companyService->update($companyId, $data);

            if (!$company) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Company update validation failed', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
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
            $this->logger->error('Error updating company', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update company',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Delete a company (soft delete)
     * DELETE /api/companies/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];

            $this->logger->info('Company deletion requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->companyService->delete($companyId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found or already deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Company deleted successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting company', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to delete company',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get company contacts
     * GET /api/companies/{id}/contacts
     */
    public function contacts(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);

            $this->logger->info('Company contacts requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $result = $this->companyService->getContacts($companyId, $page, $limit);

            if ($result === null) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

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
            $this->logger->error('Error retrieving company contacts', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve company contacts',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get company opportunities
     * GET /api/companies/{id}/opportunities
     */
    public function opportunities(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);

            $this->logger->info('Company opportunities requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $result = $this->companyService->getOpportunities($companyId, $page, $limit);

            if ($result === null) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

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
            $this->logger->error('Error retrieving company opportunities', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve company opportunities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get company statistics
     * GET /api/companies/{id}/stats
     */
    public function stats(Request $request, Response $response, array $args): Response
    {
        try {
            $companyId = (int)$args['id'];

            $this->logger->info('Company stats requested', [
                'company_id' => $companyId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $stats = $this->companyService->getStats($companyId);

            if (!$stats) {
                $errorData = [
                    'success' => false,
                    'message' => 'Company not found'
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
            $this->logger->error('Error retrieving company stats', [
                'error' => $e->getMessage(),
                'company_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve company statistics',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
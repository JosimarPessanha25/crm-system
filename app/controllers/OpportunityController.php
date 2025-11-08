<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OpportunityService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * OpportunityController - CRUD operations for opportunities
 * 
 * Handles all opportunity management operations following thin controller pattern.
 * Business logic is delegated to OpportunityService.
 */
class OpportunityController
{
    private OpportunityService $opportunityService;
    private LoggerInterface $logger;

    public function __construct(OpportunityService $opportunityService, LoggerInterface $logger)
    {
        $this->opportunityService = $opportunityService;
        $this->logger = $logger;
    }

    /**
     * List all opportunities with pagination and filtering
     * GET /api/opportunities
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            // Extract pagination and filter parameters
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);
            $search = $params['search'] ?? null;
            $stage = $params['stage'] ?? null;
            $company = $params['company'] ?? null;
            $contact = $params['contact'] ?? null;
            $responsible = $params['responsible'] ?? null;
            $valueMin = $params['value_min'] ?? null;
            $valueMax = $params['value_max'] ?? null;
            $sortBy = $params['sort_by'] ?? 'created_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            $this->logger->info('Opportunity list requested', [
                'user_id' => $request->getAttribute('user_id'),
                'page' => $page,
                'limit' => $limit,
                'search' => $search
            ]);

            $result = $this->opportunityService->list($page, $limit, [
                'search' => $search,
                'stage' => $stage,
                'company' => $company,
                'contact' => $contact,
                'responsible' => $responsible,
                'value_min' => $valueMin ? (float)$valueMin : null,
                'value_max' => $valueMax ? (float)$valueMax : null,
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
                    'per_page' => $result['per_page'],
                    'summary' => $result['summary'] ?? null
                ]
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error listing opportunities', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve opportunities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get pipeline view (opportunities grouped by stage)
     * GET /api/opportunities/pipeline
     */
    public function pipeline(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $responsible = $params['responsible'] ?? null;
            $company = $params['company'] ?? null;

            $this->logger->info('Pipeline view requested', [
                'user_id' => $request->getAttribute('user_id'),
                'responsible' => $responsible
            ]);

            $pipeline = $this->opportunityService->getPipeline([
                'responsible' => $responsible,
                'company' => $company
            ]);

            $responseData = [
                'success' => true,
                'data' => $pipeline
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving pipeline', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve pipeline',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get a specific opportunity by ID
     * GET /api/opportunities/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];
            
            $this->logger->info('Opportunity detail requested', [
                'opportunity_id' => $opportunityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $opportunity = $this->opportunityService->findById($opportunityId);

            if (!$opportunity) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $opportunity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving opportunity', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve opportunity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Create a new opportunity
     * POST /api/opportunities
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Opportunity creation requested', [
                'title' => $data['titulo'] ?? null,
                'value' => $data['valor'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $opportunity = $this->opportunityService->create($data);

            $responseData = [
                'success' => true,
                'message' => 'Opportunity created successfully',
                'data' => $opportunity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Opportunity creation validation failed', [
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
            $this->logger->error('Error creating opportunity', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to create opportunity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update an existing opportunity
     * PUT /api/opportunities/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Opportunity update requested', [
                'opportunity_id' => $opportunityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $opportunity = $this->opportunityService->update($opportunityId, $data);

            if (!$opportunity) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Opportunity updated successfully',
                'data' => $opportunity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Opportunity update validation failed', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
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
            $this->logger->error('Error updating opportunity', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update opportunity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Move opportunity to different stage
     * PUT /api/opportunities/{id}/stage
     */
    public function moveStage(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Opportunity stage move requested', [
                'opportunity_id' => $opportunityId,
                'new_stage' => $data['stage'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->opportunityService->moveStage(
                $opportunityId,
                $data['stage'] ?? '',
                $data['probability'] ?? null,
                $data['reason'] ?? null,
                $data['next_steps'] ?? null
            );

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found or invalid stage'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Opportunity stage updated successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Opportunity stage move validation failed', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
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
            $this->logger->error('Error moving opportunity stage', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to move opportunity stage',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Delete an opportunity (soft delete)
     * DELETE /api/opportunities/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];

            $this->logger->info('Opportunity deletion requested', [
                'opportunity_id' => $opportunityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->opportunityService->delete($opportunityId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found or already deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Opportunity deleted successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting opportunity', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to delete opportunity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get opportunity activities
     * GET /api/opportunities/{id}/activities
     */
    public function activities(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);

            $this->logger->info('Opportunity activities requested', [
                'opportunity_id' => $opportunityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $result = $this->opportunityService->getActivities($opportunityId, $page, $limit);

            if ($result === null) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found'
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
            $this->logger->error('Error retrieving opportunity activities', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve opportunity activities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Close/Win opportunity
     * POST /api/opportunities/{id}/close
     */
    public function close(Request $request, Response $response, array $args): Response
    {
        try {
            $opportunityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Opportunity close requested', [
                'opportunity_id' => $opportunityId,
                'won' => $data['won'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->opportunityService->close(
                $opportunityId,
                $data['won'] ?? true,
                $data['close_reason'] ?? null,
                $data['final_value'] ?? null
            );

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Opportunity not found or already closed'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Opportunity closed successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Opportunity close validation failed', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
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
            $this->logger->error('Error closing opportunity', [
                'error' => $e->getMessage(),
                'opportunity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to close opportunity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get opportunity statistics
     * GET /api/opportunities/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $period = $params['period'] ?? 'month';
            $responsible = $params['responsible'] ?? null;

            $this->logger->info('Opportunity stats requested', [
                'period' => $period,
                'responsible' => $responsible,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $stats = $this->opportunityService->getStats($period, $responsible);

            $responseData = [
                'success' => true,
                'data' => $stats
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving opportunity stats', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve opportunity statistics',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ActivityService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * ActivityController - CRUD operations for activities
 * 
 * Handles all activity management operations following thin controller pattern.
 * Business logic is delegated to ActivityService.
 */
class ActivityController
{
    private ActivityService $activityService;
    private LoggerInterface $logger;

    public function __construct(ActivityService $activityService, LoggerInterface $logger)
    {
        $this->activityService = $activityService;
        $this->logger = $logger;
    }

    /**
     * List all activities with pagination and filtering
     * GET /api/activities
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            // Extract pagination and filter parameters
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);
            $search = $params['search'] ?? null;
            $type = $params['type'] ?? null;
            $status = $params['status'] ?? null;
            $responsible = $params['responsible'] ?? null;
            $contact = $params['contact'] ?? null;
            $opportunity = $params['opportunity'] ?? null;
            $dateFrom = $params['date_from'] ?? null;
            $dateTo = $params['date_to'] ?? null;
            $overdue = $params['overdue'] ?? null;
            $sortBy = $params['sort_by'] ?? 'data_vencimento';
            $sortOrder = $params['sort_order'] ?? 'asc';

            $this->logger->info('Activity list requested', [
                'user_id' => $request->getAttribute('user_id'),
                'page' => $page,
                'limit' => $limit,
                'search' => $search
            ]);

            $result = $this->activityService->list($page, $limit, [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'responsible' => $responsible,
                'contact' => $contact,
                'opportunity' => $opportunity,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'overdue' => $overdue === 'true',
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
                    'overdue_count' => $result['overdue_count'] ?? 0,
                    'today_count' => $result['today_count'] ?? 0
                ]
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error listing activities', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve activities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get calendar view of activities
     * GET /api/activities/calendar
     */
    public function calendar(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $start = $params['start'] ?? null; // ISO date format
            $end = $params['end'] ?? null;     // ISO date format
            $responsible = $params['responsible'] ?? null;

            $this->logger->info('Activity calendar requested', [
                'user_id' => $request->getAttribute('user_id'),
                'start' => $start,
                'end' => $end
            ]);

            $activities = $this->activityService->getCalendar($start, $end, $responsible);

            $responseData = [
                'success' => true,
                'data' => $activities
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving activity calendar', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve calendar',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get upcoming activities for dashboard
     * GET /api/activities/upcoming
     */
    public function upcoming(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = (int)($params['limit'] ?? 10);
            $responsible = $params['responsible'] ?? null;

            $this->logger->info('Upcoming activities requested', [
                'user_id' => $request->getAttribute('user_id'),
                'limit' => $limit
            ]);

            $activities = $this->activityService->getUpcoming($limit, $responsible);

            $responseData = [
                'success' => true,
                'data' => $activities
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving upcoming activities', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve upcoming activities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get a specific activity by ID
     * GET /api/activities/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $activityId = (int)$args['id'];
            
            $this->logger->info('Activity detail requested', [
                'activity_id' => $activityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $activity = $this->activityService->findById($activityId);

            if (!$activity) {
                $errorData = [
                    'success' => false,
                    'message' => 'Activity not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $activity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving activity', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Create a new activity
     * POST /api/activities
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Activity creation requested', [
                'title' => $data['titulo'] ?? null,
                'type' => $data['tipo'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $activity = $this->activityService->create($data);

            $responseData = [
                'success' => true,
                'message' => 'Activity created successfully',
                'data' => $activity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Activity creation validation failed', [
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
            $this->logger->error('Error creating activity', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to create activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update an existing activity
     * PUT /api/activities/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $activityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Activity update requested', [
                'activity_id' => $activityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $activity = $this->activityService->update($activityId, $data);

            if (!$activity) {
                $errorData = [
                    'success' => false,
                    'message' => 'Activity not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Activity updated successfully',
                'data' => $activity
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Activity update validation failed', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
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
            $this->logger->error('Error updating activity', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Complete an activity
     * POST /api/activities/{id}/complete
     */
    public function complete(Request $request, Response $response, array $args): Response
    {
        try {
            $activityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Activity completion requested', [
                'activity_id' => $activityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->activityService->complete(
                $activityId,
                $data['notes'] ?? null,
                $data['outcome'] ?? null,
                $data['next_activity'] ?? null
            );

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Activity not found or already completed'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Activity completed successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error completing activity', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to complete activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Reschedule an activity
     * PUT /api/activities/{id}/reschedule
     */
    public function reschedule(Request $request, Response $response, array $args): Response
    {
        try {
            $activityId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Activity reschedule requested', [
                'activity_id' => $activityId,
                'new_date' => $data['data_vencimento'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->activityService->reschedule(
                $activityId,
                $data['data_vencimento'] ?? '',
                $data['hora_vencimento'] ?? null,
                $data['reason'] ?? null
            );

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Activity not found or cannot be rescheduled'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Activity rescheduled successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Activity reschedule validation failed', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
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
            $this->logger->error('Error rescheduling activity', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to reschedule activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Delete an activity (soft delete)
     * DELETE /api/activities/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $activityId = (int)$args['id'];

            $this->logger->info('Activity deletion requested', [
                'activity_id' => $activityId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->activityService->delete($activityId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Activity not found or already deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Activity deleted successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting activity', [
                'error' => $e->getMessage(),
                'activity_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to delete activity',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get activity statistics
     * GET /api/activities/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $period = $params['period'] ?? 'week';
            $responsible = $params['responsible'] ?? null;

            $this->logger->info('Activity stats requested', [
                'period' => $period,
                'responsible' => $responsible,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $stats = $this->activityService->getStats($period, $responsible);

            $responseData = [
                'success' => true,
                'data' => $stats
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving activity stats', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve activity statistics',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
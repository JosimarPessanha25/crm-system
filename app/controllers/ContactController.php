<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ContactService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * ContactController - CRUD operations for contacts
 * 
 * Handles all contact management operations following thin controller pattern.
 * Business logic is delegated to ContactService.
 */
class ContactController
{
    private ContactService $contactService;
    private LoggerInterface $logger;

    public function __construct(ContactService $contactService, LoggerInterface $logger)
    {
        $this->contactService = $contactService;
        $this->logger = $logger;
    }

    /**
     * List all contacts with pagination and filtering
     * GET /api/contacts
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            // Extract pagination and filter parameters
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);
            $search = $params['search'] ?? null;
            $company = $params['company'] ?? null;
            $status = $params['status'] ?? null;
            $tags = $params['tags'] ?? null;
            $sortBy = $params['sort_by'] ?? 'created_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            $this->logger->info('Contact list requested', [
                'user_id' => $request->getAttribute('user_id'),
                'page' => $page,
                'limit' => $limit,
                'search' => $search
            ]);

            $result = $this->contactService->list($page, $limit, [
                'search' => $search,
                'company' => $company,
                'status' => $status,
                'tags' => $tags,
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
            $this->logger->error('Error listing contacts', [
                'error' => $e->getMessage(),
                'user_id' => $request->getAttribute('user_id')
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve contacts',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get a specific contact by ID
     * GET /api/contacts/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            
            $this->logger->info('Contact detail requested', [
                'contact_id' => $contactId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $contact = $this->contactService->findById($contactId);

            if (!$contact) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'data' => $contact
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving contact', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve contact',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Create a new contact
     * POST /api/contacts
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Contact creation requested', [
                'name' => $data['nome'] ?? null,
                'email' => $data['email'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $contact = $this->contactService->create($data);

            $responseData = [
                'success' => true,
                'message' => 'Contact created successfully',
                'data' => $contact
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Contact creation validation failed', [
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
            $this->logger->error('Error creating contact', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to create contact',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update an existing contact
     * PUT /api/contacts/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Contact update requested', [
                'contact_id' => $contactId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $contact = $this->contactService->update($contactId, $data);

            if (!$contact) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Contact updated successfully',
                'data' => $contact
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Contact update validation failed', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
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
            $this->logger->error('Error updating contact', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update contact',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Delete a contact (soft delete)
     * DELETE /api/contacts/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];

            $this->logger->info('Contact deletion requested', [
                'contact_id' => $contactId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->contactService->delete($contactId);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found or already deleted'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Contact deleted successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error deleting contact', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to delete contact',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get contact timeline/activity feed
     * GET /api/contacts/{id}/timeline
     */
    public function timeline(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);

            $this->logger->info('Contact timeline requested', [
                'contact_id' => $contactId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $result = $this->contactService->getTimeline($contactId, $page, $limit);

            if ($result === null) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
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
            $this->logger->error('Error retrieving contact timeline', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve contact timeline',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get contact opportunities
     * GET /api/contacts/{id}/opportunities
     */
    public function opportunities(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 20);

            $this->logger->info('Contact opportunities requested', [
                'contact_id' => $contactId,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $result = $this->contactService->getOpportunities($contactId, $page, $limit);

            if ($result === null) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
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
            $this->logger->error('Error retrieving contact opportunities', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve contact opportunities',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Update contact score/lead score
     * PUT /api/contacts/{id}/score
     */
    public function updateScore(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Contact score update requested', [
                'contact_id' => $contactId,
                'score' => $data['score'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->contactService->updateScore($contactId, $data['score'] ?? 0, $data['reason'] ?? null);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Contact score updated successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Contact score update validation failed', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
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
            $this->logger->error('Error updating contact score', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to update contact score',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Add tags to contact
     * POST /api/contacts/{id}/tags
     */
    public function addTags(Request $request, Response $response, array $args): Response
    {
        try {
            $contactId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Contact tags addition requested', [
                'contact_id' => $contactId,
                'tags' => $data['tags'] ?? null,
                'user_id' => $request->getAttribute('user_id')
            ]);

            $success = $this->contactService->addTags($contactId, $data['tags'] ?? []);

            if (!$success) {
                $errorData = [
                    'success' => false,
                    'message' => 'Contact not found'
                ];

                $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $responseData = [
                'success' => true,
                'message' => 'Tags added successfully'
            ];

            $response->getBody()->write(json_encode($responseData, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Error adding contact tags', [
                'error' => $e->getMessage(),
                'contact_id' => $args['id'] ?? null
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to add tags',
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
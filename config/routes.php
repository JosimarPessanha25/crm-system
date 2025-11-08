<?php

declare(strict_types=1);

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app, ContainerInterface $container): void {
    
    // Health check endpoint
    $app->get('/health', function (Request $request, Response $response): Response {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ];
        
        $response->getBody()->write(json_encode($health));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // API Documentation endpoint
    $app->get('/api/docs', function (Request $request, Response $response): Response {
        $docs = [
            'name' => 'CRM API',
            'version' => '1.0.0',
            'description' => 'Complete CRM system API',
            'endpoints' => [
                'auth' => [
                    'POST /auth/login' => 'User authentication',
                    'POST /auth/register' => 'User registration',
                    'POST /auth/refresh' => 'Token refresh',
                    'POST /auth/logout' => 'User logout'
                ],
                'users' => [
                    'GET /api/users' => 'List users',
                    'POST /api/users' => 'Create user',
                    'GET /api/users/{id}' => 'Get user details',
                    'PUT /api/users/{id}' => 'Update user',
                    'DELETE /api/users/{id}' => 'Delete user'
                ],
                'companies' => [
                    'GET /api/companies' => 'List companies',
                    'POST /api/companies' => 'Create company',
                    'GET /api/companies/{id}' => 'Get company details',
                    'PUT /api/companies/{id}' => 'Update company',
                    'DELETE /api/companies/{id}' => 'Delete company'
                ],
                'contacts' => [
                    'GET /api/contacts' => 'List contacts',
                    'POST /api/contacts' => 'Create contact',
                    'GET /api/contacts/{id}' => 'Get contact details',
                    'PUT /api/contacts/{id}' => 'Update contact',
                    'DELETE /api/contacts/{id}' => 'Delete contact'
                ],
                'opportunities' => [
                    'GET /api/opportunities' => 'List opportunities',
                    'POST /api/opportunities' => 'Create opportunity',
                    'GET /api/opportunities/{id}' => 'Get opportunity details',
                    'PUT /api/opportunities/{id}' => 'Update opportunity',
                    'DELETE /api/opportunities/{id}' => 'Delete opportunity'
                ],
                'activities' => [
                    'GET /api/activities' => 'List activities',
                    'POST /api/activities' => 'Create activity',
                    'GET /api/activities/{id}' => 'Get activity details',
                    'PUT /api/activities/{id}' => 'Update activity',
                    'DELETE /api/activities/{id}' => 'Delete activity'
                ]
            ]
        ];
        
        $response->getBody()->write(json_encode($docs, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // Authentication routes
    $app->group('/auth', function () use ($app, $container) {
        
        // Create AuthController instance
        $authController = function() use ($container) {
            return new \App\Controllers\AuthController(
                $container->get('authService'),
                $container->get('logger')
            );
        };
        
        // Public authentication endpoints
        $app->post('/login', [$authController(), 'login']);
        $app->post('/register', [$authController(), 'register']);
        $app->post('/refresh', [$authController(), 'refresh']);
        $app->post('/forgot-password', [$authController(), 'forgotPassword']);
        $app->post('/reset-password', [$authController(), 'resetPassword']);
        
        // Protected endpoints (require authentication)
        $app->post('/logout', [$authController(), 'logout']);
        $app->get('/profile', [$authController(), 'getProfile']);
        $app->put('/profile', [$authController(), 'updateProfile']);
        $app->post('/change-password', [$authController(), 'changePassword']);
        
    });
    
    // API routes (protected by AuthMiddleware)
    $app->group('/api', function () use ($app, $container) {
        
        // Users CRUD
        $app->group('/users', function () use ($app, $container) {
            $userController = function() use ($container) {
                return new \App\Controllers\UserController(
                    $container->get('userService'),
                    $container->get('logger')
                );
            };
            
            $app->get('', [$userController(), 'index']);
            $app->post('', [$userController(), 'create']);
            $app->get('/{id}', [$userController(), 'show']);
            $app->put('/{id}', [$userController(), 'update']);
            $app->delete('/{id}', [$userController(), 'delete']);
            $app->post('/{id}/restore', [$userController(), 'restore']);
            $app->get('/{id}/stats', [$userController(), 'stats']);
        });
        
        // Companies CRUD
        $app->group('/companies', function () use ($app, $container) {
            $companyController = function() use ($container) {
                return new \App\Controllers\CompanyController(
                    $container->get('companyService'),
                    $container->get('logger')
                );
            };
            
            $app->get('', [$companyController(), 'index']);
            $app->post('', [$companyController(), 'create']);
            $app->get('/{id}', [$companyController(), 'show']);
            $app->put('/{id}', [$companyController(), 'update']);
            $app->delete('/{id}', [$companyController(), 'delete']);
            $app->get('/{id}/contacts', [$companyController(), 'contacts']);
            $app->get('/{id}/opportunities', [$companyController(), 'opportunities']);
            $app->get('/{id}/stats', [$companyController(), 'stats']);
        });
        
        // Contacts CRUD
        $app->group('/contacts', function () use ($app, $container) {
            $contactController = function() use ($container) {
                return new \App\Controllers\ContactController(
                    $container->get('contactService'),
                    $container->get('logger')
                );
            };
            
            $app->get('', [$contactController(), 'index']);
            $app->post('', [$contactController(), 'create']);
            $app->get('/{id}', [$contactController(), 'show']);
            $app->put('/{id}', [$contactController(), 'update']);
            $app->delete('/{id}', [$contactController(), 'delete']);
            $app->get('/{id}/timeline', [$contactController(), 'timeline']);
            $app->get('/{id}/opportunities', [$contactController(), 'opportunities']);
            $app->put('/{id}/score', [$contactController(), 'updateScore']);
            $app->post('/{id}/tags', [$contactController(), 'addTags']);
        });
        
        // Opportunities CRUD
        $app->group('/opportunities', function () use ($app, $container) {
            $opportunityController = function() use ($container) {
                return new \App\Controllers\OpportunityController(
                    $container->get('opportunityService'),
                    $container->get('logger')
                );
            };
            
            $app->get('', [$opportunityController(), 'index']);
            $app->get('/pipeline', [$opportunityController(), 'pipeline']);
            $app->get('/stats', [$opportunityController(), 'stats']);
            $app->post('', [$opportunityController(), 'create']);
            $app->get('/{id}', [$opportunityController(), 'show']);
            $app->put('/{id}', [$opportunityController(), 'update']);
            $app->delete('/{id}', [$opportunityController(), 'delete']);
            $app->put('/{id}/stage', [$opportunityController(), 'moveStage']);
            $app->get('/{id}/activities', [$opportunityController(), 'activities']);
            $app->post('/{id}/close', [$opportunityController(), 'close']);
        });
        
        // Activities CRUD
        $app->group('/activities', function () use ($app, $container) {
            $activityController = function() use ($container) {
                return new \App\Controllers\ActivityController(
                    $container->get('activityService'),
                    $container->get('logger')
                );
            };
            
            $app->get('', [$activityController(), 'index']);
            $app->get('/calendar', [$activityController(), 'calendar']);
            $app->get('/upcoming', [$activityController(), 'upcoming']);
            $app->get('/stats', [$activityController(), 'stats']);
            $app->post('', [$activityController(), 'create']);
            $app->get('/{id}', [$activityController(), 'show']);
            $app->put('/{id}', [$activityController(), 'update']);
            $app->delete('/{id}', [$activityController(), 'delete']);
            $app->post('/{id}/complete', [$activityController(), 'complete']);
            $app->put('/{id}/reschedule', [$activityController(), 'reschedule']);
        });
        
    });
    
    // Catch-all 404
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', 
        function (Request $request, Response $response): Response {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Route not found',
                'error_code' => 'NOT_FOUND',
                'timestamp' => date('c')
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    );
};
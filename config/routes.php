<?php

declare(strict_types=1);

/**
 * Complete CRM API Routes
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function ($app, $container) {
    
    // CORS middleware
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    // Frontend dashboard route
    $app->get('/app', function (Request $request, Response $response) {
        $appFile = __DIR__ . '/../public/app.html';
        if (file_exists($appFile)) {
            $response->getBody()->write(file_get_contents($appFile));
            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }
        $response->getBody()->write('<h1>CRM Dashboard</h1><p>Dashboard not found. Check installation.</p>');
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    });

    $app->get('/dashboard', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/app')->withStatus(302);
    });

    // Health check
    $app->get('/api/health', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'database' => $db ? 'connected' : 'disconnected',
            'tables' => []
        ];
        
        if ($db && $db->getConnection()) {
            try {
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
                $health['tables'] = array_column($tables, 'name');
            } catch (Exception $e) {
                $health['database'] = 'error: ' . $e->getMessage();
            }
        }
        
        $response->getBody()->write(json_encode($health));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Authentication endpoints
    $app->post('/api/auth/login', function (Request $request, Response $response) use ($container) {
        $data = json_decode($request->getBody()->getContents(), true);
        $db = $container->get('database');
        
        if (!$db || !isset($data['email']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $user = $db->query("SELECT * FROM usuarios WHERE email = ? AND ativo = 1", [$data['email']]);
            
            if (empty($user) || !password_verify($data['password'], $user[0]['senha_hash'])) {
                $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
            
            $userData = $user[0];
            unset($userData['senha_hash']);
            
            // Update last login
            $db->execute("UPDATE usuarios SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?", [$userData['id']]);
            
            // Generate JWT token (simplified)
            $token = base64_encode(json_encode([
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ]));
            
            $response->getBody()->write(json_encode([
                'token' => $token,
                'user' => $userData
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Users CRUD
    $app->get('/api/usuarios', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        try {
            $usuarios = $db->query("SELECT id, nome, email, role, ativo, created_at FROM usuarios ORDER BY nome");
            $response->getBody()->write(json_encode($usuarios));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Empresas CRUD
    $app->get('/api/empresas', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        try {
            $empresas = $db->query("SELECT * FROM empresas ORDER BY nome");
            $response->getBody()->write(json_encode($empresas));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Contatos CRUD
    $app->get('/api/contatos', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        try {
            $contatos = $db->query("
                SELECT c.*, e.nome as empresa_nome, u.nome as owner_nome
                FROM contatos c
                LEFT JOIN empresas e ON c.empresa_id = e.id
                LEFT JOIN usuarios u ON c.owner_id = u.id
                ORDER BY c.nome
            ");
            $response->getBody()->write(json_encode($contatos));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Oportunidades CRUD  
    $app->get('/api/oportunidades', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        try {
            $oportunidades = $db->query("
                SELECT o.*, c.nome as contato_nome, e.nome as empresa_nome, u.nome as owner_nome
                FROM oportunidades o
                LEFT JOIN contatos c ON o.contato_id = c.id
                LEFT JOIN empresas e ON o.empresa_id = e.id
                LEFT JOIN usuarios u ON o.owner_id = u.id
                ORDER BY o.created_at DESC
            ");
            $response->getBody()->write(json_encode($oportunidades));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Dashboard statistics
    $app->get('/api/dashboard/stats', function (Request $request, Response $response) use ($container) {
        $db = $container->get('database');
        try {
            $stats = [
                'usuarios' => $db->query("SELECT COUNT(*) as count FROM usuarios")[0]['count'],
                'empresas' => $db->query("SELECT COUNT(*) as count FROM empresas")[0]['count'],
                'contatos' => $db->query("SELECT COUNT(*) as count FROM contatos")[0]['count'],
                'oportunidades' => $db->query("SELECT COUNT(*) as count FROM oportunidades")[0]['count'],
                'oportunidades_abertas' => $db->query("SELECT COUNT(*) as count FROM oportunidades WHERE estagio NOT IN ('closed_won', 'closed_lost')")[0]['count'],
                'valor_pipeline' => $db->query("SELECT COALESCE(SUM(valor_estimado), 0) as total FROM oportunidades WHERE estagio NOT IN ('closed_won', 'closed_lost')")[0]['total']
            ];
            
            $response->getBody()->write(json_encode($stats));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Serve frontend
    $app->get('/', function (Request $request, Response $response) {
        $html = file_get_contents(__DIR__ . '/../public/app.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Options for CORS
    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });
};
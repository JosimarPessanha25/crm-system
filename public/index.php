<?php
/**
 * CRM System - Simplified Entry Point
 * Sistema CRM completo com dados pré-salvos (sem banco de dados)
 */

// Sistema CRM unificado com dados em memória
class SimpleCRM {
    
    // Dados pré-salvos do sistema
    private $users = [
        ['id' => 1, 'nome' => 'Admin Demo', 'email' => 'demo@test.com', 'role' => 'admin'],
        ['id' => 2, 'nome' => 'João Silva', 'email' => 'joao@empresa.com', 'role' => 'vendedor'],
        ['id' => 3, 'nome' => 'Maria Santos', 'email' => 'maria@vendas.com', 'role' => 'vendedor'],
        ['id' => 4, 'nome' => 'Pedro Costa', 'email' => 'pedro@marketing.com', 'role' => 'marketing']
    ];
    
    private $companies = [
        ['id' => 1, 'nome' => 'TechCorp Ltda', 'cnpj' => '12.345.678/0001-90', 'setor' => 'Tecnologia'],
        ['id' => 2, 'nome' => 'Inovação S.A.', 'cnpj' => '98.765.432/0001-10', 'setor' => 'Consultoria'],
        ['id' => 3, 'nome' => 'StartupX', 'cnpj' => '11.222.333/0001-44', 'setor' => 'Software'],
        ['id' => 4, 'nome' => 'Digital Plus', 'cnpj' => '55.666.777/0001-88', 'setor' => 'Marketing Digital']
    ];
    
    private $contacts = [
        ['id' => 1, 'nome' => 'Carlos Mendes', 'email' => 'carlos@techcorp.com', 'telefone' => '(11) 91234-5678', 'cargo' => 'CTO', 'empresa_id' => 1],
        ['id' => 2, 'nome' => 'Ana Paula Silva', 'email' => 'ana@techcorp.com', 'telefone' => '(11) 91234-5679', 'cargo' => 'Gerente de Vendas', 'empresa_id' => 1],
        ['id' => 3, 'nome' => 'Roberto Lima', 'email' => 'roberto@inovacao.com', 'telefone' => '(21) 98765-4321', 'cargo' => 'CEO', 'empresa_id' => 2],
        ['id' => 4, 'nome' => 'Lucas Oliveira', 'email' => 'lucas@startupx.com', 'telefone' => '(11) 95555-1111', 'cargo' => 'CEO', 'empresa_id' => 3],
        ['id' => 5, 'nome' => 'Rafael Santos', 'email' => 'rafael@digitalplus.com', 'telefone' => '(85) 94444-3333', 'cargo' => 'Diretor de Marketing', 'empresa_id' => 4]
    ];
    
    private $opportunities = [
        ['id' => 1, 'titulo' => 'Sistema CRM Personalizado', 'valor' => 85000.00, 'estagio' => 'proposal', 'empresa_id' => 1, 'contato_id' => 1],
        ['id' => 2, 'titulo' => 'Consultoria Digital', 'valor' => 120000.00, 'estagio' => 'negotiation', 'empresa_id' => 2, 'contato_id' => 3],
        ['id' => 3, 'titulo' => 'App Mobile', 'valor' => 65000.00, 'estagio' => 'qualification', 'empresa_id' => 3, 'contato_id' => 4],
        ['id' => 4, 'titulo' => 'Campanha Marketing', 'valor' => 45000.00, 'estagio' => 'closed_won', 'empresa_id' => 4, 'contato_id' => 5],
        ['id' => 5, 'titulo' => 'E-commerce Platform', 'valor' => 95000.00, 'estagio' => 'closed_won', 'empresa_id' => 1, 'contato_id' => 2]
    ];
    
    public function handleRequest() {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // CORS Headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle OPTIONS
        if ($method === 'OPTIONS') {
            http_response_code(200);
            return;
        }
        
        // API Routes
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            return $this->handleAPI($uri, $method);
        }
        
        // Frontend Routes
        if ($uri === '/' || $uri === '/dashboard' || $uri === '/app') {
            return $this->serveFrontend();
        }
        
        // Static files
        $filePath = __DIR__ . $uri;
        if (file_exists($filePath) && is_file($filePath)) {
            $this->serveStaticFile($filePath);
            return;
        }
        
        // 404
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    
    private function handleAPI($uri, $method) {
        if ($uri === '/api/health') {
            return $this->jsonResponse(['status' => 'healthy', 'version' => '1.0.0']);
        }
        
        if ($uri === '/api/auth/login' && $method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (($data['email'] ?? '') === 'demo@test.com' && ($data['password'] ?? '') === 'demo123') {
                return $this->jsonResponse([
                    'success' => true,
                    'token' => 'demo_token_' . time(),
                    'user' => $this->users[0]
                ]);
            }
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        
        if ($uri === '/api/dashboard/stats') {
            $stats = [
                'totalContacts' => count($this->contacts),
                'totalCompanies' => count($this->companies),
                'activeOpportunities' => count(array_filter($this->opportunities, function($o) { 
                    return !in_array($o['estagio'], ['closed_won', 'closed_lost']); 
                })),
                'wonOpportunities' => count(array_filter($this->opportunities, function($o) { 
                    return $o['estagio'] === 'closed_won'; 
                })),
                'totalRevenue' => array_sum(array_map(function($o) { 
                    return $o['estagio'] === 'closed_won' ? $o['valor'] : 0; 
                }, $this->opportunities)),
                'opportunitiesByStage' => $this->getOpportunitiesByStage()
            ];
            return $this->jsonResponse(['success' => true, 'data' => $stats]);
        }
        
        if ($uri === '/api/dashboard/recent-activities') {
            $activities = array_slice($this->opportunities, -5);
            return $this->jsonResponse(['success' => true, 'data' => $activities]);
        }
        
        if ($uri === '/api/contatos') {
            return $this->jsonResponse($this->getContactsWithCompanies());
        }
        
        if ($uri === '/api/empresas') {
            return $this->jsonResponse($this->companies);
        }
        
        if ($uri === '/api/oportunidades') {
            return $this->jsonResponse($this->getOpportunitiesWithRelations());
        }
        
        return $this->jsonResponse(['error' => 'Endpoint not found'], 404);
    }
    
    private function getOpportunitiesByStage() {
        $stages = ['prospecting' => 0, 'qualification' => 0, 'proposal' => 0, 'negotiation' => 0, 'closed_won' => 0, 'closed_lost' => 0];
        foreach ($this->opportunities as $opp) {
            if (isset($stages[$opp['estagio']])) {
                $stages[$opp['estagio']]++;
            }
        }
        return $stages;
    }
    
    private function getContactsWithCompanies() {
        return array_map(function($contact) {
            $company = crm_array_find($this->companies, function($c) use ($contact) {
                return $c['id'] === $contact['empresa_id'];
            });
            $contact['empresa_nome'] = $company ? $company['nome'] : 'N/A';
            return $contact;
        }, $this->contacts);
    }
    
    private function getOpportunitiesWithRelations() {
        return array_map(function($opp) {
            $contact = crm_array_find($this->contacts, function($c) use ($opp) {
                return $c['id'] === $opp['contato_id'];
            });
            $company = crm_array_find($this->companies, function($c) use ($opp) {
                return $c['id'] === $opp['empresa_id'];
            });
            $opp['contato_nome'] = $contact ? $contact['nome'] : 'N/A';
            $opp['empresa_nome'] = $company ? $company['nome'] : 'N/A';
            return $opp;
        }, $this->opportunities);
    }
    
    private function serveFrontend() {
        $htmlFile = __DIR__ . '/index.html';
        if (file_exists($htmlFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($htmlFile);
        } else {
            echo $this->getBuiltinHTML();
        }
    }
    
    private function serveStaticFile($filePath) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
    }
    
    private function jsonResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
    }
    
    private function getBuiltinHTML() {
        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>CRM System</a>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Dashboard CRM</h2>
                <div class="row" id="dashboard">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Contatos</h5>
                                <h3 id="totalContatos">-</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Empresas</h5>
                                <h3 id="totalEmpresas">-</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Oportunidades</h5>
                                <h3 id="totalOportunidades">-</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Receita</h5>
                                <h3 id="totalReceita">-</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load dashboard data
        fetch("/api/dashboard/stats")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    document.getElementById("totalContatos").textContent = stats.totalContacts;
                    document.getElementById("totalEmpresas").textContent = stats.totalCompanies;
                    document.getElementById("totalOportunidades").textContent = stats.activeOpportunities;
                    document.getElementById("totalReceita").textContent = "R$ " + stats.totalRevenue.toLocaleString("pt-BR");
                }
            })
            .catch(error => console.error("Error:", error));
    </script>
</body>
</html>';
    }
}

// Helper function
if (!function_exists('crm_array_find')) {
    function crm_array_find($array, $callback) {
        foreach ($array as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return null;
    }
}

// Initialize and run CRM system
$crm = new SimpleCRM();
$crm->handleRequest();

<?php declare(strict_types=1);

/**
 * Simplified Bootstrap for CRM System (without Composer dependencies)
 */

// Load manual autoloader
require_once __DIR__ . '/../config/autoloader.php';

// Simple error handler
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set timezone
date_default_timezone_set('America/Sao_Paulo');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema CRM - Demonstração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" style="min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-users-cog"></i> CRM System
                    </h4>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#companies">
                                <i class="fas fa-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#contacts">
                                <i class="fas fa-address-book"></i> Contatos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#opportunities">
                                <i class="fas fa-chart-line"></i> Oportunidades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#activities">
                                <i class="fas fa-tasks"></i> Atividades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#users">
                                <i class="fas fa-users"></i> Usuários
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white">
                    
                    <div class="mt-4">
                        <h6 class="text-white-50 text-uppercase">API Status</h6>
                        <div class="text-white small">
                            <p><i class="fas fa-circle text-success"></i> Sistema Online</p>
                            <p><i class="fas fa-database"></i> MySQL: <?= $_ENV['DB_HOST'] ?></p>
                            <p><i class="fas fa-server"></i> PHP: <?= PHP_VERSION ?></p>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Sistema CRM - Estrutura Completa</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">125</h4>
                                        <p class="card-text">Empresas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">1,567</h4>
                                        <p class="card-text">Contatos</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-address-book fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">89</h4>
                                        <p class="card-text">Oportunidades</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">R$ 2,5M</h4>
                                        <p class="card-text">Pipeline</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Structure Overview -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-code"></i> Estrutura Técnica Implementada</h5>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="techAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingBackend">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBackend">
                                                <i class="fas fa-server me-2"></i> Backend (PHP 8.2+)
                                            </button>
                                        </h2>
                                        <div id="collapseBackend" class="accordion-collapse collapse show" data-bs-parent="#techAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Slim Framework 4.x configurado</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Eloquent ORM integrado</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> JWT Authentication</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> 5 Middlewares (CORS, Auth, RateLimit, etc)</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> PSR-12 Coding Standards</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingDatabase">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDatabase">
                                                <i class="fas fa-database me-2"></i> Banco de Dados
                                            </button>
                                        </h2>
                                        <div id="collapseDatabase" class="accordion-collapse collapse" data-bs-parent="#techAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> MySQL 8.0 Schema completo</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> 6 Tabelas com relacionamentos</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Índices e otimizações</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Dados de exemplo inseridos</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingModels">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModels">
                                                <i class="fas fa-cubes me-2"></i> Models Eloquent
                                            </button>
                                        </h2>
                                        <div id="collapseModels" class="accordion-collapse collapse" data-bs-parent="#techAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> User.php - Autenticação e permissões</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Company.php - Gestão de empresas</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Contact.php - Contatos com lead scoring</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Opportunity.php - Pipeline de vendas</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Activity.php - Atividades polimórficas</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span><i class="fas fa-check text-success"></i> Interaction.php - Timeline de interações</span>
                                                        <span class="badge bg-success">Completo</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list-check"></i> Status do Projeto</h5>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" style="width: 75%">75% Completo</div>
                                </div>
                                
                                <h6 class="text-success"><i class="fas fa-check-circle"></i> Concluído</h6>
                                <ul class="list-unstyled small">
                                    <li>✅ Estrutura do projeto</li>
                                    <li>✅ Configurações</li>
                                    <li>✅ Schema do banco</li>
                                    <li>✅ Models Eloquent</li>
                                    <li>✅ Middlewares</li>
                                    <li>✅ Rotas básicas</li>
                                </ul>
                                
                                <h6 class="text-warning mt-3"><i class="fas fa-clock"></i> Pendente</h6>
                                <ul class="list-unstyled small">
                                    <li>🔄 Controllers CRUD</li>
                                    <li>🔄 Services de negócio</li>
                                    <li>🔄 Testes unitários</li>
                                    <li>🔄 Frontend SPA</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-tools"></i> Tecnologias</h5>
                            </div>
                            <div class="card-body">
                                <span class="badge bg-primary me-1 mb-1">PHP 8.2+</span>
                                <span class="badge bg-success me-1 mb-1">Slim 4.x</span>
                                <span class="badge bg-info me-1 mb-1">MySQL 8.0</span>
                                <span class="badge bg-warning me-1 mb-1">Eloquent ORM</span>
                                <span class="badge bg-danger me-1 mb-1">JWT Auth</span>
                                <span class="badge bg-secondary me-1 mb-1">PSR-12</span>
                                <span class="badge bg-dark me-1 mb-1">Bootstrap 5</span>
                                <span class="badge bg-primary me-1 mb-1">JavaScript ES6+</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- API Endpoints Demo -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plug"></i> API Endpoints Disponíveis</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Autenticação</h6>
                                        <div class="border p-2 mb-2 bg-light">
                                            <code>POST /auth/login</code> - Login do usuário<br>
                                            <code>POST /auth/register</code> - Registro<br>
                                            <code>POST /auth/refresh</code> - Renovar token<br>
                                            <code>POST /auth/logout</code> - Logout
                                        </div>
                                        
                                        <h6 class="text-success">Empresas</h6>
                                        <div class="border p-2 mb-2 bg-light">
                                            <code>GET /api/companies</code> - Listar<br>
                                            <code>POST /api/companies</code> - Criar<br>
                                            <code>PUT /api/companies/{id}</code> - Atualizar<br>
                                            <code>DELETE /api/companies/{id}</code> - Excluir
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-info">Contatos</h6>
                                        <div class="border p-2 mb-2 bg-light">
                                            <code>GET /api/contacts</code> - Listar<br>
                                            <code>POST /api/contacts</code> - Criar<br>
                                            <code>PUT /api/contacts/{id}</code> - Atualizar<br>
                                            <code>DELETE /api/contacts/{id}</code> - Excluir
                                        </div>
                                        
                                        <h6 class="text-warning">Sistema</h6>
                                        <div class="border p-2 mb-2 bg-light">
                                            <code>GET /health</code> - Status do sistema<br>
                                            <code>GET /api/docs</code> - Documentação da API
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Nota:</strong> Todas as rotas da API estão protegidas por autenticação JWT, 
                                    exceto as rotas de autenticação e sistema.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
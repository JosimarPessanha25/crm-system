/**
 * CRM System Opportunities Component
 * Manages sales pipeline, opportunity tracking, and deal management
 */

const Opportunities = {
    // Component data
    data: {
        table: null,
        opportunities: [],
        currentOpportunity: null,
        filters: {
            search: '',
            stage: 'all',
            responsible: 'all',
            dateRange: 'all'
        },
        pagination: {
            current: 1,
            total: 0,
            perPage: 25
        },
        kanbanView: false
    },

    /**
     * Render the opportunities page
     */
    render: function() {
        const pageContent = document.getElementById('pageContent');
        
        pageContent.innerHTML = `
            <div class="container-fluid p-4">
                <!-- Opportunities Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-bullseye me-2 text-primary"></i>Oportunidades
                        </h1>
                        <p class="text-muted mb-0">Gerenciar pipeline de vendas e negociações</p>
                    </div>
                    <div>
                        <div class="btn-group me-2">
                            <button class="btn btn-outline-secondary ${!this.data.kanbanView ? 'active' : ''}" 
                                    onclick="Opportunities.toggleView(false)">
                                <i class="fas fa-list me-1"></i>Lista
                            </button>
                            <button class="btn btn-outline-secondary ${this.data.kanbanView ? 'active' : ''}" 
                                    onclick="Opportunities.toggleView(true)">
                                <i class="fas fa-columns me-1"></i>Kanban
                            </button>
                        </div>
                        <button class="btn btn-primary" onclick="Opportunities.showCreateModal()">
                            <i class="fas fa-plus me-1"></i>Nova Oportunidade
                        </button>
                    </div>
                </div>

                <!-- Pipeline Summary -->
                <div class="row mb-4" id="pipelineSummary">
                    ${this.renderPipelineSkeletons()}
                </div>

                <!-- Filters Row -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Buscar oportunidades..." 
                                   id="opportunitiesSearch">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="opportunityStageFilter">
                            <option value="all">Todos os Estágios</option>
                            <option value="prospeccao">Prospecção</option>
                            <option value="qualificacao">Qualificação</option>
                            <option value="proposta">Proposta</option>
                            <option value="negociacao">Negociação</option>
                            <option value="fechamento">Fechamento</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="opportunityResponsibleFilter">
                            <option value="all">Todos Responsáveis</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="opportunityDateFilter">
                            <option value="all">Todas as Datas</option>
                            <option value="week">Esta Semana</option>
                            <option value="month">Este Mês</option>
                            <option value="quarter">Este Trimestre</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <button class="btn btn-outline-secondary w-100" onclick="Opportunities.clearFilters()">
                            <i class="fas fa-times me-1"></i>Limpar
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div id="opportunitiesContent">
                    <!-- Table View -->
                    <div class="card" id="tableView" style="${this.data.kanbanView ? 'display: none;' : ''}">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="opportunitiesTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllOpportunities">
                                                </div>
                                            </th>
                                            <th>Título</th>
                                            <th>Cliente</th>
                                            <th>Valor</th>
                                            <th>Estágio</th>
                                            <th>Probabilidade</th>
                                            <th>Fechamento Prev.</th>
                                            <th>Responsável</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="opportunitiesTableBody">
                                        ${this.renderTableSkeletons()}
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    <span id="opportunitiesCount">Carregando...</span>
                                </div>
                                <nav id="opportunitiesPagination">
                                    <!-- Pagination will be rendered here -->
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Kanban View -->
                    <div id="kanbanView" style="${!this.data.kanbanView ? 'display: none;' : ''}">
                        <div class="kanban-board" id="kanbanBoard">
                            ${this.renderKanbanSkeletons()}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opportunity Modal -->
            <div class="modal fade" id="opportunityModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="opportunityModalTitle">Nova Oportunidade</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="opportunityForm" novalidate>
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-8 mb-3">
                                        <label for="opportunityTitle" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="opportunityTitle" required>
                                        <div class="invalid-feedback">Título é obrigatório</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="opportunityValue" class="form-label">Valor *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" id="opportunityValue" 
                                                   step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback">Valor é obrigatório</div>
                                    </div>

                                    <!-- Client and Contact -->
                                    <div class="col-md-6 mb-3">
                                        <label for="opportunityClient" class="form-label">Cliente *</label>
                                        <select class="form-select" id="opportunityClient" required>
                                            <option value="">Selecionar cliente...</option>
                                        </select>
                                        <div class="invalid-feedback">Cliente é obrigatório</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="opportunityContact" class="form-label">Contato</label>
                                        <select class="form-select" id="opportunityContact">
                                            <option value="">Selecionar contato...</option>
                                        </select>
                                    </div>

                                    <!-- Stage and Probability -->
                                    <div class="col-md-4 mb-3">
                                        <label for="opportunityStage" class="form-label">Estágio</label>
                                        <select class="form-select" id="opportunityStage">
                                            <option value="prospeccao">Prospecção</option>
                                            <option value="qualificacao">Qualificação</option>
                                            <option value="proposta">Proposta</option>
                                            <option value="negociacao">Negociação</option>
                                            <option value="fechamento">Fechamento</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="opportunityProbability" class="form-label">Probabilidade</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="opportunityProbability" 
                                                   min="0" max="100" value="10">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="opportunityExpectedClose" class="form-label">Fechamento Previsto</label>
                                        <input type="date" class="form-control" id="opportunityExpectedClose">
                                    </div>

                                    <!-- Responsible -->
                                    <div class="col-md-6 mb-3">
                                        <label for="opportunityResponsible" class="form-label">Responsável</label>
                                        <select class="form-select" id="opportunityResponsible">
                                            <option value="">Selecionar responsável...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="opportunitySource" class="form-label">Origem</label>
                                        <select class="form-select" id="opportunitySource">
                                            <option value="">Selecionar...</option>
                                            <option value="website">Website</option>
                                            <option value="referencia">Referência</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="evento">Evento</option>
                                            <option value="telefone">Telefone</option>
                                            <option value="email">Email</option>
                                        </select>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-12 mb-3">
                                        <label for="opportunityDescription" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="opportunityDescription" rows="4"></textarea>
                                    </div>

                                    <!-- Products/Services -->
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Produtos/Serviços</label>
                                        <div id="opportunityProducts">
                                            <div class="row align-items-center mb-2 product-row">
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" placeholder="Nome do produto/serviço">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" class="form-control" placeholder="Qtd" min="1" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text">R$</span>
                                                        <input type="number" class="form-control" placeholder="Valor" step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="Opportunities.addProductRow()">
                                            <i class="fas fa-plus me-1"></i>Adicionar Produto
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="Opportunities.saveOpportunity()">
                                <i class="fas fa-save me-1"></i>Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Initialize and load data
        this.initializeTable();
        this.setupEventListeners();
        this.loadPipelineSummary();
        this.loadOpportunities();
        this.loadSelectOptions();
    },

    /**
     * Initialize DataTable
     */
    initializeTable: function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            this.data.table = $('#opportunitiesTable').DataTable({
                ...Config.dataTables.defaultConfig,
                order: [[7, 'asc']], // Sort by expected close date
                columnDefs: [
                    { orderable: false, targets: [0, 8] }, // Checkbox and actions columns
                    { searchable: false, targets: [0, 8] }
                ]
            });
        }
    },

    /**
     * Setup event listeners
     */
    setupEventListeners: function() {
        // Search input with debounce
        const searchInput = document.getElementById('opportunitiesSearch');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.data.filters.search = e.target.value;
                this.applyFilters();
            }, 300));
        }

        // Stage filter
        const stageFilter = document.getElementById('opportunityStageFilter');
        if (stageFilter) {
            stageFilter.addEventListener('change', (e) => {
                this.data.filters.stage = e.target.value;
                this.applyFilters();
            });
        }

        // Responsible filter
        const responsibleFilter = document.getElementById('opportunityResponsibleFilter');
        if (responsibleFilter) {
            responsibleFilter.addEventListener('change', (e) => {
                this.data.filters.responsible = e.target.value;
                this.applyFilters();
            });
        }

        // Date filter
        const dateFilter = document.getElementById('opportunityDateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.data.filters.dateRange = e.target.value;
                this.applyFilters();
            });
        }

        // Stage change in form (auto-update probability)
        const stageSelect = document.getElementById('opportunityStage');
        if (stageSelect) {
            stageSelect.addEventListener('change', (e) => {
                this.updateProbabilityBasedOnStage(e.target.value);
            });
        }
    },

    /**
     * Load pipeline summary
     */
    loadPipelineSummary: async function() {
        try {
            const response = await API.opportunities.summary();
            this.renderPipelineSummary(response.data);
        } catch (error) {
            console.error('Error loading pipeline summary:', error);
        }
    },

    /**
     * Load opportunities from API
     */
    loadOpportunities: async function(page = 1) {
        try {
            const response = await API.opportunities.list({
                page: page,
                per_page: this.data.pagination.perPage,
                search: this.data.filters.search,
                stage: this.data.filters.stage !== 'all' ? this.data.filters.stage : undefined,
                responsible: this.data.filters.responsible !== 'all' ? this.data.filters.responsible : undefined,
                date_range: this.data.filters.dateRange !== 'all' ? this.data.filters.dateRange : undefined
            });

            this.data.opportunities = response.data.opportunities || [];
            this.data.pagination = {
                current: response.data.current_page || 1,
                total: response.data.total || 0,
                perPage: response.data.per_page || 25
            };

            if (this.data.kanbanView) {
                this.renderKanban();
            } else {
                this.renderTable();
                this.renderPagination();
            }

        } catch (error) {
            console.error('Error loading opportunities:', error);
            Utils.showToast('Erro ao carregar oportunidades', 'error');
        }
    },

    /**
     * Load select options
     */
    loadSelectOptions: async function() {
        try {
            // Load clients/companies
            const clientsResponse = await API.contacts.list({ type: 'cliente' });
            this.populateSelect('opportunityClient', clientsResponse.data.contacts, 'id', 'nome');

            // Load users for responsible
            const usersResponse = await API.users.list();
            this.populateSelect('opportunityResponsible', usersResponse.data.users, 'id', 'nome');
            this.populateSelect('opportunityResponsibleFilter', usersResponse.data.users, 'id', 'nome', true);

        } catch (error) {
            console.error('Error loading select options:', error);
        }
    },

    /**
     * Populate select element
     */
    populateSelect: function(selectId, items, valueField, textField, includeAll = false) {
        const select = document.getElementById(selectId);
        if (!select) return;

        if (includeAll) {
            select.innerHTML = '<option value="all">Todos</option>';
        } else {
            select.innerHTML = '<option value="">Selecionar...</option>';
        }

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            select.appendChild(option);
        });
    },

    /**
     * Render pipeline summary skeletons
     */
    renderPipelineSkeletons: function() {
        return Array(5).fill(0).map(() => `
            <div class="col-lg col-md-6 mb-3">
                <div class="card pipeline-stage-card">
                    <div class="card-body text-center">
                        <div class="skeleton skeleton-title mb-2"></div>
                        <div class="skeleton skeleton-text mb-1"></div>
                        <div class="skeleton skeleton-text" style="width: 60%;"></div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Render pipeline summary
     */
    renderPipelineSummary: function(summary) {
        const summaryContainer = document.getElementById('pipelineSummary');
        
        const stages = ['prospeccao', 'qualificacao', 'proposta', 'negociacao', 'fechamento'];
        
        summaryContainer.innerHTML = stages.map(stage => {
            const stageData = summary[stage] || { count: 0, total_value: 0 };
            const stageConfig = Config.constants.opportunityStages[stage];
            
            return `
                <div class="col-lg col-md-6 mb-3">
                    <div class="card pipeline-stage-card" style="border-left: 4px solid ${stageConfig.color}">
                        <div class="card-body text-center">
                            <div class="stage-icon mb-2" style="color: ${stageConfig.color}">
                                <i class="${stageConfig.icon} fa-2x"></i>
                            </div>
                            <h6 class="stage-name mb-1">${stageConfig.label}</h6>
                            <div class="stage-count fw-bold">${stageData.count} oportunidades</div>
                            <div class="stage-value text-muted">${Utils.formatCurrency(stageData.total_value)}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Toggle between table and kanban view
     */
    toggleView: function(isKanban) {
        this.data.kanbanView = isKanban;
        
        const tableView = document.getElementById('tableView');
        const kanbanView = document.getElementById('kanbanView');
        
        if (isKanban) {
            tableView.style.display = 'none';
            kanbanView.style.display = 'block';
            this.renderKanban();
        } else {
            tableView.style.display = 'block';
            kanbanView.style.display = 'none';
            this.renderTable();
        }
        
        // Update button states
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    },

    /**
     * Render table skeletons
     */
    renderTableSkeletons: function() {
        return Array(10).fill(0).map(() => `
            <tr>
                <td><div class="skeleton skeleton-checkbox"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-badge"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-actions"></div></td>
            </tr>
        `).join('');
    },

    /**
     * Render opportunities table
     */
    renderTable: function() {
        const tbody = document.getElementById('opportunitiesTableBody');
        const countElement = document.getElementById('opportunitiesCount');

        // Update count
        if (countElement) {
            countElement.textContent = `${this.data.opportunities.length} de ${this.data.pagination.total} oportunidades`;
        }

        if (this.data.opportunities.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Nenhuma oportunidade encontrada</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="Opportunities.showCreateModal()">
                            Criar Primeira Oportunidade
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.data.opportunities.map(opportunity => {
            const stage = Config.constants.opportunityStages[opportunity.estagio] || {};
            const isOverdue = new Date(opportunity.fechamento_previsto) < new Date() && 
                            !['ganha', 'perdida'].includes(opportunity.status);

            return `
                <tr class="opportunity-row ${isOverdue ? 'table-warning' : ''}" data-opportunity-id="${opportunity.id}">
                    <td>
                        <div class="form-check">
                            <input class="form-check-input opportunity-checkbox" type="checkbox" value="${opportunity.id}">
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold">${Utils.escapeHtml(opportunity.titulo)}</div>
                        ${opportunity.descricao ? `<small class="text-muted">${Utils.truncateText(opportunity.descricao, 50)}</small>` : ''}
                    </td>
                    <td>${opportunity.cliente ? Utils.escapeHtml(opportunity.cliente.nome) : '-'}</td>
                    <td class="fw-semibold">${Utils.formatCurrency(opportunity.valor)}</td>
                    <td>
                        <span class="badge rounded-pill" style="background-color: ${stage.color}20; color: ${stage.color}; border: 1px solid ${stage.color}40">
                            ${stage.label || opportunity.estagio}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                <div class="progress-bar" style="width: ${opportunity.probabilidade}%; background-color: ${stage.color}"></div>
                            </div>
                            <small>${opportunity.probabilidade}%</small>
                        </div>
                    </td>
                    <td>
                        ${opportunity.fechamento_previsto ? 
                            `<span class="${isOverdue ? 'text-warning fw-semibold' : ''}">${Utils.formatDate(opportunity.fechamento_previsto, 'date')}</span>` :
                            '<span class="text-muted">-</span>'
                        }
                    </td>
                    <td>${opportunity.responsavel ? Utils.escapeHtml(opportunity.responsavel.nome) : '-'}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    onclick="Opportunities.showDetails(${opportunity.id})" 
                                    title="Ver Detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" 
                                    onclick="Opportunities.showEditModal(${opportunity.id})" 
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" 
                                    onclick="Opportunities.confirmDelete(${opportunity.id})" 
                                    title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    /**
     * Render kanban skeletons
     */
    renderKanbanSkeletons: function() {
        return `
            <div class="kanban-column">
                <div class="kanban-header">
                    <div class="skeleton skeleton-text"></div>
                </div>
                <div class="kanban-cards">
                    ${Array(3).fill(0).map(() => '<div class="skeleton skeleton-card"></div>').join('')}
                </div>
            </div>
        `.repeat(5);
    },

    /**
     * Render kanban board
     */
    renderKanban: function() {
        const kanbanBoard = document.getElementById('kanbanBoard');
        
        const stages = ['prospeccao', 'qualificacao', 'proposta', 'negociacao', 'fechamento'];
        
        kanbanBoard.innerHTML = stages.map(stage => {
            const stageConfig = Config.constants.opportunityStages[stage];
            const stageOpportunities = this.data.opportunities.filter(opp => opp.estagio === stage);
            
            return `
                <div class="kanban-column" data-stage="${stage}">
                    <div class="kanban-header" style="border-color: ${stageConfig.color}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="${stageConfig.icon} me-2" style="color: ${stageConfig.color}"></i>
                                <strong>${stageConfig.label}</strong>
                            </div>
                            <span class="badge bg-secondary rounded-pill">${stageOpportunities.length}</span>
                        </div>
                    </div>
                    <div class="kanban-cards" style="min-height: 400px;">
                        ${stageOpportunities.map(opportunity => this.renderKanbanCard(opportunity)).join('')}
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render kanban card for opportunity
     */
    renderKanbanCard: function(opportunity) {
        const isOverdue = new Date(opportunity.fechamento_previsto) < new Date() && 
                         !['ganha', 'perdida'].includes(opportunity.status);

        return `
            <div class="kanban-card ${isOverdue ? 'overdue' : ''}" data-opportunity-id="${opportunity.id}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="mb-1">${Utils.escapeHtml(opportunity.titulo)}</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="Opportunities.showDetails(${opportunity.id})">Ver Detalhes</a></li>
                                <li><a class="dropdown-item" href="#" onclick="Opportunities.showEditModal(${opportunity.id})">Editar</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="Opportunities.confirmDelete(${opportunity.id})">Excluir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong class="text-primary">${Utils.formatCurrency(opportunity.valor)}</strong>
                    </div>
                    ${opportunity.cliente ? `<div class="text-muted small mb-2">${Utils.escapeHtml(opportunity.cliente.nome)}</div>` : ''}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Probabilidade:</span>
                        <span class="small fw-semibold">${opportunity.probabilidade}%</span>
                    </div>
                    ${opportunity.fechamento_previsto ? 
                        `<div class="text-muted small">
                            <i class="fas fa-calendar-alt me-1"></i>
                            ${Utils.formatDate(opportunity.fechamento_previsto, 'date')}
                        </div>` : ''
                    }
                </div>
            </div>
        `;
    },

    /**
     * Update probability based on stage
     */
    updateProbabilityBasedOnStage: function(stage) {
        const probabilities = {
            'prospeccao': 10,
            'qualificacao': 25,
            'proposta': 50,
            'negociacao': 75,
            'fechamento': 90
        };
        
        const probabilityInput = document.getElementById('opportunityProbability');
        if (probabilityInput && probabilities[stage]) {
            probabilityInput.value = probabilities[stage];
        }
    },

    /**
     * Add product row to form
     */
    addProductRow: function() {
        const container = document.getElementById('opportunityProducts');
        const newRow = document.createElement('div');
        newRow.className = 'row align-items-center mb-2 product-row';
        newRow.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" placeholder="Nome do produto/serviço">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" placeholder="Qtd" min="1" value="1">
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="number" class="form-control" placeholder="Valor" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
    },

    /**
     * Render pagination
     */
    renderPagination: function() {
        const paginationElement = document.getElementById('opportunitiesPagination');
        if (!paginationElement) return;

        const totalPages = Math.ceil(this.data.pagination.total / this.data.pagination.perPage);
        const current = this.data.pagination.current;

        if (totalPages <= 1) {
            paginationElement.innerHTML = '';
            return;
        }

        let paginationHTML = '<ul class="pagination pagination-sm mb-0">';

        // Previous button
        paginationHTML += `
            <li class="page-item ${current === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="Opportunities.loadOpportunities(${current - 1})">Anterior</a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(totalPages, current + 2);

        if (startPage > 1) {
            paginationHTML += '<li class="page-item"><a class="page-link" href="#" onclick="Opportunities.loadOpportunities(1)">1</a></li>';
            if (startPage > 2) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === current ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="Opportunities.loadOpportunities(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="Opportunities.loadOpportunities(${totalPages})">${totalPages}</a></li>`;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${current === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="Opportunities.loadOpportunities(${current + 1})">Próxima</a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationElement.innerHTML = paginationHTML;
    },

    /**
     * Apply current filters
     */
    applyFilters: function() {
        this.loadOpportunities(1);
    },

    /**
     * Clear all filters
     */
    clearFilters: function() {
        this.data.filters = {
            search: '',
            stage: 'all',
            responsible: 'all',
            dateRange: 'all'
        };

        // Reset form inputs
        document.getElementById('opportunitiesSearch').value = '';
        document.getElementById('opportunityStageFilter').value = 'all';
        document.getElementById('opportunityResponsibleFilter').value = 'all';
        document.getElementById('opportunityDateFilter').value = 'all';

        this.loadOpportunities(1);
    },

    /**
     * Show create opportunity modal
     */
    showCreateModal: function() {
        this.data.currentOpportunity = null;
        document.getElementById('opportunityModalTitle').textContent = 'Nova Oportunidade';
        document.getElementById('opportunityForm').reset();
        
        const modal = new bootstrap.Modal(document.getElementById('opportunityModal'));
        modal.show();
    },

    /**
     * Show edit opportunity modal
     */
    showEditModal: function(opportunityId) {
        const opportunity = this.data.opportunities.find(o => o.id === opportunityId);
        if (!opportunity) return;

        this.data.currentOpportunity = opportunity;
        document.getElementById('opportunityModalTitle').textContent = 'Editar Oportunidade';

        // Fill form with opportunity data
        document.getElementById('opportunityTitle').value = opportunity.titulo || '';
        document.getElementById('opportunityValue').value = opportunity.valor || '';
        document.getElementById('opportunityClient').value = opportunity.cliente_id || '';
        document.getElementById('opportunityContact').value = opportunity.contato_id || '';
        document.getElementById('opportunityStage').value = opportunity.estagio || 'prospeccao';
        document.getElementById('opportunityProbability').value = opportunity.probabilidade || 10;
        document.getElementById('opportunityExpectedClose').value = opportunity.fechamento_previsto || '';
        document.getElementById('opportunityResponsible').value = opportunity.responsavel_id || '';
        document.getElementById('opportunitySource').value = opportunity.origem || '';
        document.getElementById('opportunityDescription').value = opportunity.descricao || '';

        const modal = new bootstrap.Modal(document.getElementById('opportunityModal'));
        modal.show();
    },

    /**
     * Save opportunity (create or update)
     */
    saveOpportunity: async function() {
        const form = document.getElementById('opportunityForm');
        
        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Collect products data
        const products = [];
        document.querySelectorAll('.product-row').forEach(row => {
            const name = row.querySelector('input[placeholder="Nome do produto/serviço"]').value;
            const quantity = row.querySelector('input[placeholder="Qtd"]').value;
            const price = row.querySelector('input[placeholder="Valor"]').value;
            
            if (name && quantity && price) {
                products.push({
                    nome: name,
                    quantidade: parseInt(quantity),
                    valor_unitario: parseFloat(price)
                });
            }
        });

        const opportunityData = {
            titulo: document.getElementById('opportunityTitle').value,
            valor: parseFloat(document.getElementById('opportunityValue').value),
            cliente_id: document.getElementById('opportunityClient').value,
            contato_id: document.getElementById('opportunityContact').value || null,
            estagio: document.getElementById('opportunityStage').value,
            probabilidade: parseInt(document.getElementById('opportunityProbability').value),
            fechamento_previsto: document.getElementById('opportunityExpectedClose').value || null,
            responsavel_id: document.getElementById('opportunityResponsible').value || null,
            origem: document.getElementById('opportunitySource').value || null,
            descricao: document.getElementById('opportunityDescription').value || null,
            produtos: products
        };

        try {
            let response;
            
            if (this.data.currentOpportunity) {
                // Update existing opportunity
                response = await API.opportunities.update(this.data.currentOpportunity.id, opportunityData);
                Utils.showToast('Oportunidade atualizada com sucesso', 'success');
            } else {
                // Create new opportunity
                response = await API.opportunities.create(opportunityData);
                Utils.showToast('Oportunidade criada com sucesso', 'success');
            }

            // Close modal and refresh data
            bootstrap.Modal.getInstance(document.getElementById('opportunityModal')).hide();
            this.loadOpportunities(this.data.pagination.current);
            this.loadPipelineSummary();

        } catch (error) {
            console.error('Error saving opportunity:', error);
            Utils.showToast('Erro ao salvar oportunidade', 'error');
        }
    },

    /**
     * Show opportunity details
     */
    showDetails: function(opportunityId) {
        Utils.showToast('Funcionalidade de detalhes em desenvolvimento', 'info');
    },

    /**
     * Confirm opportunity deletion
     */
    confirmDelete: function(opportunityId) {
        const opportunity = this.data.opportunities.find(o => o.id === opportunityId);
        if (!opportunity) return;

        if (confirm(`Tem certeza que deseja excluir a oportunidade "${opportunity.titulo}"?`)) {
            this.deleteOpportunity(opportunityId);
        }
    },

    /**
     * Delete opportunity
     */
    deleteOpportunity: async function(opportunityId) {
        try {
            await API.opportunities.delete(opportunityId);
            Utils.showToast('Oportunidade excluída com sucesso', 'success');
            this.loadOpportunities(this.data.pagination.current);
            this.loadPipelineSummary();
        } catch (error) {
            console.error('Error deleting opportunity:', error);
            Utils.showToast('Erro ao excluir oportunidade', 'error');
        }
    }
};

// Export Opportunities globally
window.Opportunities = Opportunities;
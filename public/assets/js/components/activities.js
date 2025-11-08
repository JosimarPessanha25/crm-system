/**
 * CRM System Activities Component
 * Manages tasks, events, calls, meetings and follow-ups
 */

const Activities = {
    // Component data
    data: {
        table: null,
        activities: [],
        currentActivity: null,
        filters: {
            search: '',
            type: 'all',
            status: 'all',
            dateRange: 'all'
        },
        pagination: {
            current: 1,
            total: 0,
            perPage: 25
        },
        calendarView: false
    },

    /**
     * Render the activities page
     */
    render: function() {
        const pageContent = document.getElementById('pageContent');
        
        pageContent.innerHTML = `
            <div class="container-fluid p-4">
                <!-- Activities Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-tasks me-2 text-primary"></i>Atividades
                        </h1>
                        <p class="text-muted mb-0">Gerenciar tarefas, eventos e follow-ups</p>
                    </div>
                    <div>
                        <div class="btn-group me-2">
                            <button class="btn btn-outline-secondary ${!this.data.calendarView ? 'active' : ''}" 
                                    onclick="Activities.toggleView(false)">
                                <i class="fas fa-list me-1"></i>Lista
                            </button>
                            <button class="btn btn-outline-secondary ${this.data.calendarView ? 'active' : ''}" 
                                    onclick="Activities.toggleView(true)">
                                <i class="fas fa-calendar me-1"></i>Calendário
                            </button>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-plus me-1"></i>Nova Atividade
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="Activities.showCreateModal('tarefa')">
                                    <i class="fas fa-check-square me-2"></i>Tarefa
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="Activities.showCreateModal('ligacao')">
                                    <i class="fas fa-phone me-2"></i>Ligação
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="Activities.showCreateModal('reuniao')">
                                    <i class="fas fa-users me-2"></i>Reunião
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="Activities.showCreateModal('email')">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="Activities.showCreateModal('evento')">
                                    <i class="fas fa-calendar-plus me-2"></i>Evento
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4" id="activitiesStats">
                    ${this.renderStatsSkeletons()}
                </div>

                <!-- Filters Row -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Buscar atividades..." 
                                   id="activitiesSearch">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="activityTypeFilter">
                            <option value="all">Todos os Tipos</option>
                            <option value="tarefa">Tarefa</option>
                            <option value="ligacao">Ligação</option>
                            <option value="reuniao">Reunião</option>
                            <option value="email">Email</option>
                            <option value="evento">Evento</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="activityStatusFilter">
                            <option value="all">Todos os Status</option>
                            <option value="pendente">Pendente</option>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="concluida">Concluída</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <select class="form-select" id="activityDateFilter">
                            <option value="all">Todas as Datas</option>
                            <option value="today">Hoje</option>
                            <option value="tomorrow">Amanhã</option>
                            <option value="week">Esta Semana</option>
                            <option value="overdue">Atrasadas</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <button class="btn btn-outline-secondary w-100" onclick="Activities.clearFilters()">
                            <i class="fas fa-times me-1"></i>Limpar
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div id="activitiesContent">
                    <!-- List View -->
                    <div class="card" id="listView" style="${this.data.calendarView ? 'display: none;' : ''}">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="activitiesTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllActivities">
                                                </div>
                                            </th>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Relacionado</th>
                                            <th>Data/Hora</th>
                                            <th>Status</th>
                                            <th>Prioridade</th>
                                            <th>Responsável</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activitiesTableBody">
                                        ${this.renderTableSkeletons()}
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    <span id="activitiesCount">Carregando...</span>
                                </div>
                                <nav id="activitiesPagination">
                                    <!-- Pagination will be rendered here -->
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar View -->
                    <div class="card" id="calendarView" style="${!this.data.calendarView ? 'display: none;' : ''}">
                        <div class="card-body">
                            <div id="activitiesCalendar" style="min-height: 600px;">
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Visualização de calendário em desenvolvimento</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Modal -->
            <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="activityModalTitle">Nova Atividade</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="activityForm" novalidate>
                                <div class="row">
                                    <!-- Activity Type -->
                                    <div class="col-md-6 mb-3">
                                        <label for="activityType" class="form-label">Tipo *</label>
                                        <select class="form-select" id="activityType" required>
                                            <option value="">Selecionar tipo...</option>
                                            <option value="tarefa">Tarefa</option>
                                            <option value="ligacao">Ligação</option>
                                            <option value="reuniao">Reunião</option>
                                            <option value="email">Email</option>
                                            <option value="evento">Evento</option>
                                        </select>
                                        <div class="invalid-feedback">Tipo é obrigatório</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="activityPriority" class="form-label">Prioridade</label>
                                        <select class="form-select" id="activityPriority">
                                            <option value="baixa">Baixa</option>
                                            <option value="media" selected>Média</option>
                                            <option value="alta">Alta</option>
                                            <option value="urgente">Urgente</option>
                                        </select>
                                    </div>

                                    <!-- Title and Description -->
                                    <div class="col-12 mb-3">
                                        <label for="activityTitle" class="form-label">Título *</label>
                                        <input type="text" class="form-control" id="activityTitle" required>
                                        <div class="invalid-feedback">Título é obrigatório</div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="activityDescription" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="activityDescription" rows="3"></textarea>
                                    </div>

                                    <!-- Date and Time -->
                                    <div class="col-md-6 mb-3">
                                        <label for="activityDate" class="form-label">Data *</label>
                                        <input type="date" class="form-control" id="activityDate" required>
                                        <div class="invalid-feedback">Data é obrigatória</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="activityTime" class="form-label">Horário</label>
                                        <input type="time" class="form-control" id="activityTime">
                                    </div>

                                    <!-- Duration (for meetings/calls) -->
                                    <div class="col-md-6 mb-3" id="durationField" style="display: none;">
                                        <label for="activityDuration" class="form-label">Duração (minutos)</label>
                                        <input type="number" class="form-control" id="activityDuration" min="15" step="15" value="60">
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6 mb-3">
                                        <label for="activityStatus" class="form-label">Status</label>
                                        <select class="form-select" id="activityStatus">
                                            <option value="pendente" selected>Pendente</option>
                                            <option value="em_andamento">Em Andamento</option>
                                            <option value="concluida">Concluída</option>
                                            <option value="cancelada">Cancelada</option>
                                        </select>
                                    </div>

                                    <!-- Related Records -->
                                    <div class="col-md-6 mb-3">
                                        <label for="activityContact" class="form-label">Contato Relacionado</label>
                                        <select class="form-select" id="activityContact">
                                            <option value="">Selecionar contato...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="activityOpportunity" class="form-label">Oportunidade Relacionada</label>
                                        <select class="form-select" id="activityOpportunity">
                                            <option value="">Selecionar oportunidade...</option>
                                        </select>
                                    </div>

                                    <!-- Responsible -->
                                    <div class="col-md-6 mb-3">
                                        <label for="activityResponsible" class="form-label">Responsável</label>
                                        <select class="form-select" id="activityResponsible">
                                            <option value="">Selecionar responsável...</option>
                                        </select>
                                    </div>

                                    <!-- Location (for meetings/events) -->
                                    <div class="col-md-6 mb-3" id="locationField" style="display: none;">
                                        <label for="activityLocation" class="form-label">Local</label>
                                        <input type="text" class="form-control" id="activityLocation">
                                    </div>

                                    <!-- Reminder -->
                                    <div class="col-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="activityReminder">
                                            <label class="form-check-label" for="activityReminder">
                                                Definir lembrete
                                            </label>
                                        </div>
                                        <div id="reminderOptions" style="display: none;" class="mt-2">
                                            <select class="form-select" id="reminderTime">
                                                <option value="15">15 minutos antes</option>
                                                <option value="30" selected>30 minutos antes</option>
                                                <option value="60">1 hora antes</option>
                                                <option value="1440">1 dia antes</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-12 mb-3">
                                        <label for="activityNotes" class="form-label">Observações</label>
                                        <textarea class="form-control" id="activityNotes" rows="2"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="Activities.saveActivity()">
                                <i class="fas fa-save me-1"></i>Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Details Modal -->
            <div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalhes da Atividade</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="activityDetailsContent">
                            <!-- Details content will be rendered here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="button" class="btn btn-primary" onclick="Activities.showEditModalFromDetails()">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Initialize and load data
        this.initializeTable();
        this.setupEventListeners();
        this.loadActivitiesStats();
        this.loadActivities();
        this.loadSelectOptions();
    },

    /**
     * Initialize DataTable
     */
    initializeTable: function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            this.data.table = $('#activitiesTable').DataTable({
                ...Config.dataTables.defaultConfig,
                order: [[4, 'asc']], // Sort by date/time
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
        const searchInput = document.getElementById('activitiesSearch');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.data.filters.search = e.target.value;
                this.applyFilters();
            }, 300));
        }

        // Filter changes
        ['activityTypeFilter', 'activityStatusFilter', 'activityDateFilter'].forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', (e) => {
                    const filterType = filterId.replace('activity', '').replace('Filter', '').toLowerCase();
                    this.data.filters[filterType] = e.target.value;
                    this.applyFilters();
                });
            }
        });

        // Activity type change in form
        const activityTypeSelect = document.getElementById('activityType');
        if (activityTypeSelect) {
            activityTypeSelect.addEventListener('change', (e) => {
                this.toggleFormFieldsBasedOnType(e.target.value);
            });
        }

        // Reminder checkbox
        const reminderCheckbox = document.getElementById('activityReminder');
        if (reminderCheckbox) {
            reminderCheckbox.addEventListener('change', (e) => {
                const reminderOptions = document.getElementById('reminderOptions');
                if (reminderOptions) {
                    reminderOptions.style.display = e.target.checked ? 'block' : 'none';
                }
            });
        }
    },

    /**
     * Toggle form fields based on activity type
     */
    toggleFormFieldsBasedOnType: function(type) {
        const durationField = document.getElementById('durationField');
        const locationField = document.getElementById('locationField');

        // Show duration for meetings and calls
        if (durationField) {
            durationField.style.display = ['reuniao', 'ligacao'].includes(type) ? 'block' : 'none';
        }

        // Show location for meetings and events
        if (locationField) {
            locationField.style.display = ['reuniao', 'evento'].includes(type) ? 'block' : 'none';
        }
    },

    /**
     * Load activities statistics
     */
    loadActivitiesStats: async function() {
        try {
            const response = await API.activities.stats();
            this.renderActivitiesStats(response.data);
        } catch (error) {
            console.error('Error loading activities stats:', error);
        }
    },

    /**
     * Load activities from API
     */
    loadActivities: async function(page = 1) {
        try {
            const response = await API.activities.list({
                page: page,
                per_page: this.data.pagination.perPage,
                search: this.data.filters.search,
                type: this.data.filters.type !== 'all' ? this.data.filters.type : undefined,
                status: this.data.filters.status !== 'all' ? this.data.filters.status : undefined,
                date_range: this.data.filters.dateRange !== 'all' ? this.data.filters.dateRange : undefined
            });

            this.data.activities = response.data.activities || [];
            this.data.pagination = {
                current: response.data.current_page || 1,
                total: response.data.total || 0,
                perPage: response.data.per_page || 25
            };

            this.renderTable();
            this.renderPagination();

        } catch (error) {
            console.error('Error loading activities:', error);
            Utils.showToast('Erro ao carregar atividades', 'error');
        }
    },

    /**
     * Load select options
     */
    loadSelectOptions: async function() {
        try {
            // Load contacts
            const contactsResponse = await API.contacts.list();
            this.populateSelect('activityContact', contactsResponse.data.contacts, 'id', 'nome');

            // Load opportunities
            const opportunitiesResponse = await API.opportunities.list();
            this.populateSelect('activityOpportunity', opportunitiesResponse.data.opportunities, 'id', 'titulo');

            // Load users for responsible
            const usersResponse = await API.users.list();
            this.populateSelect('activityResponsible', usersResponse.data.users, 'id', 'nome');

        } catch (error) {
            console.error('Error loading select options:', error);
        }
    },

    /**
     * Populate select element
     */
    populateSelect: function(selectId, items, valueField, textField) {
        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = '<option value="">Selecionar...</option>';
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            select.appendChild(option);
        });
    },

    /**
     * Render stats skeletons
     */
    renderStatsSkeletons: function() {
        return Array(4).fill(0).map(() => `
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card activity-stat-card">
                    <div class="card-body text-center">
                        <div class="skeleton skeleton-icon mb-2"></div>
                        <div class="skeleton skeleton-title mb-1"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Render activities statistics
     */
    renderActivitiesStats: function(stats) {
        const statsContainer = document.getElementById('activitiesStats');

        const statsData = [
            {
                title: 'Pendentes',
                count: stats.pendente || 0,
                icon: 'fas fa-clock',
                color: 'warning'
            },
            {
                title: 'Hoje',
                count: stats.hoje || 0,
                icon: 'fas fa-calendar-day',
                color: 'primary'
            },
            {
                title: 'Atrasadas',
                count: stats.atrasadas || 0,
                icon: 'fas fa-exclamation-triangle',
                color: 'danger'
            },
            {
                title: 'Concluídas (Mês)',
                count: stats.concluidas_mes || 0,
                icon: 'fas fa-check-circle',
                color: 'success'
            }
        ];

        statsContainer.innerHTML = statsData.map(stat => `
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card activity-stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-2">
                            <i class="${stat.icon} fa-2x text-${stat.color}"></i>
                        </div>
                        <div class="stat-count h4 mb-1 text-${stat.color}">${stat.count}</div>
                        <div class="stat-title text-muted">${stat.title}</div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Toggle between list and calendar view
     */
    toggleView: function(isCalendar) {
        this.data.calendarView = isCalendar;
        
        const listView = document.getElementById('listView');
        const calendarView = document.getElementById('calendarView');
        
        if (isCalendar) {
            listView.style.display = 'none';
            calendarView.style.display = 'block';
        } else {
            listView.style.display = 'block';
            calendarView.style.display = 'none';
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
                <td><div class="skeleton skeleton-badge"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-badge"></div></td>
                <td><div class="skeleton skeleton-badge"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-actions"></div></td>
            </tr>
        `).join('');
    },

    /**
     * Render activities table
     */
    renderTable: function() {
        const tbody = document.getElementById('activitiesTableBody');
        const countElement = document.getElementById('activitiesCount');

        // Update count
        if (countElement) {
            countElement.textContent = `${this.data.activities.length} de ${this.data.pagination.total} atividades`;
        }

        if (this.data.activities.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Nenhuma atividade encontrada</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="Activities.showCreateModal()">
                            Criar Primeira Atividade
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.data.activities.map(activity => {
            const activityType = Config.constants.activityTypes[activity.tipo] || {};
            const isOverdue = new Date(activity.data_vencimento) < new Date() && 
                            !['concluida', 'cancelada'].includes(activity.status);
            const isToday = Utils.isToday(activity.data_vencimento);

            return `
                <tr class="activity-row ${isOverdue ? 'table-warning' : ''} ${isToday ? 'table-info' : ''}" 
                    data-activity-id="${activity.id}">
                    <td>
                        <div class="form-check">
                            <input class="form-check-input activity-checkbox" type="checkbox" value="${activity.id}">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="activity-type-icon me-2">
                                <i class="${activityType.icon || 'fas fa-tasks'}" 
                                   style="color: ${activityType.color || '#6c757d'}"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">${Utils.escapeHtml(activity.titulo)}</div>
                                ${activity.descricao ? `<small class="text-muted">${Utils.truncateText(activity.descricao, 50)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill" 
                              style="background-color: ${activityType.color || '#6c757d'}20; color: ${activityType.color || '#6c757d'}; border: 1px solid ${activityType.color || '#6c757d'}40">
                            ${activityType.label || activity.tipo}
                        </span>
                    </td>
                    <td>
                        ${activity.contato ? 
                            `<div>
                                <div class="fw-semibold">${Utils.escapeHtml(activity.contato.nome)}</div>
                                ${activity.contato.empresa ? `<small class="text-muted">${Utils.escapeHtml(activity.contato.empresa)}</small>` : ''}
                            </div>` :
                            (activity.oportunidade ? 
                                `<div class="text-primary">${Utils.escapeHtml(activity.oportunidade.titulo)}</div>` :
                                '<span class="text-muted">-</span>')
                        }
                    </td>
                    <td>
                        <div>
                            ${Utils.formatDate(activity.data_vencimento, 'date')}
                            ${activity.horario ? `<br><small class="text-muted">${Utils.formatTime(activity.horario)}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${this.getStatusBadgeColor(activity.status)} rounded-pill">
                            ${this.getStatusLabel(activity.status)}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${this.getPriorityBadgeColor(activity.prioridade)} rounded-pill">
                            ${this.getPriorityLabel(activity.prioridade)}
                        </span>
                    </td>
                    <td>${activity.responsavel ? Utils.escapeHtml(activity.responsavel.nome) : '-'}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    onclick="Activities.showDetails(${activity.id})" 
                                    title="Ver Detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${activity.status !== 'concluida' ? 
                                `<button class="btn btn-outline-success" 
                                        onclick="Activities.markAsCompleted(${activity.id})" 
                                        title="Marcar como Concluída">
                                    <i class="fas fa-check"></i>
                                </button>` : ''
                            }
                            <button class="btn btn-outline-secondary" 
                                    onclick="Activities.showEditModal(${activity.id})" 
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" 
                                    onclick="Activities.confirmDelete(${activity.id})" 
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
     * Get status badge color
     */
    getStatusBadgeColor: function(status) {
        const colors = {
            'pendente': 'warning',
            'em_andamento': 'info',
            'concluida': 'success',
            'cancelada': 'secondary'
        };
        return colors[status] || 'secondary';
    },

    /**
     * Get status label
     */
    getStatusLabel: function(status) {
        const labels = {
            'pendente': 'Pendente',
            'em_andamento': 'Em Andamento',
            'concluida': 'Concluída',
            'cancelada': 'Cancelada'
        };
        return labels[status] || status;
    },

    /**
     * Get priority badge color
     */
    getPriorityBadgeColor: function(priority) {
        const colors = {
            'baixa': 'secondary',
            'media': 'primary',
            'alta': 'warning',
            'urgente': 'danger'
        };
        return colors[priority] || 'secondary';
    },

    /**
     * Get priority label
     */
    getPriorityLabel: function(priority) {
        const labels = {
            'baixa': 'Baixa',
            'media': 'Média',
            'alta': 'Alta',
            'urgente': 'Urgente'
        };
        return labels[priority] || priority;
    },

    /**
     * Render pagination
     */
    renderPagination: function() {
        const paginationElement = document.getElementById('activitiesPagination');
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
                <a class="page-link" href="#" onclick="Activities.loadActivities(${current - 1})">Anterior</a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(totalPages, current + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === current ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="Activities.loadActivities(${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${current === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="Activities.loadActivities(${current + 1})">Próxima</a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationElement.innerHTML = paginationHTML;
    },

    /**
     * Apply current filters
     */
    applyFilters: function() {
        this.loadActivities(1);
    },

    /**
     * Clear all filters
     */
    clearFilters: function() {
        this.data.filters = {
            search: '',
            type: 'all',
            status: 'all',
            dateRange: 'all'
        };

        // Reset form inputs
        document.getElementById('activitiesSearch').value = '';
        document.getElementById('activityTypeFilter').value = 'all';
        document.getElementById('activityStatusFilter').value = 'all';
        document.getElementById('activityDateFilter').value = 'all';

        this.loadActivities(1);
    },

    /**
     * Show create activity modal
     */
    showCreateModal: function(type = '') {
        this.data.currentActivity = null;
        document.getElementById('activityModalTitle').textContent = 'Nova Atividade';
        document.getElementById('activityForm').reset();
        
        if (type) {
            document.getElementById('activityType').value = type;
            this.toggleFormFieldsBasedOnType(type);
        }
        
        // Set default date to today
        document.getElementById('activityDate').value = new Date().toISOString().split('T')[0];
        
        const modal = new bootstrap.Modal(document.getElementById('activityModal'));
        modal.show();
    },

    /**
     * Show edit activity modal
     */
    showEditModal: function(activityId) {
        const activity = this.data.activities.find(a => a.id === activityId);
        if (!activity) return;

        this.data.currentActivity = activity;
        document.getElementById('activityModalTitle').textContent = 'Editar Atividade';

        // Fill form with activity data
        document.getElementById('activityType').value = activity.tipo || '';
        document.getElementById('activityPriority').value = activity.prioridade || 'media';
        document.getElementById('activityTitle').value = activity.titulo || '';
        document.getElementById('activityDescription').value = activity.descricao || '';
        document.getElementById('activityDate').value = activity.data_vencimento || '';
        document.getElementById('activityTime').value = activity.horario || '';
        document.getElementById('activityDuration').value = activity.duracao || 60;
        document.getElementById('activityStatus').value = activity.status || 'pendente';
        document.getElementById('activityContact').value = activity.contato_id || '';
        document.getElementById('activityOpportunity').value = activity.oportunidade_id || '';
        document.getElementById('activityResponsible').value = activity.responsavel_id || '';
        document.getElementById('activityLocation').value = activity.local || '';
        document.getElementById('activityNotes').value = activity.observacoes || '';

        // Handle reminder
        const hasReminder = activity.lembrete_minutos > 0;
        document.getElementById('activityReminder').checked = hasReminder;
        document.getElementById('reminderOptions').style.display = hasReminder ? 'block' : 'none';
        if (hasReminder) {
            document.getElementById('reminderTime').value = activity.lembrete_minutos;
        }

        this.toggleFormFieldsBasedOnType(activity.tipo);

        const modal = new bootstrap.Modal(document.getElementById('activityModal'));
        modal.show();
    },

    /**
     * Save activity (create or update)
     */
    saveActivity: async function() {
        const form = document.getElementById('activityForm');
        
        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const activityData = {
            tipo: document.getElementById('activityType').value,
            titulo: document.getElementById('activityTitle').value,
            descricao: document.getElementById('activityDescription').value || null,
            data_vencimento: document.getElementById('activityDate').value,
            horario: document.getElementById('activityTime').value || null,
            duracao: document.getElementById('activityDuration').value || null,
            status: document.getElementById('activityStatus').value,
            prioridade: document.getElementById('activityPriority').value,
            contato_id: document.getElementById('activityContact').value || null,
            oportunidade_id: document.getElementById('activityOpportunity').value || null,
            responsavel_id: document.getElementById('activityResponsible').value || null,
            local: document.getElementById('activityLocation').value || null,
            observacoes: document.getElementById('activityNotes').value || null,
            lembrete_minutos: document.getElementById('activityReminder').checked ? 
                document.getElementById('reminderTime').value : 0
        };

        try {
            let response;
            
            if (this.data.currentActivity) {
                // Update existing activity
                response = await API.activities.update(this.data.currentActivity.id, activityData);
                Utils.showToast('Atividade atualizada com sucesso', 'success');
            } else {
                // Create new activity
                response = await API.activities.create(activityData);
                Utils.showToast('Atividade criada com sucesso', 'success');
            }

            // Close modal and refresh data
            bootstrap.Modal.getInstance(document.getElementById('activityModal')).hide();
            this.loadActivities(this.data.pagination.current);
            this.loadActivitiesStats();

        } catch (error) {
            console.error('Error saving activity:', error);
            Utils.showToast('Erro ao salvar atividade', 'error');
        }
    },

    /**
     * Show activity details
     */
    showDetails: function(activityId) {
        const activity = this.data.activities.find(a => a.id === activityId);
        if (!activity) return;

        this.data.currentActivity = activity;
        const activityType = Config.constants.activityTypes[activity.tipo] || {};
        
        const detailsContent = document.getElementById('activityDetailsContent');
        detailsContent.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-3">
                        <i class="${activityType.icon || 'fas fa-tasks'} me-2" 
                           style="color: ${activityType.color || '#6c757d'}"></i>
                        ${Utils.escapeHtml(activity.titulo)}
                    </h5>
                    
                    ${activity.descricao ? 
                        `<div class="mb-3">
                            <h6>Descrição:</h6>
                            <p class="text-muted">${Utils.escapeHtml(activity.descricao)}</p>
                        </div>` : ''
                    }

                    ${activity.observacoes ? 
                        `<div class="mb-3">
                            <h6>Observações:</h6>
                            <p class="text-muted">${Utils.escapeHtml(activity.observacoes)}</p>
                        </div>` : ''
                    }
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Informações</h6>
                            
                            <div class="mb-2">
                                <strong>Tipo:</strong> ${activityType.label || activity.tipo}
                            </div>
                            
                            <div class="mb-2">
                                <strong>Status:</strong> 
                                <span class="badge bg-${this.getStatusBadgeColor(activity.status)} ms-1">
                                    ${this.getStatusLabel(activity.status)}
                                </span>
                            </div>
                            
                            <div class="mb-2">
                                <strong>Prioridade:</strong> 
                                <span class="badge bg-${this.getPriorityBadgeColor(activity.prioridade)} ms-1">
                                    ${this.getPriorityLabel(activity.prioridade)}
                                </span>
                            </div>
                            
                            <div class="mb-2">
                                <strong>Data:</strong> ${Utils.formatDate(activity.data_vencimento, 'date')}
                            </div>
                            
                            ${activity.horario ? 
                                `<div class="mb-2">
                                    <strong>Horário:</strong> ${Utils.formatTime(activity.horario)}
                                </div>` : ''
                            }
                            
                            ${activity.duracao ? 
                                `<div class="mb-2">
                                    <strong>Duração:</strong> ${activity.duracao} minutos
                                </div>` : ''
                            }
                            
                            ${activity.local ? 
                                `<div class="mb-2">
                                    <strong>Local:</strong> ${Utils.escapeHtml(activity.local)}
                                </div>` : ''
                            }
                            
                            ${activity.contato ? 
                                `<div class="mb-2">
                                    <strong>Contato:</strong> ${Utils.escapeHtml(activity.contato.nome)}
                                </div>` : ''
                            }
                            
                            ${activity.oportunidade ? 
                                `<div class="mb-2">
                                    <strong>Oportunidade:</strong> ${Utils.escapeHtml(activity.oportunidade.titulo)}
                                </div>` : ''
                            }
                            
                            ${activity.responsavel ? 
                                `<div class="mb-2">
                                    <strong>Responsável:</strong> ${Utils.escapeHtml(activity.responsavel.nome)}
                                </div>` : ''
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('activityDetailsModal'));
        modal.show();
    },

    /**
     * Show edit modal from details modal
     */
    showEditModalFromDetails: function() {
        if (this.data.currentActivity) {
            bootstrap.Modal.getInstance(document.getElementById('activityDetailsModal')).hide();
            this.showEditModal(this.data.currentActivity.id);
        }
    },

    /**
     * Mark activity as completed
     */
    markAsCompleted: async function(activityId) {
        try {
            await API.activities.update(activityId, { status: 'concluida' });
            Utils.showToast('Atividade marcada como concluída', 'success');
            this.loadActivities(this.data.pagination.current);
            this.loadActivitiesStats();
        } catch (error) {
            console.error('Error marking activity as completed:', error);
            Utils.showToast('Erro ao marcar atividade como concluída', 'error');
        }
    },

    /**
     * Confirm activity deletion
     */
    confirmDelete: function(activityId) {
        const activity = this.data.activities.find(a => a.id === activityId);
        if (!activity) return;

        if (confirm(`Tem certeza que deseja excluir a atividade "${activity.titulo}"?`)) {
            this.deleteActivity(activityId);
        }
    },

    /**
     * Delete activity
     */
    deleteActivity: async function(activityId) {
        try {
            await API.activities.delete(activityId);
            Utils.showToast('Atividade excluída com sucesso', 'success');
            this.loadActivities(this.data.pagination.current);
            this.loadActivitiesStats();
        } catch (error) {
            console.error('Error deleting activity:', error);
            Utils.showToast('Erro ao excluir atividade', 'error');
        }
    }
};

// Export Activities globally
window.Activities = Activities;
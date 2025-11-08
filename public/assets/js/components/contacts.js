/**
 * CRM System Contacts Component
 * Manages contact listing, filtering, and CRUD operations
 */

const Contacts = {
    // Component data
    data: {
        table: null,
        contacts: [],
        currentContact: null,
        filters: {
            search: '',
            type: 'all',
            active: 'all'
        },
        pagination: {
            current: 1,
            total: 0,
            perPage: 25
        }
    },

    /**
     * Render the contacts page
     */
    render: function() {
        const pageContent = document.getElementById('pageContent');
        
        pageContent.innerHTML = `
            <div class="container-fluid p-4">
                <!-- Contacts Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-address-book me-2 text-primary"></i>Contatos
                        </h1>
                        <p class="text-muted mb-0">Gerenciar contatos e relacionamentos</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="Contacts.showCreateModal()">
                            <i class="fas fa-plus me-1"></i>Novo Contato
                        </button>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="row mb-4">
                    <div class="col-lg-6 col-md-8 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Buscar contatos..." 
                                   id="contactsSearch">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <select class="form-select" id="contactTypeFilter">
                            <option value="all">Todos os Tipos</option>
                            <option value="lead">Leads</option>
                            <option value="cliente">Clientes</option>
                            <option value="prospect">Prospects</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="contactStatusFilter">
                            <option value="all">Todos os Status</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <button class="btn btn-outline-secondary w-100" onclick="Contacts.clearFilters()">
                            <i class="fas fa-times me-1"></i>Limpar
                        </button>
                    </div>
                </div>

                <!-- Contacts Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="contactsTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllContacts">
                                            </div>
                                        </th>
                                        <th>Nome</th>
                                        <th>Empresa</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Última Atividade</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="contactsTableBody">
                                    ${this.renderTableSkeletons()}
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                <span id="contactsCount">Carregando...</span>
                            </div>
                            <nav id="contactsPagination">
                                <!-- Pagination will be rendered here -->
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Modal -->
            <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="contactModalTitle">Novo Contato</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="contactForm" novalidate>
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-6 mb-3">
                                        <label for="contactName" class="form-label">Nome *</label>
                                        <input type="text" class="form-control" id="contactName" required>
                                        <div class="invalid-feedback">Nome é obrigatório</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contactEmail" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="contactEmail" required>
                                        <div class="invalid-feedback">Email válido é obrigatório</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contactPhone" class="form-label">Telefone</label>
                                        <input type="tel" class="form-control" id="contactPhone">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contactWhatsapp" class="form-label">WhatsApp</label>
                                        <input type="tel" class="form-control" id="contactWhatsapp">
                                    </div>

                                    <!-- Company Information -->
                                    <div class="col-md-8 mb-3">
                                        <label for="contactCompany" class="form-label">Empresa</label>
                                        <input type="text" class="form-control" id="contactCompany">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="contactPosition" class="form-label">Cargo</label>
                                        <input type="text" class="form-control" id="contactPosition">
                                    </div>

                                    <!-- Classification -->
                                    <div class="col-md-4 mb-3">
                                        <label for="contactType" class="form-label">Tipo</label>
                                        <select class="form-select" id="contactType">
                                            <option value="lead">Lead</option>
                                            <option value="prospect">Prospect</option>
                                            <option value="cliente">Cliente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="contactStatus" class="form-label">Status</label>
                                        <select class="form-select" id="contactStatus">
                                            <option value="ativo">Ativo</option>
                                            <option value="inativo">Inativo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="contactSource" class="form-label">Origem</label>
                                        <select class="form-select" id="contactSource">
                                            <option value="">Selecionar...</option>
                                            <option value="website">Website</option>
                                            <option value="referencia">Referência</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="evento">Evento</option>
                                            <option value="telefone">Telefone</option>
                                            <option value="email">Email</option>
                                        </select>
                                    </div>

                                    <!-- Address -->
                                    <div class="col-12 mb-3">
                                        <label for="contactAddress" class="form-label">Endereço</label>
                                        <textarea class="form-control" id="contactAddress" rows="2"></textarea>
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-12 mb-3">
                                        <label for="contactNotes" class="form-label">Observações</label>
                                        <textarea class="form-control" id="contactNotes" rows="3"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="Contacts.saveContact()">
                                <i class="fas fa-save me-1"></i>Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Initialize table and load data
        this.initializeTable();
        this.setupEventListeners();
        this.loadContacts();
    },

    /**
     * Initialize DataTable
     */
    initializeTable: function() {
        // Configure DataTables if available
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            this.data.table = $('#contactsTable').DataTable({
                ...Config.dataTables.defaultConfig,
                order: [[1, 'asc']], // Sort by name
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
        const searchInput = document.getElementById('contactsSearch');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.data.filters.search = e.target.value;
                this.applyFilters();
            }, 300));
        }

        // Type filter
        const typeFilter = document.getElementById('contactTypeFilter');
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                this.data.filters.type = e.target.value;
                this.applyFilters();
            });
        }

        // Status filter
        const statusFilter = document.getElementById('contactStatusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.data.filters.active = e.target.value;
                this.applyFilters();
            });
        }

        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllContacts');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', this.toggleSelectAll.bind(this));
        }
    },

    /**
     * Load contacts from API
     */
    loadContacts: async function(page = 1) {
        try {
            const response = await API.contacts.list({
                page: page,
                per_page: this.data.pagination.perPage,
                search: this.data.filters.search,
                type: this.data.filters.type !== 'all' ? this.data.filters.type : undefined,
                active: this.data.filters.active !== 'all' ? this.data.filters.active : undefined
            });

            this.data.contacts = response.data.contacts || [];
            this.data.pagination = {
                current: response.data.current_page || 1,
                total: response.data.total || 0,
                perPage: response.data.per_page || 25
            };

            this.renderTable();
            this.renderPagination();

        } catch (error) {
            console.error('Error loading contacts:', error);
            Utils.showToast('Erro ao carregar contatos', 'error');
            
            // Show error state
            document.getElementById('contactsTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                        <p class="mb-0">Erro ao carregar contatos</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="Contacts.loadContacts()">
                            Tentar Novamente
                        </button>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Render table skeletons while loading
     */
    renderTableSkeletons: function() {
        return Array(10).fill(0).map(() => `
            <tr>
                <td><div class="skeleton skeleton-checkbox"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
                <td><div class="skeleton skeleton-text"></div></td>
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
     * Render contacts table
     */
    renderTable: function() {
        const tbody = document.getElementById('contactsTableBody');
        const countElement = document.getElementById('contactsCount');

        // Update count
        if (countElement) {
            countElement.textContent = `${this.data.contacts.length} de ${this.data.pagination.total} contatos`;
        }

        if (this.data.contacts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Nenhum contato encontrado</p>
                        ${this.data.filters.search || this.data.filters.type !== 'all' || this.data.filters.active !== 'all' ?
                            '<button class="btn btn-sm btn-outline-primary mt-2" onclick="Contacts.clearFilters()">Limpar Filtros</button>' :
                            '<button class="btn btn-sm btn-primary mt-2" onclick="Contacts.showCreateModal()">Criar Primeiro Contato</button>'
                        }
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.data.contacts.map(contact => `
            <tr class="contact-row" data-contact-id="${contact.id}">
                <td>
                    <div class="form-check">
                        <input class="form-check-input contact-checkbox" type="checkbox" value="${contact.id}">
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="contact-avatar me-2">
                            ${contact.avatar_url ? 
                                `<img src="${contact.avatar_url}" alt="${contact.nome}" class="rounded-circle" width="32" height="32">` :
                                `<div class="avatar-placeholder">${Utils.getInitials(contact.nome)}</div>`
                            }
                        </div>
                        <div>
                            <div class="fw-semibold">${Utils.escapeHtml(contact.nome)}</div>
                            ${contact.cargo ? `<small class="text-muted">${Utils.escapeHtml(contact.cargo)}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    ${contact.empresa ? Utils.escapeHtml(contact.empresa) : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <a href="mailto:${contact.email}" class="text-decoration-none">
                        ${Utils.escapeHtml(contact.email)}
                    </a>
                </td>
                <td>
                    ${contact.telefone ? 
                        `<a href="tel:${contact.telefone}" class="text-decoration-none">${Utils.formatPhone(contact.telefone)}</a>` :
                        '<span class="text-muted">-</span>'
                    }
                </td>
                <td>
                    <span class="badge bg-${this.getTypeBadgeColor(contact.tipo)} rounded-pill">
                        ${contact.tipo}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${contact.status === 'ativo' ? 'success' : 'secondary'} rounded-pill">
                        ${contact.status}
                    </span>
                </td>
                <td>
                    ${contact.ultima_atividade ? 
                        Utils.formatRelativeTime(contact.ultima_atividade) :
                        '<span class="text-muted">Nunca</span>'
                    }
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" 
                                onclick="Contacts.showDetails(${contact.id})" 
                                title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" 
                                onclick="Contacts.showEditModal(${contact.id})" 
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" 
                                onclick="Contacts.confirmDelete(${contact.id})" 
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    /**
     * Get badge color for contact type
     */
    getTypeBadgeColor: function(type) {
        const colors = {
            'lead': 'warning',
            'prospect': 'info',
            'cliente': 'success'
        };
        return colors[type] || 'secondary';
    },

    /**
     * Render pagination
     */
    renderPagination: function() {
        const paginationElement = document.getElementById('contactsPagination');
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
                <a class="page-link" href="#" onclick="Contacts.loadContacts(${current - 1})">Anterior</a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(totalPages, current + 2);

        if (startPage > 1) {
            paginationHTML += '<li class="page-item"><a class="page-link" href="#" onclick="Contacts.loadContacts(1)">1</a></li>';
            if (startPage > 2) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === current ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="Contacts.loadContacts(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="Contacts.loadContacts(${totalPages})">${totalPages}</a></li>`;
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${current === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="Contacts.loadContacts(${current + 1})">Próxima</a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationElement.innerHTML = paginationHTML;
    },

    /**
     * Apply current filters
     */
    applyFilters: function() {
        this.loadContacts(1);
    },

    /**
     * Clear all filters
     */
    clearFilters: function() {
        this.data.filters = {
            search: '',
            type: 'all',
            active: 'all'
        };

        // Reset form inputs
        document.getElementById('contactsSearch').value = '';
        document.getElementById('contactTypeFilter').value = 'all';
        document.getElementById('contactStatusFilter').value = 'all';

        this.loadContacts(1);
    },

    /**
     * Toggle select all contacts
     */
    toggleSelectAll: function(e) {
        const checkboxes = document.querySelectorAll('.contact-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    },

    /**
     * Show create contact modal
     */
    showCreateModal: function() {
        this.data.currentContact = null;
        document.getElementById('contactModalTitle').textContent = 'Novo Contato';
        document.getElementById('contactForm').reset();
        
        const modal = new bootstrap.Modal(document.getElementById('contactModal'));
        modal.show();
    },

    /**
     * Show edit contact modal
     */
    showEditModal: function(contactId) {
        const contact = this.data.contacts.find(c => c.id === contactId);
        if (!contact) return;

        this.data.currentContact = contact;
        document.getElementById('contactModalTitle').textContent = 'Editar Contato';

        // Fill form with contact data
        document.getElementById('contactName').value = contact.nome || '';
        document.getElementById('contactEmail').value = contact.email || '';
        document.getElementById('contactPhone').value = contact.telefone || '';
        document.getElementById('contactWhatsapp').value = contact.whatsapp || '';
        document.getElementById('contactCompany').value = contact.empresa || '';
        document.getElementById('contactPosition').value = contact.cargo || '';
        document.getElementById('contactType').value = contact.tipo || 'lead';
        document.getElementById('contactStatus').value = contact.status || 'ativo';
        document.getElementById('contactSource').value = contact.origem || '';
        document.getElementById('contactAddress').value = contact.endereco || '';
        document.getElementById('contactNotes').value = contact.observacoes || '';

        const modal = new bootstrap.Modal(document.getElementById('contactModal'));
        modal.show();
    },

    /**
     * Save contact (create or update)
     */
    saveContact: async function() {
        const form = document.getElementById('contactForm');
        
        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const contactData = {
            nome: document.getElementById('contactName').value,
            email: document.getElementById('contactEmail').value,
            telefone: document.getElementById('contactPhone').value,
            whatsapp: document.getElementById('contactWhatsapp').value,
            empresa: document.getElementById('contactCompany').value,
            cargo: document.getElementById('contactPosition').value,
            tipo: document.getElementById('contactType').value,
            status: document.getElementById('contactStatus').value,
            origem: document.getElementById('contactSource').value,
            endereco: document.getElementById('contactAddress').value,
            observacoes: document.getElementById('contactNotes').value
        };

        try {
            let response;
            
            if (this.data.currentContact) {
                // Update existing contact
                response = await API.contacts.update(this.data.currentContact.id, contactData);
                Utils.showToast('Contato atualizado com sucesso', 'success');
            } else {
                // Create new contact
                response = await API.contacts.create(contactData);
                Utils.showToast('Contato criado com sucesso', 'success');
            }

            // Close modal and refresh table
            bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
            this.loadContacts(this.data.pagination.current);

        } catch (error) {
            console.error('Error saving contact:', error);
            Utils.showToast('Erro ao salvar contato', 'error');
        }
    },

    /**
     * Show contact details (placeholder for future implementation)
     */
    showDetails: function(contactId) {
        Utils.showToast('Funcionalidade de detalhes em desenvolvimento', 'info');
    },

    /**
     * Confirm contact deletion
     */
    confirmDelete: function(contactId) {
        const contact = this.data.contacts.find(c => c.id === contactId);
        if (!contact) return;

        if (confirm(`Tem certeza que deseja excluir o contato "${contact.nome}"?`)) {
            this.deleteContact(contactId);
        }
    },

    /**
     * Delete contact
     */
    deleteContact: async function(contactId) {
        try {
            await API.contacts.delete(contactId);
            Utils.showToast('Contato excluído com sucesso', 'success');
            this.loadContacts(this.data.pagination.current);
        } catch (error) {
            console.error('Error deleting contact:', error);
            Utils.showToast('Erro ao excluir contato', 'error');
        }
    }
};

// Export Contacts globally
window.Contacts = Contacts;
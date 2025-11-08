/**
 * CRM System Main Application
 * Main application controller and routing
 */

const App = {
    // Current page information
    currentPage: null,
    
    // Page components registry
    components: {},

    /**
     * Initialize the application
     */
    init: function() {
        // Initialize authentication
        Auth.init();
        
        // Register page components
        this.registerComponents();
        
        // Setup global event listeners
        this.setupEventListeners();
        
        // Setup periodic updates
        this.setupPeriodicUpdates();
        
        // Check authentication and load initial page
        if (Auth.checkAuthentication()) {
            const initialPage = this.getInitialPage();
            this.loadPage(initialPage);
        }
        
        if (Config.debug) {
            console.log('CRM Application initialized');
        }
    },

    /**
     * Register page components
     */
    registerComponents: function() {
        // Register components when they become available
        if (typeof Dashboard !== 'undefined') {
            this.components.dashboard = Dashboard;
        }
        if (typeof Contacts !== 'undefined') {
            this.components.contacts = Contacts;
        }
        if (typeof Opportunities !== 'undefined') {
            this.components.opportunities = Opportunities;
        }
        if (typeof Activities !== 'undefined') {
            this.components.activities = Activities;
        }
    },

    /**
     * Setup global event listeners
     */
    setupEventListeners: function() {
        // Global search
        const searchForm = document.querySelector('form[role="search"]');
        if (searchForm) {
            searchForm.addEventListener('submit', this.handleGlobalSearch.bind(this));
        }
        
        // Handle navigation links
        document.addEventListener('click', (e) => {
            const navLink = e.target.closest('.nav-link[data-page]');
            if (navLink) {
                e.preventDefault();
                const page = navLink.dataset.page;
                this.loadPage(page);
            }
        });
        
        // Handle window resize for responsive updates
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 250);
        });
        
        // Handle online/offline status
        window.addEventListener('online', () => {
            Utils.showToast('Conexão restaurada', 'success');
        });
        
        window.addEventListener('offline', () => {
            Utils.showToast('Sem conexão com a internet', 'warning');
        });
    },

    /**
     * Setup periodic updates
     */
    setupPeriodicUpdates: function() {
        // Update notifications every 30 seconds
        setInterval(() => {
            if (Auth.isAuthenticated()) {
                this.updateNotifications();
            }
        }, Config.refresh.notifications);
        
        // Refresh current page data every 5 minutes
        setInterval(() => {
            if (Auth.isAuthenticated() && this.currentPage) {
                this.refreshCurrentPage();
            }
        }, Config.refresh.dashboard);
    },

    /**
     * Get initial page based on URL hash or default
     * @returns {string} Page name
     */
    getInitialPage: function() {
        const hash = window.location.hash.substring(1);
        const validPages = ['dashboard', 'contacts', 'opportunities', 'activities'];
        
        if (hash && validPages.includes(hash)) {
            return hash;
        }
        
        return 'dashboard';
    },

    /**
     * Load and display a page
     * @param {string} pageName - Name of the page to load
     * @param {boolean} addToHistory - Whether to add to browser history
     */
    loadPage: function(pageName, addToHistory = true) {
        if (!Auth.isAuthenticated()) {
            Auth.showLogin();
            return;
        }
        
        // Show loading state
        this.showPageLoading();
        
        // Update navigation
        this.updateNavigation(pageName);
        
        // Update URL hash
        if (addToHistory) {
            history.pushState({ page: pageName }, '', `#${pageName}`);
        }
        
        // Load page component
        this.currentPage = pageName;
        
        setTimeout(() => {
            try {
                if (this.components[pageName] && typeof this.components[pageName].render === 'function') {
                    this.components[pageName].render();
                } else {
                    this.showPageNotFound(pageName);
                }
            } catch (error) {
                console.error(`Error loading page ${pageName}:`, error);
                this.showPageError(error);
            }
        }, 100); // Small delay to show loading state
    },

    /**
     * Show page loading state
     */
    showPageLoading: function() {
        const pageContent = document.getElementById('pageContent');
        pageContent.innerHTML = `
            <div class="d-flex justify-content-center align-items-center initial-loading">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="text-muted">Carregando página...</p>
                </div>
            </div>
        `;
    },

    /**
     * Show page not found error
     * @param {string} pageName - Name of the page that wasn't found
     */
    showPageNotFound: function(pageName) {
        const pageContent = document.getElementById('pageContent');
        pageContent.innerHTML = `
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                        <h2>Página não encontrada</h2>
                        <p class="text-muted mb-4">A página "${pageName}" não foi encontrada ou ainda não foi implementada.</p>
                        <button class="btn btn-primary" onclick="App.loadPage('dashboard')">
                            <i class="fas fa-home me-2"></i>Voltar ao Dashboard
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Show page error
     * @param {Error} error - The error that occurred
     */
    showPageError: function(error) {
        const pageContent = document.getElementById('pageContent');
        pageContent.innerHTML = `
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="alert alert-danger" role="alert">
                            <h4 class="alert-heading">
                                <i class="fas fa-exclamation-circle me-2"></i>Erro ao carregar página
                            </h4>
                            <p class="mb-3">${Utils.escapeHtml(error.message || 'Ocorreu um erro inesperado.')}</p>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-danger" onclick="location.reload()">
                                    <i class="fas fa-redo me-2"></i>Recarregar Página
                                </button>
                                <button class="btn btn-primary" onclick="App.loadPage('dashboard')">
                                    <i class="fas fa-home me-2"></i>Voltar ao Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Update navigation active state
     * @param {string} pageName - Current page name
     */
    updateNavigation: function(pageName) {
        // Update desktop navigation
        document.querySelectorAll('.navbar-nav .nav-link[data-page]').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.page === pageName) {
                link.classList.add('active');
            }
        });
        
        // Update mobile navigation
        document.querySelectorAll('#mobileBottomNav .nav-link[data-page]').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.page === pageName) {
                link.classList.add('active');
            }
        });
    },

    /**
     * Handle global search form submission
     * @param {Event} event - Form submit event
     */
    handleGlobalSearch: function(event) {
        event.preventDefault();
        
        const searchInput = document.getElementById('globalSearch');
        const query = searchInput.value.trim();
        
        if (query.length < Config.search.minLength) {
            Utils.showToast(`Digite pelo menos ${Config.search.minLength} caracteres`, 'warning');
            return;
        }
        
        this.performGlobalSearch(query);
    },

    /**
     * Perform global search
     * @param {string} query - Search query
     */
    performGlobalSearch: async function(query) {
        try {
            const results = await API.search.global(query);
            this.showSearchResults(query, results.data);
        } catch (error) {
            console.error('Search error:', error);
            Utils.showToast('Erro ao realizar busca', 'error');
        }
    },

    /**
     * Show search results
     * @param {string} query - Search query
     * @param {object} results - Search results
     */
    showSearchResults: function(query, results) {
        const pageContent = document.getElementById('pageContent');
        
        let resultHtml = `
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-search me-2"></i>Resultados da Busca</h2>
                        <p class="text-muted mb-0">Busca por: "${Utils.escapeHtml(query)}"</p>
                    </div>
                    <button class="btn btn-secondary" onclick="history.back()">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </button>
                </div>
                
                <div class="row">
        `;
        
        // Show results for each category
        const categories = [
            { key: 'contacts', title: 'Contatos', icon: 'fas fa-users' },
            { key: 'companies', title: 'Empresas', icon: 'fas fa-building' },
            { key: 'opportunities', title: 'Oportunidades', icon: 'fas fa-bullseye' },
            { key: 'activities', title: 'Atividades', icon: 'fas fa-tasks' }
        ];
        
        categories.forEach(category => {
            const items = results[category.key] || [];
            
            resultHtml += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="${category.icon} me-2"></i>${category.title}
                                <span class="badge bg-primary ms-2">${items.length}</span>
                            </h5>
                        </div>
                        <div class="card-body">
            `;
            
            if (items.length === 0) {
                resultHtml += '<p class="text-muted">Nenhum resultado encontrado</p>';
            } else {
                items.forEach(item => {
                    resultHtml += this.renderSearchResultItem(item, category.key);
                });
            }
            
            resultHtml += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        resultHtml += `
                </div>
            </div>
        `;
        
        pageContent.innerHTML = resultHtml;
    },

    /**
     * Render a search result item
     * @param {object} item - Search result item
     * @param {string} type - Item type
     * @returns {string} HTML for the item
     */
    renderSearchResultItem: function(item, type) {
        const onClick = `App.loadPage('${type}'); ${type === 'contacts' ? 'Contacts' : type === 'companies' ? 'Companies' : type === 'opportunities' ? 'Opportunities' : 'Activities'}.showDetails(${item.id})`;
        
        return `
            <div class="border-bottom pb-2 mb-2 cursor-pointer" onclick="${onClick}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${Utils.escapeHtml(item.nome || item.titulo || item.name || 'Item')}</h6>
                        ${item.email ? `<small class="text-muted d-block">${Utils.escapeHtml(item.email)}</small>` : ''}
                        ${item.company ? `<small class="text-muted d-block">${Utils.escapeHtml(item.company.nome)}</small>` : ''}
                    </div>
                    ${item.valor ? `<span class="text-success fw-bold">${Utils.formatCurrency(item.valor)}</span>` : ''}
                </div>
            </div>
        `;
    },

    /**
     * Update notifications
     */
    updateNotifications: async function() {
        try {
            const response = await API.dashboard.notifications();
            this.renderNotifications(response.data);
        } catch (error) {
            if (Config.debug) {
                console.error('Error updating notifications:', error);
            }
        }
    },

    /**
     * Render notifications in dropdown
     * @param {Array} notifications - Array of notifications
     */
    renderNotifications: function(notifications) {
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        
        if (!notificationList) return;
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<li><p class="dropdown-item-text text-muted text-center py-3">Nenhuma notificação</p></li>';
            notificationBadge.classList.add('d-none');
        } else {
            notificationBadge.textContent = notifications.length;
            notificationBadge.classList.remove('d-none');
            
            notificationList.innerHTML = notifications.map(notification => `
                <li>
                    <a class="dropdown-item" href="#" onclick="App.handleNotificationClick(${notification.id})">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="${notification.icon || 'fas fa-bell'} text-${notification.type || 'primary'}"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <div class="fw-bold">${Utils.escapeHtml(notification.title)}</div>
                                <div class="small text-muted">${Utils.escapeHtml(notification.message)}</div>
                                <div class="small text-muted">${Utils.formatRelativeTime(notification.created_at)}</div>
                            </div>
                        </div>
                    </a>
                </li>
            `).join('');
        }
    },

    /**
     * Handle notification click
     * @param {number} notificationId - Notification ID
     */
    handleNotificationClick: function(notificationId) {
        // Mark notification as read and navigate to related item
        // Implementation depends on notification structure
        console.log('Notification clicked:', notificationId);
    },

    /**
     * Handle window resize
     */
    handleResize: function() {
        // Refresh current page component if it has a resize handler
        if (this.currentPage && this.components[this.currentPage] && 
            typeof this.components[this.currentPage].handleResize === 'function') {
            this.components[this.currentPage].handleResize();
        }
        
        // Update DataTables if present
        if (typeof $.fn.DataTable !== 'undefined') {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    },

    /**
     * Refresh current page data
     */
    refreshCurrentPage: function() {
        if (this.currentPage && this.components[this.currentPage] && 
            typeof this.components[this.currentPage].refresh === 'function') {
            this.components[this.currentPage].refresh();
        }
    }
};

// Global navigation function (called from HTML)
function navigateTo(page) {
    App.loadPage(page);
}

// Global search function (called from HTML)
function performGlobalSearch(event) {
    event.preventDefault();
    const searchInput = document.getElementById('globalSearch');
    const query = searchInput.value.trim();
    
    if (query.length >= Config.search.minLength) {
        App.performGlobalSearch(query);
    }
    
    return false;
}

// Global functions for header actions
function showProfile() {
    Utils.showToast('Perfil do usuário ainda não implementado', 'info');
}

function showSettings() {
    Utils.showToast('Configurações ainda não implementadas', 'info');
}

function logout() {
    Utils.showConfirmDialog(
        'Confirmar Logout',
        'Tem certeza que deseja sair do sistema?',
        (confirmed) => {
            if (confirmed) {
                Auth.logout();
            }
        }
    );
}

function markAllNotificationsRead() {
    Utils.showToast('Todas as notificações foram marcadas como lidas', 'success');
    App.renderNotifications([]);
}

// Export App globally
window.App = App;
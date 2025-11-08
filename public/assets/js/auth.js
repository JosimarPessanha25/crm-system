/**
 * CRM System Authentication Module
 * Handles user authentication, token management, and auth state
 */

const Auth = {
    // Current user data
    currentUser: null,

    /**
     * Initialize authentication system
     */
    init: function() {
        this.loadUserFromStorage();
        this.setupTokenRefresh();
    },

    /**
     * Load user data from localStorage
     */
    loadUserFromStorage: function() {
        const userStr = localStorage.getItem(Config.auth.userKey);
        if (userStr) {
            try {
                this.currentUser = JSON.parse(userStr);
                this.updateUI();
            } catch (error) {
                console.error('Error parsing stored user data:', error);
                this.clearStorage();
            }
        }
    },

    /**
     * Check if user is authenticated
     * @returns {boolean} True if user is authenticated
     */
    isAuthenticated: function() {
        const token = localStorage.getItem(Config.auth.tokenKey);
        return !!(token && this.currentUser);
    },

    /**
     * Check token expiration and refresh if needed
     */
    checkAuthentication: function() {
        if (!this.isAuthenticated()) {
            this.showLogin();
            return false;
        }

        // Check token expiration
        const token = localStorage.getItem(Config.auth.tokenKey);
        if (this.isTokenExpired(token)) {
            this.refreshToken();
        }

        return true;
    },

    /**
     * Check if token is expired or about to expire
     * @param {string} token - JWT token
     * @returns {boolean} True if token is expired or about to expire
     */
    isTokenExpired: function(token) {
        if (!token) return true;

        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const now = Date.now();
            const expiration = payload.exp * 1000;
            
            // Check if token expires within the buffer time
            return (expiration - now) < Config.auth.tokenExpirationBuffer;
        } catch (error) {
            console.error('Error parsing token:', error);
            return true;
        }
    },

    /**
     * Login user with credentials
     * @param {object} credentials - Login credentials
     * @returns {Promise} Login promise
     */
    login: async function(credentials) {
        try {
            const response = await API.auth.login(credentials);
            
            if (response.success) {
                this.setAuthData(response.data);
                Utils.showToast('Login realizado com sucesso!', 'success');
                App.loadPage('dashboard');
                return true;
            } else {
                throw new Error(response.message || 'Erro no login');
            }
        } catch (error) {
            Utils.showToast(error.message || 'Erro ao fazer login', 'error');
            throw error;
        }
    },

    /**
     * Logout user
     */
    logout: async function() {
        try {
            // Call logout API if authenticated
            if (this.isAuthenticated()) {
                await API.auth.logout();
            }
        } catch (error) {
            console.error('Logout API error:', error);
        } finally {
            this.clearStorage();
            this.showLogin();
            Utils.showToast('Logout realizado com sucesso', 'info');
        }
    },

    /**
     * Refresh authentication token
     */
    refreshToken: async function() {
        const refreshToken = localStorage.getItem(Config.auth.refreshTokenKey);
        
        if (!refreshToken) {
            this.handleAuthError();
            return;
        }

        try {
            const response = await API.auth.refresh(refreshToken);
            
            if (response.success) {
                this.setAuthData(response.data);
            } else {
                throw new Error('Token refresh failed');
            }
        } catch (error) {
            console.error('Token refresh error:', error);
            this.handleAuthError();
        }
    },

    /**
     * Handle authentication errors
     */
    handleAuthError: function() {
        this.clearStorage();
        this.showLogin();
        Utils.showToast('Sessão expirada. Faça login novamente.', 'warning');
    },

    /**
     * Set authentication data in storage
     * @param {object} authData - Authentication data from API
     */
    setAuthData: function(authData) {
        const { user, token, refresh_token } = authData;
        
        this.currentUser = user;
        localStorage.setItem(Config.auth.tokenKey, token);
        localStorage.setItem(Config.auth.userKey, JSON.stringify(user));
        
        if (refresh_token) {
            localStorage.setItem(Config.auth.refreshTokenKey, refresh_token);
        }
        
        this.updateUI();
    },

    /**
     * Clear all authentication data
     */
    clearStorage: function() {
        this.currentUser = null;
        localStorage.removeItem(Config.auth.tokenKey);
        localStorage.removeItem(Config.auth.userKey);
        localStorage.removeItem(Config.auth.refreshTokenKey);
    },

    /**
     * Update UI with current user data
     */
    updateUI: function() {
        if (this.currentUser) {
            const userNameElement = document.getElementById('currentUserName');
            if (userNameElement) {
                userNameElement.textContent = this.currentUser.nome || 'Usuário';
            }
        }
    },

    /**
     * Setup automatic token refresh
     */
    setupTokenRefresh: function() {
        // Check token every 5 minutes
        setInterval(() => {
            if (this.isAuthenticated()) {
                const token = localStorage.getItem(Config.auth.tokenKey);
                if (this.isTokenExpired(token)) {
                    this.refreshToken();
                }
            }
        }, 300000); // 5 minutes
    },

    /**
     * Show login form
     */
    showLogin: function() {
        const loginHtml = `
            <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
                <div class="row w-100">
                    <div class="col-md-6 col-lg-4 mx-auto">
                        <div class="card shadow">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                    <h1 class="h3 mb-1">CRM System</h1>
                                    <p class="text-muted">Faça login para continuar</p>
                                </div>
                                
                                <form id="loginForm" onsubmit="return Auth.handleLoginSubmit(event)">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               required 
                                               autocomplete="email"
                                               placeholder="seu@email.com">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               required 
                                               autocomplete="current-password"
                                               placeholder="Sua senha">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                                        <label class="form-check-label" for="rememberMe">
                                            Lembrar de mim
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary" id="loginButton">
                                            <span class="login-text">Entrar</span>
                                            <span class="login-loading d-none">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Entrando...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="text-center mt-3">
                                    <a href="#" class="text-muted small" onclick="Auth.showForgotPassword()">
                                        Esqueceu sua senha?
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                © 2024 CRM System. Todos os direitos reservados.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('pageContent').innerHTML = loginHtml;
        
        // Focus on first input
        setTimeout(() => {
            document.getElementById('email').focus();
        }, 100);
    },

    /**
     * Handle login form submission
     * @param {Event} event - Form submit event
     */
    handleLoginSubmit: function(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const credentials = {
            email: formData.get('email'),
            password: formData.get('password'),
            remember: formData.get('remember') ? true : false
        };
        
        // Reset form validation
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
        });
        
        // Show loading state
        this.setLoginLoading(true);
        
        // Perform login
        this.login(credentials).catch(error => {
            this.setLoginLoading(false);
            
            // Show validation errors if they exist
            if (error.data && error.data.errors) {
                this.showValidationErrors(form, error.data.errors);
            }
        });
        
        return false;
    },

    /**
     * Set login button loading state
     * @param {boolean} loading - Loading state
     */
    setLoginLoading: function(loading) {
        const button = document.getElementById('loginButton');
        const text = button.querySelector('.login-text');
        const loadingSpinner = button.querySelector('.login-loading');
        
        if (loading) {
            button.disabled = true;
            text.classList.add('d-none');
            loadingSpinner.classList.remove('d-none');
        } else {
            button.disabled = false;
            text.classList.remove('d-none');
            loadingSpinner.classList.add('d-none');
        }
    },

    /**
     * Show validation errors on form
     * @param {HTMLElement} form - Form element
     * @param {object} errors - Validation errors
     */
    showValidationErrors: function(form, errors) {
        for (const [field, messages] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = messages[0];
                }
            }
        }
        
        Utils.focusFirstError();
    },

    /**
     * Show forgot password form
     */
    showForgotPassword: function() {
        Utils.showToast('Funcionalidade de recuperação de senha ainda não implementada', 'info');
    },

    /**
     * Get current user data
     * @returns {object|null} Current user data or null
     */
    getCurrentUser: function() {
        return this.currentUser;
    },

    /**
     * Check if user has specific permission
     * @param {string} permission - Permission to check
     * @returns {boolean} True if user has permission
     */
    hasPermission: function(permission) {
        if (!this.currentUser) return false;
        
        // Admin users have all permissions
        if (this.currentUser.perfil === 'admin') return true;
        
        // Add your permission logic here based on user roles/permissions
        return true;
    }
};

// Export Auth globally
window.Auth = Auth;
/**
 * CRM System API Client
 * Handles all HTTP requests to the backend API
 */

const API = {
    /**
     * Make HTTP request with proper error handling and authentication
     * @param {string} endpoint - API endpoint
     * @param {object} options - Request options
     * @returns {Promise} Request promise
     */
    request: async function(endpoint, options = {}) {
        const url = Config.api.baseUrl + endpoint;
        const token = localStorage.getItem(Config.auth.tokenKey);
        
        // Default headers
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };
        
        // Add authorization header if token exists
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Request configuration
        const config = {
            method: options.method || 'GET',
            headers: headers,
            ...options
        };
        
        // Add body for non-GET requests
        if (config.method !== 'GET' && options.body) {
            if (config.headers['Content-Type'] === 'application/json') {
                config.body = JSON.stringify(options.body);
            } else {
                config.body = options.body;
            }
        }
        
        try {
            const response = await fetch(url, config);
            
            // Handle authentication errors
            if (response.status === 401) {
                Auth.handleAuthError();
                throw new Error('Não autorizado. Faça login novamente.');
            }
            
            // Parse JSON response
            const data = await response.json();
            
            // Handle API errors
            if (!response.ok) {
                const error = new Error(data.message || `HTTP Error: ${response.status}`);
                error.status = response.status;
                error.data = data;
                throw error;
            }
            
            return data;
        } catch (error) {
            if (Config.debug) {
                console.error('API Request Error:', error);
            }
            
            // Handle network errors
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Erro de conexão. Verifique sua internet.');
            }
            
            throw error;
        }
    },

    /**
     * GET request
     * @param {string} endpoint - API endpoint
     * @param {object} params - Query parameters
     * @returns {Promise} Request promise
     */
    get: function(endpoint, params = {}) {
        const queryString = Utils.objectToQueryString(params);
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, { method: 'GET' });
    },

    /**
     * POST request
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request body data
     * @returns {Promise} Request promise
     */
    post: function(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: data
        });
    },

    /**
     * PUT request
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request body data
     * @returns {Promise} Request promise
     */
    put: function(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: data
        });
    },

    /**
     * DELETE request
     * @param {string} endpoint - API endpoint
     * @returns {Promise} Request promise
     */
    delete: function(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },

    /**
     * Upload file
     * @param {string} endpoint - API endpoint
     * @param {FormData} formData - Form data with file
     * @returns {Promise} Request promise
     */
    upload: function(endpoint, formData) {
        return this.request(endpoint, {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set Content-Type for FormData
        });
    },

    // Authentication endpoints
    auth: {
        login: function(credentials) {
            return API.post('/auth/login', credentials);
        },
        
        logout: function() {
            return API.post('/auth/logout');
        },
        
        refresh: function(refreshToken) {
            return API.post('/auth/refresh', { refresh_token: refreshToken });
        },
        
        forgotPassword: function(email) {
            return API.post('/auth/forgot-password', { email });
        },
        
        resetPassword: function(token, password, passwordConfirmation) {
            return API.post('/auth/reset-password', {
                token,
                password,
                password_confirmation: passwordConfirmation
            });
        }
    },

    // User endpoints
    users: {
        list: function(params = {}) {
            return API.get('/users', params);
        },
        
        get: function(id) {
            return API.get(`/users/${id}`);
        },
        
        create: function(data) {
            return API.post('/users', data);
        },
        
        update: function(id, data) {
            return API.put(`/users/${id}`, data);
        },
        
        delete: function(id) {
            return API.delete(`/users/${id}`);
        },
        
        restore: function(id) {
            return API.post(`/users/${id}/restore`);
        },
        
        stats: function() {
            return API.get('/users/stats');
        }
    },

    // Company endpoints
    companies: {
        list: function(params = {}) {
            return API.get('/companies', params);
        },
        
        get: function(id) {
            return API.get(`/companies/${id}`);
        },
        
        create: function(data) {
            return API.post('/companies', data);
        },
        
        update: function(id, data) {
            return API.put(`/companies/${id}`, data);
        },
        
        delete: function(id) {
            return API.delete(`/companies/${id}`);
        },
        
        contacts: function(id, params = {}) {
            return API.get(`/companies/${id}/contacts`, params);
        },
        
        opportunities: function(id, params = {}) {
            return API.get(`/companies/${id}/opportunities`, params);
        },
        
        stats: function(id) {
            return API.get(`/companies/${id}/stats`);
        }
    },

    // Contact endpoints
    contacts: {
        list: function(params = {}) {
            return API.get('/contacts', params);
        },
        
        get: function(id) {
            return API.get(`/contacts/${id}`);
        },
        
        create: function(data) {
            return API.post('/contacts', data);
        },
        
        update: function(id, data) {
            return API.put(`/contacts/${id}`, data);
        },
        
        delete: function(id) {
            return API.delete(`/contacts/${id}`);
        },
        
        timeline: function(id, params = {}) {
            return API.get(`/contacts/${id}/timeline`, params);
        },
        
        opportunities: function(id, params = {}) {
            return API.get(`/contacts/${id}/opportunities`, params);
        },
        
        updateScore: function(id, score) {
            return API.put(`/contacts/${id}/score`, { score });
        },
        
        addTags: function(id, tags) {
            return API.post(`/contacts/${id}/tags`, { tags });
        },
        
        removeTags: function(id, tags) {
            return API.delete(`/contacts/${id}/tags`, { tags });
        }
    },

    // Opportunity endpoints
    opportunities: {
        list: function(params = {}) {
            return API.get('/opportunities', params);
        },
        
        get: function(id) {
            return API.get(`/opportunities/${id}`);
        },
        
        create: function(data) {
            return API.post('/opportunities', data);
        },
        
        update: function(id, data) {
            return API.put(`/opportunities/${id}`, data);
        },
        
        delete: function(id) {
            return API.delete(`/opportunities/${id}`);
        },
        
        pipeline: function(params = {}) {
            return API.get('/opportunities/pipeline', params);
        },
        
        moveStage: function(id, stage, notes = null) {
            return API.put(`/opportunities/${id}/move-stage`, { stage, notes });
        },
        
        close: function(id, won, notes = null, finalValue = null) {
            return API.put(`/opportunities/${id}/close`, { won, notes, final_value: finalValue });
        },
        
        activities: function(id, params = {}) {
            return API.get(`/opportunities/${id}/activities`, params);
        },
        
        stats: function() {
            return API.get('/opportunities/stats');
        }
    },

    // Activity endpoints
    activities: {
        list: function(params = {}) {
            return API.get('/activities', params);
        },
        
        get: function(id) {
            return API.get(`/activities/${id}`);
        },
        
        create: function(data) {
            return API.post('/activities', data);
        },
        
        update: function(id, data) {
            return API.put(`/activities/${id}`, data);
        },
        
        delete: function(id) {
            return API.delete(`/activities/${id}`);
        },
        
        calendar: function(startDate, endDate, userId = null) {
            const params = { start_date: startDate, end_date: endDate };
            if (userId) params.user_id = userId;
            return API.get('/activities/calendar', params);
        },
        
        upcoming: function(days = 7, userId = null) {
            const params = { days };
            if (userId) params.user_id = userId;
            return API.get('/activities/upcoming', params);
        },
        
        complete: function(id, notes = null) {
            return API.put(`/activities/${id}/complete`, { notes });
        },
        
        reschedule: function(id, newDateTime, reason = null) {
            return API.put(`/activities/${id}/reschedule`, { 
                new_date_time: newDateTime, 
                reason 
            });
        },
        
        stats: function(userId = null) {
            const params = userId ? { user_id: userId } : {};
            return API.get('/activities/stats', params);
        }
    },

    // Dashboard endpoints
    dashboard: {
        stats: function() {
            return API.get('/dashboard/stats');
        },
        
        recentActivities: function(limit = 10) {
            return API.get('/dashboard/recent-activities', { limit });
        },
        
        notifications: function() {
            return API.get('/dashboard/notifications');
        }
    },

    // Search endpoints
    search: {
        global: function(query, limit = 10) {
            return API.get('/search', { query, limit });
        },
        
        contacts: function(query, limit = 10) {
            return API.get('/search/contacts', { query, limit });
        },
        
        companies: function(query, limit = 10) {
            return API.get('/search/companies', { query, limit });
        },
        
        opportunities: function(query, limit = 10) {
            return API.get('/search/opportunities', { query, limit });
        }
    }
};

// Export API globally
window.API = API;
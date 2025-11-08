/**
 * CRM System Utilities
 * Common utility functions used throughout the application
 */

const Utils = {
    /**
     * Format currency values
     * @param {number} value - The numeric value to format
     * @param {string} currency - Currency code (default: BRL)
     * @returns {string} Formatted currency string
     */
    formatCurrency: function(value, currency = 'BRL') {
        if (value === null || value === undefined || isNaN(value)) {
            return 'R$ 0,00';
        }
        
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: currency
        }).format(value);
    },

    /**
     * Format money values (alias for formatCurrency)
     * @param {number} value - The numeric value to format
     * @returns {string} Formatted money string
     */
    formatMoney: function(value) {
        return this.formatCurrency(value);
    },

    /**
     * Format numbers with locale-specific formatting
     * @param {number} value - The numeric value to format
     * @param {number} decimals - Number of decimal places
     * @returns {string} Formatted number string
     */
    formatNumber: function(value, decimals = 0) {
        if (value === null || value === undefined || isNaN(value)) {
            return '0';
        }
        
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    },

    /**
     * Format date values
     * @param {string|Date} date - The date to format
     * @param {string} format - Format type ('date', 'datetime', 'time')
     * @returns {string} Formatted date string
     */
    formatDate: function(date, format = 'date') {
        if (!date) return '';
        
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        if (isNaN(dateObj.getTime())) return '';
        
        const options = {
            date: { day: '2-digit', month: '2-digit', year: 'numeric' },
            datetime: { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            },
            time: { hour: '2-digit', minute: '2-digit' }
        };
        
        return new Intl.DateTimeFormat('pt-BR', options[format] || options.date).format(dateObj);
    },

    /**
     * Format relative time (e.g., "há 2 horas", "em 1 dia")
     * @param {string|Date} date - The date to compare
     * @returns {string} Relative time string
     */
    formatRelativeTime: function(date) {
        if (!date) return '';
        
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        if (isNaN(dateObj.getTime())) return '';
        
        const now = new Date();
        const diffMs = now.getTime() - dateObj.getTime();
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (Math.abs(diffMinutes) < 1) return 'agora mesmo';
        if (Math.abs(diffMinutes) < 60) {
            return diffMinutes > 0 ? `há ${diffMinutes} minuto${diffMinutes !== 1 ? 's' : ''}` 
                                  : `em ${Math.abs(diffMinutes)} minuto${Math.abs(diffMinutes) !== 1 ? 's' : ''}`;
        }
        if (Math.abs(diffHours) < 24) {
            return diffHours > 0 ? `há ${diffHours} hora${diffHours !== 1 ? 's' : ''}` 
                                : `em ${Math.abs(diffHours)} hora${Math.abs(diffHours) !== 1 ? 's' : ''}`;
        }
        if (Math.abs(diffDays) < 30) {
            return diffDays > 0 ? `há ${diffDays} dia${diffDays !== 1 ? 's' : ''}` 
                               : `em ${Math.abs(diffDays)} dia${Math.abs(diffDays) !== 1 ? 's' : ''}`;
        }
        
        return this.formatDate(dateObj);
    },

    /**
     * Validate email format
     * @param {string} email - Email to validate
     * @returns {boolean} True if valid email format
     */
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Validate phone number (Brazilian format)
     * @param {string} phone - Phone number to validate
     * @returns {boolean} True if valid phone format
     */
    validatePhone: function(phone) {
        const cleanPhone = phone.replace(/\D/g, '');
        return cleanPhone.length >= 10 && cleanPhone.length <= 11;
    },

    /**
     * Validate CNPJ (Brazilian company ID)
     * @param {string} cnpj - CNPJ to validate
     * @returns {boolean} True if valid CNPJ
     */
    validateCNPJ: function(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return false;
        }
        
        // Validate check digits
        let sum = 0;
        let weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for (let i = 0; i < 12; i++) {
            sum += parseInt(cnpj[i]) * weights[i];
        }
        
        let remainder = sum % 11;
        let checkDigit1 = remainder < 2 ? 0 : 11 - remainder;
        
        if (parseInt(cnpj[12]) !== checkDigit1) {
            return false;
        }
        
        sum = 0;
        weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for (let i = 0; i < 13; i++) {
            sum += parseInt(cnpj[i]) * weights[i];
        }
        
        remainder = sum % 11;
        let checkDigit2 = remainder < 2 ? 0 : 11 - remainder;
        
        return parseInt(cnpj[13]) === checkDigit2;
    },

    /**
     * Format phone number for display
     * @param {string} phone - Raw phone number
     * @returns {string} Formatted phone number
     */
    formatPhone: function(phone) {
        if (!phone) return '';
        
        const cleanPhone = phone.replace(/\D/g, '');
        
        if (cleanPhone.length === 10) {
            return cleanPhone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else if (cleanPhone.length === 11) {
            return cleanPhone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        
        return phone;
    },

    /**
     * Format CNPJ for display
     * @param {string} cnpj - Raw CNPJ
     * @returns {string} Formatted CNPJ
     */
    formatCNPJ: function(cnpj) {
        if (!cnpj) return '';
        
        const cleanCNPJ = cnpj.replace(/\D/g, '');
        
        if (cleanCNPJ.length === 14) {
            return cleanCNPJ.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        
        return cnpj;
    },

    /**
     * Generate a random ID
     * @param {number} length - Length of the ID
     * @returns {string} Random ID string
     */
    generateId: function(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    /**
     * Debounce function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function calls
     * @param {Function} func - Function to throttle
     * @param {number} limit - Time limit in milliseconds
     * @returns {Function} Throttled function
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped HTML
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Strip HTML tags from string
     * @param {string} html - HTML string
     * @returns {string} Plain text
     */
    stripHtml: function(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    },

    /**
     * Truncate text to specified length
     * @param {string} text - Text to truncate
     * @param {number} length - Maximum length
     * @param {string} suffix - Suffix to add (default: '...')
     * @returns {string} Truncated text
     */
    truncate: function(text, length, suffix = '...') {
        if (!text || text.length <= length) return text || '';
        return text.substring(0, length) + suffix;
    },

    /**
     * Get contact score class and color
     * @param {number} score - Contact score (0-100)
     * @returns {object} Score info with class and color
     */
    getContactScoreInfo: function(score) {
        const { contactScores } = Config.constants;
        
        for (const [key, config] of Object.entries(contactScores)) {
            if (score >= config.min && score <= config.max) {
                return {
                    class: config.class,
                    color: config.color,
                    label: key.charAt(0).toUpperCase() + key.slice(1)
                };
            }
        }
        
        return contactScores.low;
    },

    /**
     * Get status badge HTML
     * @param {string} status - Status value
     * @param {string} type - Status type (opportunity, activity, etc.)
     * @returns {string} HTML for status badge
     */
    getStatusBadge: function(status, type) {
        const statusConfig = Config.constants.statuses[type]?.[status];
        if (!statusConfig) {
            return `<span class="status-badge">${status}</span>`;
        }
        
        return `<span class="status-badge ${statusConfig.class}">${statusConfig.label}</span>`;
    },

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {number} duration - Display duration in milliseconds
     */
    showToast: function(message, type = 'info', duration = 5000) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;
        
        const toastId = this.generateId();
        const iconClasses = {
            success: 'fas fa-check-circle text-success',
            error: 'fas fa-exclamation-circle text-danger',
            warning: 'fas fa-exclamation-triangle text-warning',
            info: 'fas fa-info-circle text-info'
        };
        
        const toastHtml = `
            <div id="toast-${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="${iconClasses[type] || iconClasses.info} me-2"></i>
                    <strong class="me-auto">CRM System</strong>
                    <small>agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${this.escapeHtml(message)}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(`toast-${toastId}`);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: duration });
        toast.show();
        
        // Remove toast from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    },

    /**
     * Show confirmation dialog
     * @param {string} title - Dialog title
     * @param {string} message - Dialog message
     * @param {Function} callback - Callback function with boolean result
     */
    showConfirmDialog: function(title, message, callback) {
        const modalId = `confirmModal-${this.generateId()}`;
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">${this.escapeHtml(title)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${this.escapeHtml(message)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="${modalId}Confirm">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        const confirmBtn = document.getElementById(`${modalId}Confirm`);
        
        confirmBtn.addEventListener('click', function() {
            callback(true);
            modal.hide();
        });
        
        document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
            document.getElementById(modalId).remove();
        });
        
        modal.show();
    },

    /**
     * Convert object to query string
     * @param {object} params - Parameters object
     * @returns {string} Query string
     */
    objectToQueryString: function(params) {
        return Object.keys(params)
            .filter(key => params[key] !== null && params[key] !== undefined && params[key] !== '')
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
            .join('&');
    },

    /**
     * Parse query string to object
     * @param {string} queryString - Query string
     * @returns {object} Parameters object
     */
    queryStringToObject: function(queryString) {
        const params = {};
        const urlParams = new URLSearchParams(queryString);
        
        for (const [key, value] of urlParams.entries()) {
            params[key] = value;
        }
        
        return params;
    },

    /**
     * Deep clone an object
     * @param {object} obj - Object to clone
     * @returns {object} Cloned object
     */
    deepClone: function(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj.getTime());
        if (obj instanceof Array) return obj.map(item => this.deepClone(item));
        if (typeof obj === 'object') {
            const clonedObj = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    clonedObj[key] = this.deepClone(obj[key]);
                }
            }
            return clonedObj;
        }
    },

    /**
     * Check if device is mobile
     * @returns {boolean} True if mobile device
     */
    isMobile: function() {
        return window.innerWidth < Config.breakpoints.md;
    },

    /**
     * Scroll to top of page smoothly
     */
    scrollToTop: function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    /**
     * Focus on first form field with error
     */
    focusFirstError: function() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

// Export Utils globally
window.Utils = Utils;
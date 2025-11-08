/**
 * CRM System Configuration
 * Application-wide configuration and constants
 */

const Config = {
    // API Configuration
    api: {
        baseUrl: window.location.protocol + '//' + window.location.host + '/api',
        timeout: 30000,
        retryAttempts: 3,
        retryDelay: 1000
    },

    // Authentication
    auth: {
        tokenKey: 'crm_token',
        userKey: 'crm_user',
        refreshTokenKey: 'crm_refresh_token',
        tokenExpirationBuffer: 300000 // 5 minutes in milliseconds
    },

    // Pagination
    pagination: {
        defaultPageSize: 20,
        pageSizeOptions: [10, 20, 50, 100]
    },

    // Date/Time formats
    dateFormat: {
        display: 'dd/MM/yyyy',
        displayDateTime: 'dd/MM/yyyy HH:mm',
        api: 'yyyy-MM-dd',
        apiDateTime: 'yyyy-MM-dd HH:mm:ss'
    },

    // Toast notification settings
    toast: {
        defaultDuration: 5000,
        positions: {
            topRight: 'top-0 end-0',
            topLeft: 'top-0 start-0',
            bottomRight: 'bottom-0 end-0',
            bottomLeft: 'bottom-0 start-0'
        }
    },

    // DataTables default configuration
    dataTable: {
        language: {
            "sProcessing": "Processando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "Nenhum registro encontrado",
            "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
            "sInfoFiltered": "(Filtrados de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ".",
            "sLoadingRecords": "Carregando...",
            "oPaginate": {
                "sFirst": "Primeiro",
                "sLast": "Último",
                "sNext": "Seguinte",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Ordenar colunas de forma ascendente",
                "sSortDescending": ": Ordenar colunas de forma descendente"
            }
        },
        pageLength: 20,
        responsive: true,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: -1 } // Disable ordering on last column (actions)
        ]
    },

    // Chart.js default configuration
    chart: {
        defaultOptions: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        },
        colors: {
            primary: '#2563eb',
            secondary: '#64748b',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#06b6d4'
        }
    },

    // Form validation messages
    validation: {
        messages: {
            required: 'Este campo é obrigatório',
            email: 'Digite um email válido',
            phone: 'Digite um telefone válido',
            cnpj: 'Digite um CNPJ válido',
            cpf: 'Digite um CPF válido',
            minLength: 'Mínimo de {0} caracteres',
            maxLength: 'Máximo de {0} caracteres',
            numeric: 'Digite apenas números',
            decimal: 'Digite um valor decimal válido',
            date: 'Digite uma data válida',
            url: 'Digite uma URL válida'
        }
    },

    // Application constants
    constants: {
        // Contact scores
        contactScores: {
            high: { min: 70, max: 100, class: 'high', color: '#10b981' },
            medium: { min: 40, max: 69, class: 'medium', color: '#f59e0b' },
            low: { min: 0, max: 39, class: 'low', color: '#ef4444' }
        },

        // Opportunity stages
        opportunityStages: {
            'prospeccao': { label: 'Prospecção', color: '#64748b', probability: 10 },
            'qualificacao': { label: 'Qualificação', color: '#06b6d4', probability: 25 },
            'proposta': { label: 'Proposta', color: '#f59e0b', probability: 50 },
            'negociacao': { label: 'Negociação', color: '#8b5cf6', probability: 75 },
            'fechamento': { label: 'Fechamento', color: '#10b981', probability: 90 }
        },

        // Activity types
        activityTypes: {
            'ligacao': { label: 'Ligação', icon: 'fas fa-phone', color: '#2563eb' },
            'email': { label: 'Email', icon: 'fas fa-envelope', color: '#06b6d4' },
            'reuniao': { label: 'Reunião', icon: 'fas fa-users', color: '#10b981' },
            'tarefa': { label: 'Tarefa', icon: 'fas fa-tasks', color: '#64748b' },
            'follow_up': { label: 'Follow-up', icon: 'fas fa-redo', color: '#f59e0b' },
            'proposta': { label: 'Proposta', icon: 'fas fa-file-alt', color: '#8b5cf6' },
            'apresentacao': { label: 'Apresentação', icon: 'fas fa-presentation', color: '#ef4444' }
        },

        // Status mappings
        statuses: {
            opportunity: {
                'ativa': { label: 'Ativa', class: 'status-active' },
                'pausada': { label: 'Pausada', class: 'status-pending' },
                'fechada_ganha': { label: 'Fechada - Ganha', class: 'status-active' },
                'fechada_perdida': { label: 'Fechada - Perdida', class: 'status-closed' }
            },
            activity: {
                'agendada': { label: 'Agendada', class: 'status-pending' },
                'em_andamento': { label: 'Em Andamento', class: 'status-active' },
                'concluida': { label: 'Concluída', class: 'status-active' },
                'cancelada': { label: 'Cancelada', class: 'status-closed' },
                'adiada': { label: 'Adiada', class: 'status-pending' }
            },
            user: {
                'ativo': { label: 'Ativo', class: 'status-active' },
                'inativo': { label: 'Inativo', class: 'status-inactive' }
            },
            company: {
                'ativa': { label: 'Ativa', class: 'status-active' },
                'inativa': { label: 'Inativa', class: 'status-inactive' },
                'prospect': { label: 'Prospect', class: 'status-pending' },
                'cliente': { label: 'Cliente', class: 'status-active' }
            }
        },

        // Priority levels
        priorities: {
            'baixa': { label: 'Baixa', class: 'text-success', icon: 'fas fa-arrow-down' },
            'media': { label: 'Média', class: 'text-warning', icon: 'fas fa-minus' },
            'alta': { label: 'Alta', class: 'text-danger', icon: 'fas fa-arrow-up' },
            'urgente': { label: 'Urgente', class: 'text-danger', icon: 'fas fa-exclamation-triangle' }
        }
    },

    // File upload settings
    upload: {
        maxFileSize: 10 * 1024 * 1024, // 10MB
        allowedTypes: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        maxFiles: 5
    },

    // Search settings
    search: {
        minLength: 2,
        debounceDelay: 300
    },

    // Auto-refresh intervals (in milliseconds)
    refresh: {
        notifications: 30000, // 30 seconds
        dashboard: 300000,    // 5 minutes
        activities: 60000     // 1 minute
    },

    // Mobile breakpoints
    breakpoints: {
        xs: 0,
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1400
    },

    // Debug mode
    debug: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
};

// Freeze configuration to prevent accidental modifications
Object.freeze(Config);

// Global configuration accessor
window.Config = Config;
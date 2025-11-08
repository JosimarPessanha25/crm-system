/**
 * CRM System Dashboard Component
 * Displays main dashboard with KPIs, charts, and recent activities
 */

const Dashboard = {
    // Dashboard data
    data: {
        stats: null,
        recentActivities: null,
        charts: {}
    },

    /**
     * Render the dashboard page
     */
    render: function() {
        const pageContent = document.getElementById('pageContent');
        
        // Initial dashboard layout
        pageContent.innerHTML = `
            <div class="container-fluid p-4">
                <!-- Dashboard Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
                        </h1>
                        <p class="text-muted mb-0">Visão geral do sistema - ${Utils.formatDate(new Date(), 'date')}</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm me-2" onclick="Dashboard.refresh()">
                            <i class="fas fa-sync-alt me-1"></i>Atualizar
                        </button>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="dashboardPeriod" id="period7d" value="7" checked>
                            <label class="btn btn-outline-secondary btn-sm" for="period7d">7 dias</label>
                            
                            <input type="radio" class="btn-check" name="dashboardPeriod" id="period30d" value="30">
                            <label class="btn btn-outline-secondary btn-sm" for="period30d">30 dias</label>
                            
                            <input type="radio" class="btn-check" name="dashboardPeriod" id="period90d" value="90">
                            <label class="btn btn-outline-secondary btn-sm" for="period90d">90 dias</label>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards Row -->
                <div class="row mb-4" id="kpiCards">
                    ${this.renderKPISkeletons()}
                </div>

                <!-- Charts and Activities Row -->
                <div class="row">
                    <!-- Pipeline Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Pipeline por Estágio
                                </h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="navigateTo('opportunities')">
                                    Ver Pipeline
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="pipelineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-lg-4 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Atividades Recentes
                                </h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="navigateTo('activities')">
                                    Ver Todas
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div id="recentActivitiesList" style="max-height: 400px; overflow-y: auto;">
                                    ${this.renderActivitiesSkeletons()}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart Row -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Vendas por Período
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Atividades por Tipo
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="activitiesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Setup event listeners
        this.setupEventListeners();

        // Load dashboard data
        this.loadData();
    },

    /**
     * Setup event listeners for dashboard interactions
     */
    setupEventListeners: function() {
        // Period filter change
        document.querySelectorAll('input[name="dashboardPeriod"]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.loadData(e.target.value);
            });
        });
    },

    /**
     * Load dashboard data from API
     * @param {string} period - Time period (7, 30, 90 days)
     */
    loadData: async function(period = '7') {
        try {
            // Load dashboard stats
            const statsResponse = await API.dashboard.stats();
            this.data.stats = statsResponse.data;

            // Load recent activities
            const activitiesResponse = await API.dashboard.recentActivities(10);
            this.data.recentActivities = activitiesResponse.data;

            // Render the loaded data
            this.renderKPICards();
            this.renderRecentActivities();
            this.renderCharts();

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            Utils.showToast('Erro ao carregar dados do dashboard', 'error');
        }
    },

    /**
     * Render KPI skeletons while loading
     */
    renderKPISkeletons: function() {
        return `
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body dashboard-kpi">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text" style="width: 60%;"></div>
                    </div>
                </div>
            </div>
        `.repeat(4);
    },

    /**
     * Render KPI cards with real data
     */
    renderKPICards: function() {
        if (!this.data.stats) return;

        const stats = this.data.stats;
        const kpiContainer = document.getElementById('kpiCards');

        const kpiData = [
            {
                title: 'Vendas do Mês',
                value: Utils.formatCurrency(stats.monthly_revenue || 0),
                change: stats.revenue_change || 0,
                icon: 'fas fa-dollar-sign',
                color: 'success'
            },
            {
                title: 'Pipeline Total',
                value: Utils.formatCurrency(stats.pipeline_value || 0),
                change: stats.pipeline_change || 0,
                icon: 'fas fa-bullseye',
                color: 'primary'
            },
            {
                title: 'Atividades Hoje',
                value: `${stats.today_activities || 0}`,
                change: stats.activities_change || 0,
                icon: 'fas fa-tasks',
                color: 'info'
            },
            {
                title: 'Novos Leads',
                value: `${stats.new_leads || 0}`,
                change: stats.leads_change || 0,
                icon: 'fas fa-users',
                color: 'warning'
            }
        ];

        kpiContainer.innerHTML = kpiData.map(kpi => `
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body dashboard-kpi">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <i class="${kpi.icon} fa-2x text-${kpi.color}"></i>
                            </div>
                            <div class="text-end">
                                <div class="display-6 fw-bold text-${kpi.color}">${kpi.value}</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">${kpi.title}</span>
                            <span class="badge bg-${kpi.change >= 0 ? 'success' : 'danger'} rounded-pill">
                                <i class="fas fa-arrow-${kpi.change >= 0 ? 'up' : 'down'} me-1"></i>
                                ${Math.abs(kpi.change)}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Render activities skeletons while loading
     */
    renderActivitiesSkeletons: function() {
        return `
            <div class="p-3 border-bottom">
                <div class="skeleton skeleton-text mb-2"></div>
                <div class="skeleton skeleton-text" style="width: 70%;"></div>
                <div class="skeleton skeleton-text" style="width: 40%;"></div>
            </div>
        `.repeat(5);
    },

    /**
     * Render recent activities list
     */
    renderRecentActivities: function() {
        if (!this.data.recentActivities) return;

        const activitiesList = document.getElementById('recentActivitiesList');
        const activities = this.data.recentActivities;

        if (activities.length === 0) {
            activitiesList.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma atividade recente</p>
                </div>
            `;
            return;
        }

        activitiesList.innerHTML = activities.map(activity => {
            const activityType = Config.constants.activityTypes[activity.tipo] || {};
            const isOverdue = new Date(activity.data_vencimento) < new Date() && 
                            !['concluida', 'cancelada'].includes(activity.status);

            return `
                <div class="activity-item ${isOverdue ? 'overdue' : ''} p-3 border-bottom cursor-pointer" 
                     onclick="navigateTo('activities'); Activities.showDetails(${activity.id})">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <i class="${activityType.icon || 'fas fa-tasks'} fa-lg" 
                               style="color: ${activityType.color || '#6c757d'}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">${Utils.escapeHtml(activity.titulo)}</div>
                            ${activity.company ? 
                                `<div class="small text-muted mb-1">
                                    <i class="fas fa-building me-1"></i>${Utils.escapeHtml(activity.company.nome)}
                                </div>` : ''}
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="activity-time">
                                    ${Utils.formatRelativeTime(activity.data_vencimento)}
                                </span>
                                <span class="badge bg-${isOverdue ? 'danger' : 'secondary'} rounded-pill">
                                    ${activity.status}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render all charts
     */
    renderCharts: function() {
        this.renderPipelineChart();
        this.renderRevenueChart();
        this.renderActivitiesChart();
    },

    /**
     * Render pipeline chart
     */
    renderPipelineChart: function() {
        const canvas = document.getElementById('pipelineChart');
        if (!canvas || !this.data.stats) return;

        // Destroy existing chart if it exists
        if (this.data.charts.pipeline) {
            this.data.charts.pipeline.destroy();
        }

        const ctx = canvas.getContext('2d');
        const pipelineData = this.data.stats.pipeline_stages || {};

        const stages = Object.keys(Config.constants.opportunityStages);
        const labels = stages.map(stage => Config.constants.opportunityStages[stage].label);
        const values = stages.map(stage => pipelineData[stage]?.total_value || 0);
        const counts = stages.map(stage => pipelineData[stage]?.count || 0);

        this.data.charts.pipeline = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Valor (R$)',
                    data: values,
                    backgroundColor: stages.map(stage => Config.constants.opportunityStages[stage].color),
                    borderRadius: 4,
                    yAxisID: 'y'
                }, {
                    label: 'Quantidade',
                    data: counts,
                    type: 'line',
                    borderColor: Config.chart.colors.secondary,
                    backgroundColor: 'transparent',
                    yAxisID: 'y1'
                }]
            },
            options: {
                ...Config.chart.defaultOptions,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Utils.formatCurrency(value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return `Valor: ${Utils.formatCurrency(context.raw)}`;
                                } else {
                                    return `Quantidade: ${context.raw} oportunidades`;
                                }
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Render revenue chart
     */
    renderRevenueChart: function() {
        const canvas = document.getElementById('revenueChart');
        if (!canvas || !this.data.stats) return;

        // Destroy existing chart if it exists
        if (this.data.charts.revenue) {
            this.data.charts.revenue.destroy();
        }

        const ctx = canvas.getContext('2d');
        const revenueData = this.data.stats.revenue_history || [];

        const labels = revenueData.map(item => Utils.formatDate(item.date, 'date'));
        const values = revenueData.map(item => item.value || 0);

        this.data.charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Vendas',
                    data: values,
                    borderColor: Config.chart.colors.primary,
                    backgroundColor: Config.chart.colors.primary + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...Config.chart.defaultOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Utils.formatCurrency(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Vendas: ${Utils.formatCurrency(context.raw)}`;
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Render activities chart
     */
    renderActivitiesChart: function() {
        const canvas = document.getElementById('activitiesChart');
        if (!canvas || !this.data.stats) return;

        // Destroy existing chart if it exists
        if (this.data.charts.activities) {
            this.data.charts.activities.destroy();
        }

        const ctx = canvas.getContext('2d');
        const activitiesData = this.data.stats.activities_by_type || {};

        const types = Object.keys(Config.constants.activityTypes);
        const labels = types.map(type => Config.constants.activityTypes[type].label);
        const values = types.map(type => activitiesData[type] || 0);
        const colors = types.map(type => Config.constants.activityTypes[type].color);

        this.data.charts.activities = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                ...Config.chart.defaultOptions,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    /**
     * Refresh dashboard data
     */
    refresh: function() {
        const selectedPeriod = document.querySelector('input[name="dashboardPeriod"]:checked')?.value || '7';
        this.loadData(selectedPeriod);
        Utils.showToast('Dashboard atualizado', 'success');
    },

    /**
     * Handle window resize
     */
    handleResize: function() {
        // Redraw charts on resize
        Object.values(this.data.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
};

// Export Dashboard globally
window.Dashboard = Dashboard;
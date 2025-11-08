#!/bin/bash

# CRM System Deployment Script
# This script helps deploy the CRM system to production

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="CRM System"
PROJECT_DIR="/var/www/crm-system"
BACKUP_DIR="/var/backups/crm-system"
LOG_FILE="/var/log/crm-deployment.log"
WEB_USER="www-data"

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

check_requirements() {
    print_status "Checking system requirements..."
    
    # Check PHP version
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed"
        exit 1
    fi
    
    php_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [[ $(echo "$php_version >= 8.0" | bc -l) -eq 0 ]]; then
        print_error "PHP 8.0 or higher is required. Current version: $php_version"
        exit 1
    fi
    
    print_success "PHP version $php_version is compatible"
    
    # Check required PHP extensions
    required_extensions=("pdo" "pdo_mysql" "json" "curl" "mbstring" "openssl")
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            print_error "Required PHP extension missing: $ext"
            exit 1
        fi
    done
    
    print_success "All required PHP extensions are available"
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed"
        exit 1
    fi
    
    print_success "Composer is available"
    
    # Check database connection
    if [[ -f "config/database.php" ]]; then
        print_status "Database configuration found"
    else
        print_warning "Database configuration not found. Please configure after deployment."
    fi
}

create_directories() {
    print_status "Creating necessary directories..."
    
    directories=(
        "$PROJECT_DIR"
        "$PROJECT_DIR/logs"
        "$PROJECT_DIR/uploads"
        "$PROJECT_DIR/cache" 
        "$BACKUP_DIR"
    )
    
    for dir in "${directories[@]}"; do
        if [[ ! -d "$dir" ]]; then
            mkdir -p "$dir"
            print_success "Created directory: $dir"
        fi
    done
}

backup_existing() {
    if [[ -d "$PROJECT_DIR" ]] && [[ "$(ls -A $PROJECT_DIR 2>/dev/null)" ]]; then
        print_status "Creating backup of existing installation..."
        
        backup_name="crm-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
        backup_path="$BACKUP_DIR/$backup_name"
        
        tar -czf "$backup_path" -C "$(dirname $PROJECT_DIR)" "$(basename $PROJECT_DIR)" 2>/dev/null || true
        
        if [[ -f "$backup_path" ]]; then
            print_success "Backup created: $backup_path"
        else
            print_warning "Backup creation failed, continuing with deployment"
        fi
    fi
}

deploy_files() {
    print_status "Deploying application files..."
    
    # Copy application files
    rsync -av --exclude='.git' --exclude='node_modules' --exclude='tests' . "$PROJECT_DIR/"
    
    print_success "Application files deployed"
}

install_dependencies() {
    print_status "Installing PHP dependencies..."
    
    cd "$PROJECT_DIR"
    
    # Install Composer dependencies
    composer install --no-dev --optimize-autoloader --no-interaction
    
    print_success "Dependencies installed"
}

configure_permissions() {
    print_status "Setting file permissions..."
    
    # Set ownership
    chown -R "$WEB_USER:$WEB_USER" "$PROJECT_DIR"
    
    # Set directory permissions
    find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
    
    # Set writable directories
    chmod -R 775 "$PROJECT_DIR/logs"
    chmod -R 775 "$PROJECT_DIR/uploads"
    chmod -R 775 "$PROJECT_DIR/cache"
    
    print_success "Permissions configured"
}

configure_database() {
    print_status "Configuring database..."
    
    cd "$PROJECT_DIR"
    
    if [[ -f "database/migrations/migrate.php" ]]; then
        print_status "Running database migrations..."
        php database/migrations/migrate.php
        print_success "Database migrations completed"
    else
        print_warning "Migration script not found. Please run migrations manually."
    fi
    
    if [[ -f "database/seeds/seed.php" ]]; then
        read -p "Do you want to seed the database with initial data? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            php database/seeds/seed.php
            print_success "Database seeded with initial data"
        fi
    fi
}

configure_webserver() {
    print_status "Configuring web server..."
    
    # Apache configuration
    if command -v apache2 &> /dev/null; then
        print_status "Detected Apache web server"
        
        apache_config="/etc/apache2/sites-available/crm-system.conf"
        
        cat > "$apache_config" << EOF
<VirtualHost *:80>
    ServerName crm.yourdomain.com
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        AllowOverride All
        Require all granted
        
        # Enable rewrite engine
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.html [L]
    </Directory>
    
    # API routing
    Alias /api $PROJECT_DIR/api.php
    
    <Location "/api">
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ $PROJECT_DIR/api.php [L]
    </Location>
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/crm-error.log
    CustomLog \${APACHE_LOG_DIR}/crm-access.log combined
</VirtualHost>
EOF
        
        # Enable site and required modules
        a2enmod rewrite
        a2ensite crm-system
        systemctl reload apache2
        
        print_success "Apache configuration completed"
    fi
    
    # Nginx configuration
    if command -v nginx &> /dev/null; then
        print_status "Detected Nginx web server"
        
        nginx_config="/etc/nginx/sites-available/crm-system"
        
        cat > "$nginx_config" << EOF
server {
    listen 80;
    server_name crm.yourdomain.com;
    root $PROJECT_DIR/public;
    index index.html;
    
    # Frontend routing
    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    # API routing
    location /api {
        try_files \$uri /api.php\$is_args\$args;
    }
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index api.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Logging
    access_log /var/log/nginx/crm-access.log;
    error_log /var/log/nginx/crm-error.log;
}
EOF
        
        # Enable site
        ln -sf "$nginx_config" /etc/nginx/sites-enabled/
        nginx -t && systemctl reload nginx
        
        print_success "Nginx configuration completed"
    fi
}

setup_ssl() {
    read -p "Do you want to setup SSL/TLS with Let's Encrypt? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        if command -v certbot &> /dev/null; then
            print_status "Setting up SSL certificate..."
            
            read -p "Enter your domain name: " domain_name
            read -p "Enter your email address: " email_address
            
            certbot --nginx -d "$domain_name" --email "$email_address" --agree-tos --non-interactive
            
            print_success "SSL certificate configured"
        else
            print_error "Certbot not installed. Please install it first."
        fi
    fi
}

setup_monitoring() {
    print_status "Setting up monitoring and logging..."
    
    # Create log rotation configuration
    logrotate_config="/etc/logrotate.d/crm-system"
    
    cat > "$logrotate_config" << EOF
$PROJECT_DIR/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    su $WEB_USER $WEB_USER
}
EOF
    
    # Create systemd service for monitoring (if systemd is available)
    if command -v systemctl &> /dev/null; then
        service_file="/etc/systemd/system/crm-monitor.service"
        
        cat > "$service_file" << EOF
[Unit]
Description=CRM System Monitor
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/crm-health-check
User=root

[Install]
WantedBy=multi-user.target
EOF
        
        # Create health check script
        health_check_script="/usr/local/bin/crm-health-check"
        
        cat > "$health_check_script" << 'EOF'
#!/bin/bash

# CRM System Health Check
LOG_FILE="/var/log/crm-health.log"
API_URL="http://localhost/api/health"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check API health
response=$(curl -s -w "%{http_code}" "$API_URL" -o /dev/null)

if [[ "$response" == "200" ]]; then
    log "API health check: OK"
else
    log "API health check: FAILED (HTTP $response)"
fi

# Check disk space
disk_usage=$(df /var/www/crm-system | tail -1 | awk '{print $5}' | sed 's/%//')

if [[ "$disk_usage" -gt 85 ]]; then
    log "Disk usage warning: ${disk_usage}%"
fi

# Check log file sizes
find /var/www/crm-system/logs -name "*.log" -size +100M -exec basename {} \; | while read logfile; do
    log "Large log file detected: $logfile"
done
EOF
        
        chmod +x "$health_check_script"
        
        # Create timer for regular health checks
        timer_file="/etc/systemd/system/crm-monitor.timer"
        
        cat > "$timer_file" << EOF
[Unit]
Description=Run CRM System Monitor every 5 minutes
Requires=crm-monitor.service

[Timer]
OnCalendar=*:0/5
Persistent=true

[Install]
WantedBy=timers.target
EOF
        
        systemctl daemon-reload
        systemctl enable crm-monitor.timer
        systemctl start crm-monitor.timer
        
        print_success "Monitoring system configured"
    fi
}

run_tests() {
    print_status "Running post-deployment tests..."
    
    cd "$PROJECT_DIR"
    
    # Run API tests if available
    if [[ -f "tests/api-test.php" ]]; then
        print_status "Running API integration tests..."
        php tests/api-test.php "http://localhost"
        
        if [[ $? -eq 0 ]]; then
            print_success "API tests passed"
        else
            print_warning "API tests failed. Please check the configuration."
        fi
    fi
    
    # Test web server configuration
    if command -v curl &> /dev/null; then
        print_status "Testing web server configuration..."
        
        response=$(curl -s -w "%{http_code}" "http://localhost" -o /dev/null)
        
        if [[ "$response" == "200" ]]; then
            print_success "Web server is responding correctly"
        else
            print_warning "Web server test failed (HTTP $response)"
        fi
    fi
}

print_summary() {
    print_success "Deployment completed successfully!"
    echo
    echo "=========================================="
    echo "  $PROJECT_NAME Deployment Summary"
    echo "=========================================="
    echo "Project Directory: $PROJECT_DIR"
    echo "Logs Directory: $PROJECT_DIR/logs"
    echo "Backup Directory: $BACKUP_DIR"
    echo "Web User: $WEB_USER"
    echo
    echo "Next Steps:"
    echo "1. Configure your database settings in config/database.php"
    echo "2. Update your domain name in the web server configuration"
    echo "3. Set up SSL/TLS certificate for production"
    echo "4. Configure email settings for notifications"
    echo "5. Set up regular backups"
    echo
    echo "Default Login:"
    echo "Email: admin@crm.com"
    echo "Password: admin123"
    echo
    echo "Please change the default password after first login!"
    echo "=========================================="
}

# Main deployment function
main() {
    print_status "Starting deployment of $PROJECT_NAME..."
    log_message "Deployment started"
    
    # Check if running as root
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root"
        exit 1
    fi
    
    # Run deployment steps
    check_requirements
    create_directories
    backup_existing
    deploy_files
    install_dependencies
    configure_permissions
    configure_database
    configure_webserver
    setup_ssl
    setup_monitoring
    run_tests
    
    log_message "Deployment completed successfully"
    print_summary
}

# Parse command line arguments
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "backup")
        backup_existing
        ;;
    "test")
        run_tests
        ;;
    "permissions")
        configure_permissions
        ;;
    "help")
        echo "Usage: $0 [deploy|backup|test|permissions|help]"
        echo
        echo "Commands:"
        echo "  deploy      - Full deployment (default)"
        echo "  backup      - Create backup only" 
        echo "  test        - Run tests only"
        echo "  permissions - Fix permissions only"
        echo "  help        - Show this help"
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Use '$0 help' for available commands"
        exit 1
        ;;
esac
#!/bin/bash

# Enable Apache modules with error handling
echo "Enabling Apache modules..."
a2enmod rewrite || echo "Rewrite module already enabled or failed"
a2enmod headers || echo "Headers module already enabled or failed" 
a2enmod expires || echo "Expires module already enabled or failed"
a2enmod deflate || echo "Deflate module already enabled or failed"

# Create database if it doesn't exist
echo "Setting up database..."
if [ ! -f /var/www/html/database/crm_system.db ]; then
    mkdir -p /var/www/html/database
    touch /var/www/html/database/crm_system.db
    chmod 666 /var/www/html/database/crm_system.db
fi

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod 666 /var/www/html/database/crm_system.db

# Test Apache configuration
echo "Testing Apache configuration..."
apache2ctl configtest

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
#!/bin/bash
# Render Build Script for PHP

echo "🚀 Building CRM for Render..."

# Install PHP (for systems that don't have it)
echo "📦 Installing PHP..."
apt-get update || echo "Could not update package lists (read-only filesystem)"
apt-get install -y php php-cli php-sqlite3 php-json php-mbstring php-curl || echo "Using system PHP"

# Create directories
mkdir -p database
mkdir -p storage/logs

# Set permissions
chmod -R 755 public/ || echo "Could not set public permissions"
chmod -R 755 database/ || echo "Could not set database permissions"

# Create SQLite database
touch database/crm_system.db
chmod 666 database/crm_system.db || echo "Could not set database file permissions"

# Check PHP installation
php --version || echo "PHP not found, using container default"

echo "✅ Build completed!"
#!/bin/bash
# Render Build Script

echo "🚀 Building CRM for Render..."

# Update system
apt-get update

# Install PHP extensions if needed
# (Render usually has these pre-installed)

# Set permissions
chmod -R 755 public/
chmod -R 755 database/

# Create database file for SQLite (since Render free tier doesn't include MySQL)
touch database/crm_system.db
chmod 666 database/crm_system.db

echo "✅ Build completed!"
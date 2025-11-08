#!/bin/bash
echo "🚀 Configurando ambiente CRM no Codespaces..."

# Instalar dependências PHP
echo "📦 Instalando extensões PHP..."
sudo apt-get update
sudo apt-get install -y php-mysql php-curl php-zip php-xml

# Configurar Apache
echo "🔧 Configurando Apache..."
sudo a2enmod rewrite
sudo service apache2 restart

# Configurar banco de dados
echo "🗄️ Configurando MySQL..."
sudo apt-get install -y mysql-server
sudo service mysql start

# Criar banco de dados
mysql -u root -e "CREATE DATABASE IF NOT EXISTS crm_system;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'crm_user'@'localhost' IDENTIFIED BY 'crm_password';"
mysql -u root -e "GRANT ALL PRIVILEGES ON crm_system.* TO 'crm_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Executar migração
echo "🔄 Executando migração do banco..."
php database/migrations/create_tables.php

# Configurar permissões
echo "🔐 Configurando permissões..."
sudo chown -R www-data:www-data /workspaces/crm-system
sudo chmod -R 755 /workspaces/crm-system

echo "✅ Ambiente configurado! Acesse: http://localhost:8080"
echo "🔑 Credenciais padrão: admin@admin.com / admin123"
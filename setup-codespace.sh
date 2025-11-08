#!/bin/bash

echo "🚀 Configurando CRM no Codespace..."

# Atualizar sistema
sudo apt-get update -y

# Instalar PHP e extensões necessárias
echo "📦 Instalando PHP e extensões..."
sudo apt-get install -y php php-mysql php-pdo php-json php-curl php-zip php-xml

# Instalar e configurar MySQL
echo "🗄️ Configurando MySQL..."
sudo apt-get install -y mysql-server
sudo service mysql start

# Aguardar MySQL inicializar
sleep 5

# Criar banco de dados e usuário
echo "📊 Criando banco de dados..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS crm_system;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'crm_user'@'localhost' IDENTIFIED BY 'crm_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON crm_system.* TO 'crm_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Executar migração do banco
echo "🔄 Executando migrações..."
cd /workspaces/crm-system
php database/migrations/create_tables.php

# Configurar Apache (se necessário)
echo "🌐 Configurando servidor web..."
sudo service apache2 start || echo "Apache não disponível, usando PHP built-in server"

# Iniciar servidor PHP
echo "🚀 Iniciando servidor CRM..."
cd /workspaces/crm-system
echo "✅ Configuração concluída!"
echo ""
echo "🎯 Para iniciar o CRM, execute:"
echo "   php -S 0.0.0.0:8080 -t public/"
echo ""
echo "🔐 Credenciais:"
echo "   Email: admin@admin.com"
echo "   Senha: admin123"
echo ""
echo "🌐 Acesso: Vá na aba PORTS e clique na porta 8080"
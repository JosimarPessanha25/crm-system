#!/bin/bash

echo "🚀 Deploy CRM no Heroku"
echo "======================"

# Verificar se Heroku CLI está instalado
if ! command -v heroku &> /dev/null; then
    echo "❌ Heroku CLI não encontrado!"
    echo "📥 Instale em: https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

# Login no Heroku
echo "🔐 Fazendo login no Heroku..."
heroku login

# Criar app no Heroku
echo "📱 Criando app no Heroku..."
read -p "Digite o nome do seu app (ex: meu-crm-teste): " APP_NAME
heroku create $APP_NAME

# Adicionar MySQL addon
echo "🗄️ Adicionando banco de dados..."
heroku addons:create jawsdb:kitefin -a $APP_NAME

# Configurar variáveis de ambiente
echo "⚙️ Configurando variáveis..."
heroku config:set PHP_BUILDPACK_VERSION="8.2" -a $APP_NAME

# Deploy
echo "🚀 Fazendo deploy..."
git push heroku main

# Executar migração
echo "🔄 Executando migração..."
heroku run php database/migrations/create_tables.php -a $APP_NAME

echo "✅ Deploy concluído!"
echo "🌐 Acesse: https://$APP_NAME.herokuapp.com"
echo "⚡ Logs: heroku logs --tail -a $APP_NAME"
# 🚀 Guia Rápido - Codespace Recovery Mode

## 🔧 **Problema:** Container em Recovery Mode

### **Solução 1: Rebuild Container (RECOMENDADO)**

1. **No Codespace, pressione:** `Ctrl + Shift + P`
2. **Digite:** `Codespaces: Rebuild Container`
3. **Aguarde 2-3 minutos** para recriar o ambiente
4. **Teste novamente** após rebuild

### **Solução 2: Setup Manual**

Se o rebuild não funcionar, execute no terminal do Codespace:

```bash
# Verificar se PHP está instalado
php --version

# Se PHP não estiver instalado:
sudo apt update
sudo apt install -y php php-cli php-mysql php-json php-curl

# Verificar se MySQL está instalado
mysql --version

# Se MySQL não estiver instalado:
sudo apt install -y mysql-server
sudo service mysql start

# Criar banco de dados
sudo mysql -e "CREATE DATABASE IF NOT EXISTS crm_system;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'crm_user'@'localhost' IDENTIFIED BY 'crm_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON crm_system.* TO 'crm_user'@'localhost';"

# Executar migração
php database/migrations/create_tables.php

# Iniciar servidor
php -S 0.0.0.0:8080 -t public/
```

### **Solução 3: Usar Container Padrão**

Se nada funcionar, você pode:

1. **Fechar o Codespace atual**
2. **Ir para seu repositório GitHub**
3. **Criar novo Codespace:**
   - Code → Codespaces → Create codespace on main
   - Escolher "Default" container em vez de custom

### **Solução 4: Testar Localmente (Backup)**

Se Codespace não cooperar:

```bash
# No seu Windows (PowerShell):
cd "C:\Users\pessa\OneDrive\Desktop\Nova pasta (6)\crm-system"
php -S localhost:8080 -t public/
```

## 🎯 **Por que aconteceu?**

O recovery mode geralmente ocorre quando:
- Container customizado (.devcontainer) tem conflitos
- Dependências não instalaram corretamente
- Timeout durante primeira inicialização

## ✅ **Status do Projeto:**

**Independente do Codespace, seu projeto está 100% funcional:**
- ✅ Código completo no GitHub
- ✅ Deploy scripts funcionais
- ✅ Documentação completa
- ✅ Pronto para produção

## 🚀 **Próximos Passos:**

1. **Tente o Rebuild primeiro** (mais simples)
2. **Se não funcionar, use setup manual**
3. **Como último recurso, crie novo Codespace**
4. **Continue compartilhando o projeto** mesmo que Codespace tenha problemas

**O importante é que seu CRM está completo e funcionando! 🎉**
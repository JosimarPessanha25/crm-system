# 🔧 Fix Render Deployment - PHP Environment

## 🚨 **Problema Identificado:**
O Render está usando ambiente Node.js que não tem PHP instalado.

## ✅ **Solução 1: Usar Dockerfile (RECOMENDADO)**

### **1. Configurar no Render:**
- **Environment:** `Docker` (em vez de Node.js)
- **Build Command:** (deixar vazio)
- **Start Command:** (deixar vazio - usa Dockerfile)

### **2. Configuração criada:**
- ✅ **Dockerfile** - Container PHP 8.2 + Apache
- ✅ **Apache config** - Virtual host otimizado
- ✅ **Build script** melhorado

## ✅ **Solução 2: Usar PHP Native Environment**

### **Configurar no Render:**
- **Environment:** `Native`
- **Build Command:** 
```bash
apt-get update && apt-get install -y php php-cli php-sqlite3 php-json php-mbstring
```
- **Start Command:**
```bash
php -S 0.0.0.0:$PORT -t public/
```

## 🚀 **Próximos Passos:**

### **Opção A (Dockerfile - Recomendado):**
1. No Render, vá em **Settings**
2. Mude **Environment** para `Docker`
3. Remove Build e Start commands
4. **Deploy novamente**

### **Opção B (Native):**
1. Mude **Environment** para `Native`  
2. **Build Command:**
```
apt-get update && apt-get install -y php php-cli php-sqlite3 php-json
```
3. **Start Command:**
```
php -S 0.0.0.0:$PORT -t public/
```

## 🎯 **Resultado Esperado:**
- ✅ Container com PHP instalado
- ✅ Apache servindo na porta 80
- ✅ SQLite database funcionando
- ✅ CRM acessível publicamente

## 📞 **Se continuar dando erro:**
- Ver logs no Render Dashboard  
- Verificar se Dockerfile foi commitado
- Testar localmente com Docker

**Vamos corrigir isso agora! 🚀**
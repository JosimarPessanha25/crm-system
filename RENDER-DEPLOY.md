# 🚀 Deploy CRM no Render

## 📋 **Passo-a-Passo Completo:**

### **1. Acessar Render**
1. Vá para: **https://render.com**
2. Faça login com GitHub (conecte sua conta)
3. Autorize acesso aos repositórios

### **2. Criar Web Service**
1. Clique em **"New +"** → **"Web Service"**
2. **Connect Repository:** `JosimarPessanha25/crm-system`
3. **Name:** `crm-system` (ou nome que preferir)
4. **Environment:** `Node.js` (funciona para PHP também)
5. **Branch:** `main`

### **3. Configurações de Deploy**

**Build Command:**
```bash
chmod +x render-build.sh && ./render-build.sh
```

**Start Command:**
```bash
php -S 0.0.0.0:$PORT -t public/
```

**Instance Type:**
- Escolha **"Free"** (0$/mês, 750h/mês)

### **4. Variáveis de Ambiente (Environment Variables)**

Adicionar estas variáveis:

| Nome | Valor |
|------|-------|
| `DB_TYPE` | `sqlite` |
| `DB_PATH` | `/opt/render/project/src/database/crm_system.db` |
| `JWT_SECRET` | `render_jwt_secret_2024_crm_system` |
| `APP_ENV` | `production` |

### **5. Deploy Automático**
1. Clique **"Create Web Service"**
2. Render fará deploy automático
3. Aguarde 3-5 minutos
4. URL será gerada: `https://crm-system-xyz.onrender.com`

## 🔐 **Credenciais de Acesso:**
- **Email:** `admin@admin.com`
- **Senha:** `admin123`

## 🎯 **Vantagens do Render:**

### ✅ **Gratuito:**
- 750 horas/mês (suficiente para demos)
- Certificado SSL automático
- Deploy automático via GitHub

### ✅ **Profissional:**
- URL pública real
- Logs detalhados
- Monitoramento incluído

### ✅ **Fácil:**
- Zero configuração de servidor
- Deploy automático a cada push
- Interface intuitiva

## 🚀 **Após o Deploy:**

### 📊 **Monitoramento:**
- Ver logs no painel Render
- Monitorar performance
- Configurar alertas (opcional)

### 🔄 **Atualizações:**
- Qualquer push para `main` = deploy automático
- Rollback fácil via interface
- Histórico de deploys

### 📱 **Compartilhamento:**
- URL pública para demos
- Funciona em qualquer dispositivo
- Sempre online (dentro das 750h)

## 📞 **Suporte:**

**Se der problema:**
1. Ver logs no painel Render
2. Verificar se build passou
3. Checar variáveis de ambiente
4. Testar localmente primeiro

## 🎯 **Resultado Final:**

Seu CRM estará disponível em:
**`https://seu-crm.onrender.com`**

Qualquer pessoa poderá acessar e testar! 🎉

---

**💡 Dica:** Render hiberna após 15min sem uso no plano gratuito, mas acorda rápido quando alguém acessa!
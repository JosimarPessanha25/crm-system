# 🚀 Guia para Colocar seu CRM Online

## Opções Gratuitas Disponíveis

### 1. 🔴 **GitHub Codespaces** (Mais Fácil - 60h/mês grátis)

**Vantagens:**
- ✅ Configuração automática
- ✅ Não precisa instalar nada
- ✅ Funciona direto no navegador
- ✅ 60 horas gratuitas por mês

**Como usar:**
1. Vá no seu repositório GitHub
2. Clique em "Code" > "Codespaces" > "Create codespace"
3. Aguarde 2-3 minutos para configurar
4. Acesse: `http://localhost:8080`

---

### 2. 🟠 **Heroku** (Deploy Real - Gratuito com limitações)

**Vantagens:**
- ✅ URL pública real
- ✅ Deploy automático do GitHub
- ✅ Banco de dados incluído
- ⚠️ "Dorme" após 30min inativo

**Como usar:**
1. Crie conta no [Heroku](https://heroku.com)
2. Execute: `bash deploy-heroku.sh`
3. Siga as instruções do script

---

### 3. 🔵 **Azure App Service** (Profissional - Créditos gratuitos)

**Vantagens:**
- ✅ $200 créditos gratuitos (12 meses)
- ✅ Infraestrutura profissional
- ✅ Monitoramento avançado
- ✅ Escalabilidade

**Como usar:**
1. Crie conta [Azure](https://azure.com/free)
2. Execute: `azd up` (seguindo o plano em `.azure/plan.copilotmd`)

---

### 4. 🟢 **Railway** (Alternativa moderna)

**Vantagens:**
- ✅ $5/mês de uso gratuito
- ✅ Deploy do GitHub automático
- ✅ Interface moderna

---

## 🎯 Recomendação Rápida

**Para teste rápido:** Use **GitHub Codespaces** - mais fácil e rápido

**Para demonstração:** Use **Heroku** - URL real para mostrar

**Para produção:** Use **Azure** - mais profissional

## 📞 Precisa de Ajuda?

1. Codespaces não funcionou? → Verifique se tem créditos GitHub
2. Heroku deu erro? → Instale Heroku CLI primeiro
3. Azure complexo? → Posso te ajudar passo-a-passo

**Qual opção prefere tentar primeiro?**
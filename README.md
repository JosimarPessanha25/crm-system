# CRM System - Sistema de Gestão de Relacionamento com Cliente

Um sistema CRM completo desenvolvido em PHP com frontend moderno em JavaScript, projetado para gerenciar contatos, oportunidades de vendas, atividades e relacionamentos com clientes.

## 📋 Funcionalidades

### 🏠 Dashboard
- Visão geral dos indicadores-chave (KPIs)
- Gráficos interativos do pipeline de vendas
- Atividades recentes e pendentes
- Métricas de performance e vendas

### 👥 Gestão de Contatos
- Cadastro completo de contatos e empresas
- Classificação por tipo (Lead, Prospect, Cliente)
- Histórico de interações e atividades
- Campos personalizáveis e observações

### 🎯 Gestão de Oportunidades
- Pipeline de vendas com estágios customizáveis
- Visualização Kanban e Lista
- Acompanhamento de probabilidade e valores
- Gestão de produtos/serviços por oportunidade

### 📅 Gestão de Atividades
- Tarefas, ligações, reuniões e eventos
- Sistema de lembretes e notificações
- Priorização e status de atividades
- Calendário integrado (em desenvolvimento)

### 🔐 Autenticação e Segurança
- Sistema de login JWT
- Controle de acesso e permissões
- Sessões seguras e auto-renovação de tokens
- Proteção contra ataques XSS e CSRF

## 🚀 Tecnologias Utilizadas

### Backend
- **PHP 8.0+** - Linguagem principal
- **Slim Framework 4** - Framework web minimalista
- **Firebase JWT** - Autenticação baseada em tokens
- **PDO** - Abstração de banco de dados
- **MySQL/PostgreSQL** - Banco de dados

### Frontend
- **JavaScript ES6+** - Linguagem principal
- **Bootstrap 5.3.2** - Framework CSS
- **Chart.js 4.4.0** - Gráficos interativos
- **DataTables 1.13.7** - Tabelas avançadas
- **Font Awesome 6.4.0** - Ícones

### Arquitetura
- **SPA (Single Page Application)** - Interface de usuário
- **REST API** - Comunicação cliente-servidor
- **Arquitetura MVC** - Organização do código
- **Design Responsivo** - Compatível com móveis

## 📦 Estrutura do Projeto

```
crm-system/
├── config/
│   ├── database.php          # Configuração do banco de dados
│   └── settings.php          # Configurações gerais
├── src/
│   ├── Controllers/          # Controladores da API
│   │   ├── AuthController.php
│   │   ├── ContactController.php
│   │   ├── OpportunityController.php
│   │   └── ActivityController.php
│   ├── Models/               # Modelos de dados
│   │   ├── Contact.php
│   │   ├── Opportunity.php
│   │   └── Activity.php
│   ├── Services/             # Serviços de negócio
│   │   ├── ContactService.php
│   │   ├── OpportunityService.php
│   │   └── ActivityService.php
│   └── Middleware/           # Middlewares
│       └── AuthMiddleware.php
├── public/
│   ├── index.html           # Aplicação frontend
│   └── assets/
│       ├── css/
│       │   └── style.css    # Estilos customizados
│       └── js/
│           ├── config.js    # Configurações
│           ├── utils.js     # Utilitários
│           ├── api.js       # Cliente REST
│           ├── auth.js      # Autenticação
│           ├── app.js       # Controlador principal
│           └── components/  # Componentes de página
│               ├── dashboard.js
│               ├── contacts.js
│               ├── opportunities.js
│               └── activities.js
├── database/
│   ├── migrations/          # Migrações do banco
│   └── seeds/              # Dados iniciais
├── api.php                 # Ponto de entrada da API
└── README.md              # Este arquivo
```

## 🛠️ Instalação e Configuração

### Pré-requisitos
- PHP 8.0 ou superior
- Composer
- MySQL 8.0 ou PostgreSQL 13+
- Servidor web (Apache/Nginx) ou PHP built-in server

### Passo a Passo

1. **Clone o repositório**
```bash
git clone https://github.com/seu-usuario/crm-system.git
cd crm-system
```

2. **Instale as dependências**
```bash
composer install
```

3. **Configure o banco de dados**
```bash
# Copie o arquivo de configuração
cp config/database.example.php config/database.php

# Edite as configurações do banco
nano config/database.php
```

4. **Execute as migrações**
```bash
php database/migrate.php
```

5. **Carregue os dados iniciais (opcional)**
```bash
php database/seed.php
```

6. **Configure o servidor web**

**Apache (.htaccess já incluído)**
```bash
# Aponte o DocumentRoot para a pasta public/
```

**Nginx**
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /path/to/crm-system/public;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        try_files $uri /api.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index api.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

**PHP Built-in Server (Desenvolvimento)**
```bash
php -S localhost:8000 -t public/
```

7. **Acesse a aplicação**
```bash
# Navegue para:
http://localhost:8000

# Login padrão:
Email: admin@crm.com
Senha: admin123
```

## 🔧 Configuração

### Banco de Dados
Edite `config/database.php`:
```php
<?php
return [
    'driver' => 'mysql', // ou 'pgsql'
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'crm_system',
    'username' => 'seu_usuario',
    'password' => 'sua_senha',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

### Configurações Gerais
Edite `config/settings.php`:
```php
<?php
return [
    'app' => [
        'name' => 'CRM System',
        'env' => 'development', // ou 'production'
        'debug' => true,
        'timezone' => 'America/Sao_Paulo',
    ],
    'jwt' => [
        'secret' => 'sua-chave-secreta-jwt',
        'lifetime' => 3600, // 1 hora
    ],
    'api' => [
        'base_url' => 'http://localhost:8000/api',
        'version' => 'v1',
    ]
];
```

## 📱 Uso da Aplicação

### Dashboard
- Visualize KPIs importantes em tempo real
- Acompanhe o pipeline de vendas por estágio
- Monitore atividades recentes e pendentes
- Acesse gráficos de performance

### Gestão de Contatos
1. Clique em "Contatos" no menu
2. Use "Novo Contato" para adicionar
3. Filtre por tipo, status ou busque por nome/empresa
4. Edite ou visualize detalhes clicando nas ações

### Gestão de Oportunidades
1. Acesse "Oportunidades" no menu
2. Alterne entre visualização Lista e Kanban
3. Crie nova oportunidade com "Nova Oportunidade"
4. Arraste entre estágios no modo Kanban
5. Acompanhe probabilidades e valores

### Gestão de Atividades
1. Vá para "Atividades" no menu
2. Crie diferentes tipos: Tarefa, Ligação, Reunião, Email, Evento
3. Defina prioridades e lembretes
4. Marque como concluída quando finalizada
5. Filtre por data, tipo ou status

## 🔌 API REST

### Endpoints Principais

#### Autenticação
```bash
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
```

#### Contatos
```bash
GET    /api/contacts          # Listar contatos
POST   /api/contacts          # Criar contato
GET    /api/contacts/{id}     # Obter contato
PUT    /api/contacts/{id}     # Atualizar contato
DELETE /api/contacts/{id}     # Excluir contato
```

#### Oportunidades
```bash
GET    /api/opportunities     # Listar oportunidades
POST   /api/opportunities     # Criar oportunidade
GET    /api/opportunities/{id} # Obter oportunidade
PUT    /api/opportunities/{id} # Atualizar oportunidade
DELETE /api/opportunities/{id} # Excluir oportunidade
```

#### Atividades
```bash
GET    /api/activities        # Listar atividades
POST   /api/activities        # Criar atividade
GET    /api/activities/{id}   # Obter atividade
PUT    /api/activities/{id}   # Atualizar atividade
DELETE /api/activities/{id}   # Excluir atividade
```

### Autenticação
Todas as requisições (exceto login) devem incluir o token JWT:
```bash
Authorization: Bearer {seu_jwt_token}
```

### Exemplo de Uso
```javascript
// Login
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'usuario@email.com',
        password: 'senha123'
    })
});

// Usar token nas próximas requisições
const data = await response.json();
const token = data.token;

// Listar contatos
const contacts = await fetch('/api/contacts', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

## 🧪 Testes

### Testes de Unidade
```bash
# Execute os testes PHPUnit
composer test

# Com cobertura de código
composer test-coverage
```

### Testes de Integração
```bash
# Testes de API
php tests/api/run-tests.php

# Testes de frontend
npm test
```

## 📊 Banco de Dados

### Principais Tabelas

#### contacts
- Armazena informações de contatos e empresas
- Relacionamentos com atividades e oportunidades

#### opportunities
- Pipeline de vendas e oportunidades de negócio
- Produtos/serviços associados

#### activities
- Tarefas, eventos e interações
- Sistema de lembretes e follow-ups

#### users
- Usuários do sistema e permissões
- Responsáveis por contatos e oportunidades

## 🚀 Deploy em Produção

### Scripts de Deploy Automatizado

O sistema inclui scripts de deploy automatizado para Linux/Unix e Windows:

#### Linux/Unix
```bash
# Torne o script executável
chmod +x deploy.sh

# Execute o deploy (requer sudo)
sudo ./deploy.sh

# Comandos específicos disponíveis:
sudo ./deploy.sh deploy      # Deploy completo (padrão)
sudo ./deploy.sh backup      # Apenas backup
sudo ./deploy.sh test        # Apenas testes
sudo ./deploy.sh permissions # Apenas permissões
```

#### Windows (PowerShell)
```powershell
# Execute como Administrador
.\deploy.ps1

# Comandos específicos disponíveis:
.\deploy.ps1 -Command deploy      # Deploy completo (padrão)
.\deploy.ps1 -Command backup      # Apenas backup
.\deploy.ps1 -Command test        # Apenas testes
.\deploy.ps1 -Command permissions # Apenas permissões

# Com parâmetros personalizados:
.\deploy.ps1 -SiteName "My-CRM" -ProjectPath "D:\websites\crm"
```

### Configurações de Produção

#### 1. Configure o ambiente de produção
```bash
# Copie e configure o arquivo de ambiente
cp .env.example .env

# Edite as configurações para produção
nano .env
```

#### 2. Variáveis de ambiente importantes
```bash
# Aplicação
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.yourdomain.com

# Segurança (ALTERE ESTAS CHAVES!)
JWT_SECRET=sua-chave-secreta-de-256-bits
ENCRYPTION_KEY=sua-chave-de-encriptacao-32-chars

# Banco de dados
DB_HOST=localhost
DB_DATABASE=crm_system
DB_USERNAME=crm_user
DB_PASSWORD=senha_segura_aqui

# Email
MAIL_HOST=smtp.gmail.com
MAIL_USER=seu-email@gmail.com
MAIL_PASS=sua-senha-do-email
```

#### 3. Configure SSL/HTTPS
```bash
# Usando Let's Encrypt (Certbot)
certbot --nginx -d crm.yourdomain.com

# Ou para Apache
certbot --apache -d crm.yourdomain.com
```

#### 4. Configure monitoramento
O script de deploy configura automaticamente:
- Rotação de logs
- Health checks do sistema
- Monitoramento de espaço em disco
- Verificação de performance da API

### Testes de Deploy

#### Execute testes de integração
```bash
# Testes da API
php tests/api-test.php http://yourdomain.com

# Testes do frontend (abra no navegador)
open tests/frontend-test.html
```

#### Verificação de saúde do sistema
```bash
# Status da API
curl -X GET http://yourdomain.com/api/health

# Status do sistema
curl -X GET http://yourdomain.com/api/status
```

### Checklist de Deploy Completo
- [ ] ✅ Scripts de deploy executados com sucesso
- [ ] ✅ Variáveis de ambiente configuradas
- [ ] ✅ Banco de dados migrado e configurado
- [ ] ✅ SSL/HTTPS configurado
- [ ] ✅ Permissões de arquivo configuradas
- [ ] ✅ Servidor web configurado (Apache/Nginx/IIS)
- [ ] ✅ Testes de integração executados
- [ ] ✅ Monitoramento e logs configurados
- [ ] ✅ Backups automatizados configurados
- [ ] ✅ Credenciais padrão alteradas
- [ ] ✅ Funcionalidades críticas testadas

### Configuração de Backup Automatizado
```bash
# Backup do banco (diário às 2h)
0 2 * * * php /var/www/crm-system/scripts/backup-database.php

# Backup de arquivos (semanal aos domingos às 3h)
0 3 * * 0 php /var/www/crm-system/scripts/backup-files.php
```

### Monitoramento e Logs
- **Logs da aplicação**: `/var/www/crm-system/logs/app.log`
- **Logs de erro**: `/var/www/crm-system/logs/error.log`
- **Logs da API**: `/var/www/crm-system/logs/api.log`
- **Health checks**: Executados a cada 5 minutos automaticamente

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Padrões de Código
- PHP: PSR-12
- JavaScript: ESLint + Prettier
- CSS: BEM methodology
- Commits: Conventional Commits

## 📝 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 📞 Suporte

- **Documentação**: [Wiki do projeto](https://github.com/seu-usuario/crm-system/wiki)
- **Issues**: [GitHub Issues](https://github.com/seu-usuario/crm-system/issues)
- **Email**: suporte@crm-system.com

## 🗺️ Roadmap

### Versão 2.0 (Próximas Funcionalidades)
- [ ] Módulo de relatórios avançados
- [ ] Integração com email marketing
- [ ] App mobile (React Native)
- [ ] Integração com WhatsApp Business
- [ ] Sistema de workflows automatizados
- [ ] Dashboard de BI com Power BI/Tableau
- [ ] API de integração com terceiros
- [ ] Sistema de tickets de suporte

### Melhorias Contínuas
- [ ] Performance e otimização
- [ ] Testes automatizados
- [ ] Documentação expandida
- [ ] Acessibilidade (WCAG 2.1)
- [ ] Internacionalização (i18n)

---

**Desenvolvido com ❤️ para otimizar relacionamentos com clientes**
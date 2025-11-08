<?php

declare(strict_types=1);

/**
 * Complete CRM Database with Auto-Migration
 */

// Database path
$databaseDir = __DIR__ . '/../database';
$databasePath = $databaseDir . '/crm_system.db';

// Ensure database directory exists
if (!is_dir($databaseDir)) {
    @mkdir($databaseDir, 0755, true);
}

try {
    // Create PDO connection
    $pdo = new PDO("sqlite:$databasePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Create complete CRM database wrapper
    $database = new class($pdo) {
        private PDO $connection;
        
        public function __construct(PDO $connection) {
            $this->connection = $connection;
        }
        
        public function getConnection(): PDO {
            return $this->connection;
        }
        
        public function setAsGlobal(): void {
            // Compatibility with frameworks
        }
        
        public function bootEloquent(): void {
            // Run migrations on first boot
            $this->runMigrations();
        }
        
        public function query(string $sql, array $params = []): array {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        
        public function execute(string $sql, array $params = []): int {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }
        
        public function lastInsertId(): string {
            return $this->connection->lastInsertId();
        }
        
        private function runMigrations(): void {
            try {
                // Create migrations tracking table
                $this->connection->exec("
                    CREATE TABLE IF NOT EXISTS migrations (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        migration VARCHAR(255) NOT NULL,
                        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // List of all migrations
                $migrations = [
                    'create_usuarios_table',
                    'create_empresas_table',
                    'create_contatos_table', 
                    'create_oportunidades_table',
                    'create_atividades_table',
                    'create_interacoes_table',
                    'create_tickets_table',
                    'create_campanhas_table',
                    'create_campanhas_envio_table',
                    'create_automacoes_table',
                    'insert_demo_data'
                ];
                
                // Run each migration if not already executed
                foreach ($migrations as $migration) {
                    $stmt = $this->connection->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
                    $stmt->execute([$migration]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        $this->executeMigration($migration);
                        
                        $insert = $this->connection->prepare("INSERT INTO migrations (migration) VALUES (?)");
                        $insert->execute([$migration]);
                    }
                }
                
            } catch (Exception $e) {
                error_log("Migration error: " . $e->getMessage());
            }
        }
        
        private function executeMigration(string $migration): void {
            switch ($migration) {
                case 'create_usuarios_table':
                    $this->connection->exec("
                        CREATE TABLE usuarios (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            nome VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            senha_hash VARCHAR(255) NOT NULL,
                            role VARCHAR(50) DEFAULT 'user',
                            permissions TEXT DEFAULT '[]',
                            timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
                            ativo BOOLEAN DEFAULT 1,
                            avatar_url VARCHAR(500),
                            last_login_at DATETIME,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    $this->connection->exec("CREATE INDEX idx_usuarios_email ON usuarios(email)");
                    break;
                    
                case 'create_empresas_table':
                    $this->connection->exec("
                        CREATE TABLE empresas (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            nome VARCHAR(255) NOT NULL,
                            cnpj_cpf VARCHAR(20),
                            endereco TEXT,
                            setor VARCHAR(100),
                            website VARCHAR(255),
                            telefone VARCHAR(20),
                            receita_anual DECIMAL(15,2),
                            num_funcionarios INTEGER,
                            tags TEXT DEFAULT '[]',
                            custom_fields TEXT DEFAULT '{}',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_contatos_table':
                    $this->connection->exec("
                        CREATE TABLE contatos (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            nome VARCHAR(255) NOT NULL,
                            emails TEXT DEFAULT '[]',
                            telefones TEXT DEFAULT '[]', 
                            cargo VARCHAR(100),
                            origem VARCHAR(50),
                            tags TEXT DEFAULT '[]',
                            empresa_id TEXT REFERENCES empresas(id) ON DELETE SET NULL,
                            owner_id TEXT REFERENCES usuarios(id) ON DELETE SET NULL,
                            score INTEGER DEFAULT 0,
                            status VARCHAR(20) DEFAULT 'ativo',
                            custom_fields TEXT DEFAULT '{}',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_oportunidades_table':
                    $this->connection->exec("
                        CREATE TABLE oportunidades (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            titulo VARCHAR(255) NOT NULL,
                            descricao TEXT,
                            valor_estimado DECIMAL(15,2),
                            moeda VARCHAR(3) DEFAULT 'BRL',
                            estagio VARCHAR(50) DEFAULT 'prospecting',
                            probabilidade INTEGER DEFAULT 0,
                            contato_id TEXT REFERENCES contatos(id) ON DELETE CASCADE,
                            empresa_id TEXT REFERENCES empresas(id) ON DELETE CASCADE,
                            owner_id TEXT REFERENCES usuarios(id) ON DELETE SET NULL,
                            expected_close_date DATE,
                            origem VARCHAR(50),
                            lost_reason VARCHAR(255),
                            closed_at DATETIME,
                            tags TEXT DEFAULT '[]',
                            custom_fields TEXT DEFAULT '{}',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_atividades_table':
                    $this->connection->exec("
                        CREATE TABLE atividades (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            tipo VARCHAR(50) NOT NULL,
                            assunto VARCHAR(255) NOT NULL,
                            descricao TEXT,
                            status VARCHAR(20) DEFAULT 'pendente',
                            due_date DATETIME,
                            completed_at DATETIME,
                            relacionado_tipo VARCHAR(20),
                            relacionado_id TEXT,
                            assigned_to TEXT REFERENCES usuarios(id),
                            created_by TEXT REFERENCES usuarios(id),
                            reminders TEXT DEFAULT '[]',
                            location VARCHAR(255),
                            duration_minutes INTEGER,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_interacoes_table':
                    $this->connection->exec("
                        CREATE TABLE interacoes (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            tipo VARCHAR(50) NOT NULL,
                            assunto VARCHAR(255),
                            conteudo TEXT,
                            anexos TEXT DEFAULT '[]',
                            direction VARCHAR(10),
                            relacionado_tipo VARCHAR(20),
                            relacionado_id TEXT NOT NULL,
                            author_id TEXT REFERENCES usuarios(id),
                            external_id VARCHAR(255),
                            metadata TEXT DEFAULT '{}',
                            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_tickets_table':
                    $this->connection->exec("
                        CREATE TABLE tickets (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            numero INTEGER UNIQUE,
                            assunto VARCHAR(255) NOT NULL,
                            descricao TEXT,
                            prioridade VARCHAR(20) DEFAULT 'media',
                            status VARCHAR(20) DEFAULT 'aberto',
                            categoria VARCHAR(100),
                            contato_id TEXT REFERENCES contatos(id),
                            empresa_id TEXT REFERENCES empresas(id),
                            assigned_to TEXT REFERENCES usuarios(id),
                            created_by TEXT REFERENCES usuarios(id),
                            sla_deadline DATETIME,
                            first_response_at DATETIME,
                            resolved_at DATETIME,
                            tags TEXT DEFAULT '[]',
                            satisfaction_score INTEGER,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_campanhas_table':
                    $this->connection->exec("
                        CREATE TABLE campanhas (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            nome VARCHAR(255) NOT NULL,
                            tipo VARCHAR(50) DEFAULT 'email',
                            descricao TEXT,
                            status VARCHAR(20) DEFAULT 'rascunho',
                            segmento_query TEXT,
                            template_email TEXT,
                            metrics TEXT DEFAULT '{}',
                            scheduled_at DATETIME,
                            sent_at DATETIME,
                            created_by TEXT REFERENCES usuarios(id),
                            tags TEXT DEFAULT '[]',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'create_campanhas_envio_table':
                    $this->connection->exec("
                        CREATE TABLE campanhas_envio (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            campanha_id TEXT REFERENCES campanhas(id) ON DELETE CASCADE,
                            contato_id TEXT REFERENCES contatos(id) ON DELETE CASCADE,
                            status VARCHAR(20) DEFAULT 'pendente',
                            sent_at DATETIME,
                            delivered_at DATETIME,
                            opened_at DATETIME,
                            clicked_at DATETIME,
                            bounced_at DATETIME,
                            error_msg TEXT,
                            external_id VARCHAR(255)
                        )
                    ");
                    break;
                    
                case 'create_automacoes_table':
                    $this->connection->exec("
                        CREATE TABLE automacoes (
                            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
                            nome VARCHAR(255) NOT NULL,
                            descricao TEXT,
                            trigger_config TEXT NOT NULL,
                            conditions TEXT DEFAULT '[]',
                            actions TEXT NOT NULL,
                            ativo BOOLEAN DEFAULT 1,
                            created_by TEXT REFERENCES usuarios(id),
                            executions_count INTEGER DEFAULT 0,
                            last_execution_at DATETIME,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    break;
                    
                case 'insert_demo_data':
                    // Insert admin user
                    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $this->connection->exec("
                        INSERT OR IGNORE INTO usuarios (id, nome, email, senha_hash, role) VALUES 
                        ('admin001', 'Admin CRM', 'admin@crm.com', '$adminPassword', 'admin')
                    ");
                    
                    // Insert sample empresa
                    $this->connection->exec("
                        INSERT OR IGNORE INTO empresas (id, nome, cnpj_cpf, setor) VALUES 
                        ('empresa001', 'TechCorp Ltda', '12.345.678/0001-90', 'Tecnologia')
                    ");
                    
                    // Insert sample contato  
                    $this->connection->exec("
                        INSERT OR IGNORE INTO contatos (id, nome, emails, cargo, empresa_id, owner_id) VALUES 
                        ('contato001', 'João Silva', '[\"joao@techcorp.com\"]', 'CTO', 'empresa001', 'admin001')
                    ");
                    
                    // Insert sample oportunidade
                    $this->connection->exec("
                        INSERT OR IGNORE INTO oportunidades (id, titulo, valor_estimado, estagio, contato_id, owner_id) VALUES 
                        ('oport001', 'Projeto Sistema CRM', 75000.00, 'qualification', 'contato001', 'admin001')
                    ");
                    break;
            }
        }
    };
    
    return $database;

} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    
    // Return fallback database
    return new class {
        public function setAsGlobal(): void {}
        public function bootEloquent(): void {}
        public function getConnection(): ?PDO { return null; }
        public function query(string $sql, array $params = []): array { return []; }
        public function execute(string $sql, array $params = []): int { return 0; }
        public function lastInsertId(): string { return '0'; }
    };
}
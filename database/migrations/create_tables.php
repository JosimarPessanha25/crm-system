<?php
/**
 * CRM System Database Migration
 * Creates all necessary tables for the CRM system
 */

// Database configuration
$host = 'localhost';
$dbname = 'crm_system';
$username = 'crm_user';
$password = 'crm_pass';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");
    
    echo "📊 Creating CRM System Database Tables...\n";
    echo str_repeat("=", 50) . "\n";
    
    // Users table
    echo "Creating users table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'manager', 'user') DEFAULT 'user',
            avatar VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // Companies table
    echo "Creating companies table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS companies (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            website VARCHAR(255) NULL,
            industry VARCHAR(100) NULL,
            size ENUM('startup', 'small', 'medium', 'large', 'enterprise') NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            country VARCHAR(100) DEFAULT 'Brasil',
            postal_code VARCHAR(20) NULL,
            notes TEXT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_industry (industry),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // Contacts table
    echo "Creating contacts table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            mobile VARCHAR(50) NULL,
            position VARCHAR(100) NULL,
            department VARCHAR(100) NULL,
            type ENUM('lead', 'prospect', 'customer', 'partner') DEFAULT 'lead',
            source ENUM('website', 'referral', 'social_media', 'advertising', 'event', 'cold_call', 'other') NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            country VARCHAR(100) DEFAULT 'Brasil',
            postal_code VARCHAR(20) NULL,
            birthday DATE NULL,
            notes TEXT NULL,
            tags JSON NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            assigned_to INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_name (name),
            INDEX idx_email (email),
            INDEX idx_type (type),
            INDEX idx_source (source),
            INDEX idx_status (status),
            INDEX idx_company (company_id),
            INDEX idx_assigned (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // Opportunities table
    echo "Creating opportunities table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS opportunities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            contact_id INT NULL,
            company_id INT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            value DECIMAL(15,2) DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'BRL',
            probability INT DEFAULT 0,
            stage ENUM('lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'lead',
            source ENUM('website', 'referral', 'social_media', 'advertising', 'event', 'cold_call', 'other') NULL,
            products JSON NULL,
            expected_close_date DATE NULL,
            actual_close_date DATE NULL,
            notes TEXT NULL,
            tags JSON NULL,
            status ENUM('active', 'won', 'lost', 'on_hold') DEFAULT 'active',
            assigned_to INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_name (name),
            INDEX idx_stage (stage),
            INDEX idx_status (status),
            INDEX idx_value (value),
            INDEX idx_close_date (expected_close_date),
            INDEX idx_contact (contact_id),
            INDEX idx_company (company_id),
            INDEX idx_assigned (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // Activities table
    echo "Creating activities table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type ENUM('task', 'call', 'meeting', 'email', 'event') NOT NULL,
            subject VARCHAR(255) NOT NULL,
            description TEXT NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            due_date DATETIME NULL,
            completed_date DATETIME NULL,
            duration INT NULL COMMENT 'Duration in minutes',
            location VARCHAR(255) NULL,
            contact_id INT NULL,
            opportunity_id INT NULL,
            company_id INT NULL,
            assigned_to INT NULL,
            created_by INT NULL,
            notes TEXT NULL,
            attachments JSON NULL,
            reminders JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
            FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_due_date (due_date),
            INDEX idx_contact (contact_id),
            INDEX idx_opportunity (opportunity_id),
            INDEX idx_company (company_id),
            INDEX idx_assigned (assigned_to),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // User sessions table (for JWT token management)
    echo "Creating user_sessions table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            refresh_token_hash VARCHAR(255) NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_token (token_hash),
            INDEX idx_expires (expires_at),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    // Audit log table
    echo "Creating audit_logs table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT NULL,
            old_values JSON NULL,
            new_values JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅\n";
    
    echo str_repeat("=", 50) . "\n";
    echo "🎉 Database migration completed successfully!\n\n";
    
    echo "📋 Tables created:\n";
    echo "   ✅ users - User accounts and authentication\n";
    echo "   ✅ companies - Company/organization records\n";
    echo "   ✅ contacts - Individual contacts\n";
    echo "   ✅ opportunities - Sales pipeline\n";
    echo "   ✅ activities - Tasks, calls, meetings, etc.\n";
    echo "   ✅ user_sessions - JWT token management\n";
    echo "   ✅ audit_logs - System activity tracking\n\n";
    
    echo "🔄 Next steps:\n";
    echo "   1. Run: php database/seeds/seed.php (to add initial data)\n";
    echo "   2. Configure database connection in config/database.php\n";
    echo "   3. Update .env file with your database credentials\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
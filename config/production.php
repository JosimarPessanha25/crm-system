<?php
/**
 * Production Environment Configuration
 * This file contains production-specific settings for the CRM system
 */

return [
    // Application Settings
    'app' => [
        'name' => 'CRM System',
        'version' => '1.0.0',
        'environment' => 'production',
        'debug' => false,
        'timezone' => 'UTC',
        'locale' => 'en_US',
        'url' => 'https://crm.yourdomain.com',
        'api_url' => 'https://crm.yourdomain.com/api'
    ],

    // Database Configuration
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'crm_system',
                'username' => $_ENV['DB_USERNAME'] ?? 'crm_user',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ]
            ]
        ],
        'pool' => [
            'min_connections' => 5,
            'max_connections' => 20,
            'connection_timeout' => 30,
            'idle_timeout' => 600
        ]
    ],

    // Security Settings
    'security' => [
        'jwt' => [
            'secret' => $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production',
            'algorithm' => 'HS256',
            'expire_time' => 3600, // 1 hour
            'refresh_expire_time' => 604800, // 7 days
            'issuer' => 'crm-system',
            'audience' => 'crm-users'
        ],
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
            'hash_algorithm' => PASSWORD_DEFAULT,
            'hash_cost' => 12
        ],
        'session' => [
            'lifetime' => 3600, // 1 hour
            'name' => 'CRM_SESSION',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ],
        'cors' => [
            'allowed_origins' => [
                'https://crm.yourdomain.com'
            ],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'expose_headers' => ['X-Total-Count'],
            'max_age' => 86400,
            'credentials' => true
        ],
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'blacklist_threshold' => 100
        ]
    ],

    // Logging Configuration
    'logging' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/logs/app.log',
                'level' => 'info',
                'max_files' => 30,
                'max_size' => '10MB'
            ],
            'error' => [
                'driver' => 'file',
                'path' => __DIR__ . '/logs/error.log',
                'level' => 'error',
                'max_files' => 60,
                'max_size' => '50MB'
            ],
            'audit' => [
                'driver' => 'file',
                'path' => __DIR__ . '/logs/audit.log',
                'level' => 'info',
                'max_files' => 365,
                'max_size' => '100MB'
            ]
        ],
        'log_requests' => true,
        'log_queries' => false, // Disable in production for performance
        'log_api_calls' => true
    ],

    // Cache Configuration
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/cache',
                'prefix' => 'crm_'
            ],
            'redis' => [
                'driver' => 'redis',
                'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DATABASE'] ?? 0,
                'prefix' => 'crm_cache_'
            ]
        ],
        'ttl' => [
            'default' => 3600, // 1 hour
            'user_session' => 7200, // 2 hours
            'dashboard_stats' => 300, // 5 minutes
            'contact_list' => 600, // 10 minutes
            'opportunity_pipeline' => 300 // 5 minutes
        ]
    ],

    // Email Configuration
    'email' => [
        'default' => 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'timeout' => 30
            ]
        ],
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@crm.yourdomain.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'CRM System'
        ],
        'templates' => [
            'welcome' => 'emails/welcome.html',
            'password_reset' => 'emails/password-reset.html',
            'notification' => 'emails/notification.html'
        ]
    ],

    // File Upload Configuration
    'uploads' => [
        'max_size' => 10485760, // 10MB
        'allowed_extensions' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'archives' => ['zip', 'rar', '7z']
        ],
        'storage' => [
            'driver' => 'local',
            'path' => __DIR__ . '/uploads',
            'url' => '/uploads'
        ],
        'image_processing' => [
            'resize_on_upload' => true,
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'generate_thumbnails' => true,
            'thumbnail_sizes' => [
                'small' => [150, 150],
                'medium' => [300, 300],
                'large' => [600, 600]
            ]
        ]
    ],

    // Performance Settings
    'performance' => [
        'enable_compression' => true,
        'enable_minification' => true,
        'enable_caching' => true,
        'cache_static_assets' => true,
        'asset_cache_duration' => 31536000, // 1 year
        'database_query_cache' => true,
        'api_response_cache' => true,
        'pagination' => [
            'default_per_page' => 25,
            'max_per_page' => 100
        ]
    ],

    // Monitoring and Analytics
    'monitoring' => [
        'enable_metrics' => true,
        'enable_health_checks' => true,
        'health_check_interval' => 300, // 5 minutes
        'performance_monitoring' => true,
        'error_tracking' => true,
        'analytics' => [
            'track_user_activity' => true,
            'track_api_usage' => true,
            'track_performance' => true
        ]
    ],

    // API Configuration
    'api' => [
        'version' => 'v1',
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 100,
            'burst_limit' => 200
        ],
        'pagination' => [
            'default_limit' => 25,
            'max_limit' => 100
        ],
        'response_format' => 'json',
        'include_metadata' => true,
        'enable_versioning' => true,
        'documentation_url' => '/api/docs'
    ],

    // Backup Configuration
    'backup' => [
        'enabled' => true,
        'schedule' => [
            'database' => '0 2 * * *', // Daily at 2 AM
            'files' => '0 3 * * 0' // Weekly on Sunday at 3 AM
        ],
        'retention' => [
            'daily' => 7,
            'weekly' => 4,
            'monthly' => 12
        ],
        'storage' => [
            'local' => __DIR__ . '/backups',
            'remote' => [
                'enabled' => false,
                'driver' => 's3',
                'bucket' => 'crm-backups',
                'region' => 'us-east-1'
            ]
        ],
        'compression' => true,
        'encryption' => true
    ],

    // Third-party Integrations
    'integrations' => [
        'google' => [
            'maps_api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? '',
            'analytics_id' => $_ENV['GOOGLE_ANALYTICS_ID'] ?? '',
            'oauth' => [
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? ''
            ]
        ],
        'social_login' => [
            'enabled' => false,
            'providers' => ['google', 'microsoft']
        ],
        'webhooks' => [
            'enabled' => true,
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 5
        ]
    ],

    // Maintenance Mode
    'maintenance' => [
        'enabled' => false,
        'allowed_ips' => [
            '127.0.0.1',
            '::1'
        ],
        'message' => 'The system is currently under maintenance. Please try again later.',
        'retry_after' => 3600 // 1 hour
    ]
];
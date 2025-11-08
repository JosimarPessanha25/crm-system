<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * @property string $id
 * @property string $nome
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $timezone
 * @property bool $is_active
 * @property ?string $email_verified_at
 * @property ?string $remember_token
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Model
{
    protected $table = 'usuarios';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'nome',
        'email',
        'password',
        'role',
        'timezone',
        'is_active'
    ];
    
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_USER = 'user';
    public const ROLE_VIEWER = 'viewer';
    
    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_USER,
        self::ROLE_VIEWER
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }
    
    // Relationships
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'owner_id');
    }
    
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }
    
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'owner_id');
    }
    
    public function assignedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'assigned_to');
    }
    
    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by');
    }
    
    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    
    public function isManager(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }
    
    public function canManageUser(User $user): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isManager()) {
            return !$user->isAdmin();
        }
        
        return $this->id === $user->id;
    }
    
    public function hasPermission(string $permission): bool
    {
        $permissions = [
            self::ROLE_ADMIN => ['*'],
            self::ROLE_MANAGER => [
                'users.view', 'users.create', 'users.update',
                'contacts.*', 'companies.*', 'opportunities.*', 'activities.*',
                'dashboard.view', 'reports.view'
            ],
            self::ROLE_USER => [
                'contacts.*', 'companies.*', 'opportunities.own', 'activities.own',
                'dashboard.view'
            ],
            self::ROLE_VIEWER => [
                'contacts.view', 'companies.view', 'opportunities.view', 'activities.view',
                'dashboard.view'
            ]
        ];
        
        $rolePermissions = $permissions[$this->role] ?? [];
        
        if (in_array('*', $rolePermissions)) {
            return true;
        }
        
        return in_array($permission, $rolePermissions) || 
               in_array(str_replace('.*', '.*', $permission), $rolePermissions);
    }
}
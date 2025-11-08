<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Log\LoggerInterface;

/**
 * UserService - Business logic for user management
 * 
 * Handles all user-related business operations including validation,
 * data processing, and complex business rules.
 */
class UserService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * List users with pagination and filtering
     */
    public function list(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $query = User::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('telefone', 'like', $search);
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($filters['status'] === 'inactive') {
                $query->whereNotNull('deleted_at');
            }
        } else {
            // By default, only show active users
            $query->whereNull('deleted_at');
        }

        // Apply role filter
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $users = $query->offset($offset)->limit($limit)->get();

        // Transform data
        $data = $users->map(function ($user) {
            return $this->transformUserData($user);
        });

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $user = User::withTrashed()->find($id);
        
        if (!$user) {
            return null;
        }

        return $this->transformUserData($user, true);
    }

    /**
     * Create a new user
     */
    public function create(array $data): array
    {
        $this->validateUserData($data);
        
        // Check for email uniqueness
        if (User::withTrashed()->where('email', $data['email'])->exists()) {
            throw new \InvalidArgumentException('Email already exists');
        }

        // Hash password if provided
        if (!empty($data['senha'])) {
            $data['senha'] = password_hash($data['senha'], PASSWORD_ARGON2ID);
        }

        // Set default role if not provided
        if (empty($data['role'])) {
            $data['role'] = 'user';
        }

        // Set default status
        $data['ativo'] = $data['ativo'] ?? true;
        $data['email_verificado_em'] = null; // Will be set when user verifies email

        $user = User::create($data);

        $this->logger->info('User created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        return $this->transformUserData($user);
    }

    /**
     * Update an existing user
     */
    public function update(int $id, array $data): ?array
    {
        $user = User::find($id);
        
        if (!$user) {
            return null;
        }

        $this->validateUserData($data, $id);

        // Check email uniqueness if email is being changed
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            if (User::withTrashed()->where('email', $data['email'])->where('id', '!=', $id)->exists()) {
                throw new \InvalidArgumentException('Email already exists');
            }
            // Reset email verification if email changed
            $data['email_verificado_em'] = null;
        }

        // Hash password if provided
        if (!empty($data['senha'])) {
            $data['senha'] = password_hash($data['senha'], PASSWORD_ARGON2ID);
        } else {
            // Don't update password if not provided
            unset($data['senha']);
        }

        // Don't allow role changes for non-admin users (this could be enhanced with proper permission check)
        if (!empty($data['role']) && $data['role'] !== $user->role) {
            $this->logger->warning('Role change attempted', [
                'user_id' => $id,
                'old_role' => $user->role,
                'new_role' => $data['role']
            ]);
        }

        $user->update($data);

        $this->logger->info('User updated', [
            'user_id' => $user->id,
            'changes' => array_keys($data)
        ]);

        return $this->transformUserData($user->fresh());
    }

    /**
     * Soft delete a user
     */
    public function delete(int $id): bool
    {
        $user = User::find($id);
        
        if (!$user) {
            return false;
        }

        // Don't allow deletion of super admin
        if ($user->role === 'super_admin') {
            throw new \InvalidArgumentException('Cannot delete super admin user');
        }

        $user->delete();

        $this->logger->info('User deleted', [
            'user_id' => $id,
            'email' => $user->email
        ]);

        return true;
    }

    /**
     * Restore a soft-deleted user
     */
    public function restore(int $id): bool
    {
        $user = User::onlyTrashed()->find($id);
        
        if (!$user) {
            return false;
        }

        $user->restore();

        $this->logger->info('User restored', [
            'user_id' => $id,
            'email' => $user->email
        ]);

        return true;
    }

    /**
     * Get user statistics
     */
    public function getStats(int $id): ?array
    {
        $user = User::withTrashed()->find($id);
        
        if (!$user) {
            return null;
        }

        // Get related data counts
        $contactsCount = $user->contacts()->count();
        $opportunitiesCount = $user->opportunities()->count();
        $activitiesCount = $user->activities()->count();
        $completedActivitiesCount = $user->activities()->where('status', 'concluida')->count();

        // Calculate total opportunity value
        $totalOpportunityValue = $user->opportunities()->sum('valor');
        $wonOpportunityValue = $user->opportunities()->where('status', 'fechada_ganha')->sum('valor');

        // Get recent activity
        $recentActivities = $user->activities()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'titulo' => $activity->titulo,
                    'tipo' => $activity->tipo,
                    'status' => $activity->status,
                    'data_vencimento' => $activity->data_vencimento?->format('Y-m-d'),
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s')
                ];
            });

        return [
            'user_id' => $user->id,
            'performance' => [
                'contacts_count' => $contactsCount,
                'opportunities_count' => $opportunitiesCount,
                'activities_count' => $activitiesCount,
                'completed_activities_count' => $completedActivitiesCount,
                'completion_rate' => $activitiesCount > 0 ? round(($completedActivitiesCount / $activitiesCount) * 100, 2) : 0
            ],
            'revenue' => [
                'total_opportunity_value' => $totalOpportunityValue,
                'won_opportunity_value' => $wonOpportunityValue,
                'conversion_rate' => $totalOpportunityValue > 0 ? round(($wonOpportunityValue / $totalOpportunityValue) * 100, 2) : 0
            ],
            'recent_activities' => $recentActivities
        ];
    }

    /**
     * Validate user input data
     */
    private function validateUserData(array $data, ?int $userId = null): void
    {
        // Required fields for creation
        if ($userId === null) {
            if (empty($data['nome'])) {
                throw new \InvalidArgumentException('Name is required');
            }
            if (empty($data['email'])) {
                throw new \InvalidArgumentException('Email is required');
            }
        }

        // Email format validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Phone validation (basic Brazilian format)
        if (!empty($data['telefone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['telefone']);
            if (strlen($phone) < 10 || strlen($phone) > 11) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }
        }

        // Password validation
        if (!empty($data['senha'])) {
            if (strlen($data['senha']) < 8) {
                throw new \InvalidArgumentException('Password must be at least 8 characters long');
            }
            // Additional password strength validation could be added here
        }

        // Role validation
        if (!empty($data['role'])) {
            $allowedRoles = ['super_admin', 'admin', 'manager', 'user'];
            if (!in_array($data['role'], $allowedRoles)) {
                throw new \InvalidArgumentException('Invalid role specified');
            }
        }

        // Name length validation
        if (!empty($data['nome']) && strlen($data['nome']) > 255) {
            throw new \InvalidArgumentException('Name is too long');
        }
    }

    /**
     * Transform user data for API response
     */
    private function transformUserData(User $user, bool $includeDetails = false): array
    {
        $data = [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'telefone' => $user->telefone,
            'role' => $user->role,
            'ativo' => $user->ativo,
            'email_verificado' => $user->email_verificado_em !== null,
            'ultimo_login_em' => $user->ultimo_login_em?->format('Y-m-d H:i:s'),
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
        ];

        if ($user->deleted_at) {
            $data['deleted_at'] = $user->deleted_at->format('Y-m-d H:i:s');
        }

        if ($includeDetails) {
            // Add relationship counts
            $data['stats'] = [
                'contacts_count' => $user->contacts()->count(),
                'opportunities_count' => $user->opportunities()->count(),
                'activities_count' => $user->activities()->count()
            ];
        }

        return $data;
    }

    /**
     * Search users by email or name
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $searchTerm = '%' . $query . '%';
        
        return User::where('nome', 'like', $searchTerm)
            ->orWhere('email', 'like', $searchTerm)
            ->limit($limit)
            ->get();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection
    {
        return User::where('role', $role)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): void
    {
        User::where('id', $userId)->update([
            'ultimo_login_em' => now()
        ]);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $user->update(['email_verificado_em' => now()]);
        
        $this->logger->info('Email verified', [
            'user_id' => $userId,
            'email' => $user->email
        ]);

        return true;
    }
}
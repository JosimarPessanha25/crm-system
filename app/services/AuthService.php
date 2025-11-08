<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Helpers\JwtHelper;
use Exception;
use InvalidArgumentException;

class AuthService
{
    private JwtHelper $jwtHelper;

    public function __construct(JwtHelper $jwtHelper)
    {
        $this->jwtHelper = $jwtHelper;
    }

    /**
     * Authenticate user with email and password
     */
    public function login(string $email, string $password): array
    {
        // Validate input
        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException('Email and password are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Find user by email
        $user = User::where('email', $email)
            ->where('ativo', true)
            ->first();

        if (!$user) {
            throw new Exception('Invalid credentials', 401);
        }

        // Verify password
        if (!password_verify($password, $user->senha)) {
            // Update failed login attempts
            $user->increment('tentativas_login');
            
            if ($user->tentativas_login >= 5) {
                $user->bloqueado_até = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $user->save();
                throw new Exception('Account temporarily blocked due to multiple failed attempts', 423);
            }
            
            $user->save();
            throw new Exception('Invalid credentials', 401);
        }

        // Check if account is blocked
        if ($user->bloqueado_até && strtotime($user->bloqueado_até) > time()) {
            throw new Exception('Account is temporarily blocked', 423);
        }

        // Reset failed attempts on successful login
        $user->tentativas_login = 0;
        $user->bloqueado_até = null;
        $user->ultimo_login = date('Y-m-d H:i:s');
        $user->save();

        // Prepare user data for token
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->nome,
            'role' => $user->role,
            'permissions' => $user->getPermissions()
        ];

        // Generate tokens
        $tokens = $this->jwtHelper->generateTokenPair($userData);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'ultimo_login' => $user->ultimo_login,
                'permissions' => $user->getPermissions()
            ],
            'tokens' => $tokens
        ];
    }

    /**
     * Register new user
     */
    public function register(array $userData): array
    {
        // Validate required fields
        $requiredFields = ['nome', 'email', 'senha'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Check if email already exists
        if (User::where('email', $userData['email'])->exists()) {
            throw new Exception('Email already registered', 409);
        }

        // Validate password strength
        if (!$this->isPasswordStrong($userData['senha'])) {
            throw new InvalidArgumentException(
                'Password must be at least 8 characters with uppercase, lowercase, number and special character'
            );
        }

        // Create user
        $user = new User();
        $user->fill([
            'nome' => $userData['nome'],
            'email' => $userData['email'],
            'senha' => password_hash($userData['senha'], PASSWORD_ARGON2ID),
            'role' => $userData['role'] ?? 'user',
            'ativo' => true,
            'telefone' => $userData['telefone'] ?? null,
            'avatar' => $userData['avatar'] ?? null,
            'criado_em' => date('Y-m-d H:i:s')
        ]);

        if (!$user->save()) {
            throw new Exception('Failed to create user account', 500);
        }

        // Prepare user data for token
        $tokenUserData = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->nome,
            'role' => $user->role,
            'permissions' => $user->getPermissions()
        ];

        // Generate tokens
        $tokens = $this->jwtHelper->generateTokenPair($tokenUserData);

        return [
            'success' => true,
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'permissions' => $user->getPermissions()
            ],
            'tokens' => $tokens
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            // Validate refresh token
            $userId = $this->jwtHelper->validateRefreshToken($refreshToken);
            
            // Find user
            $user = User::where('id', $userId)
                ->where('ativo', true)
                ->first();

            if (!$user) {
                throw new Exception('User not found or inactive', 404);
            }

            // Prepare user data
            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->nome,
                'role' => $user->role,
                'permissions' => $user->getPermissions()
            ];

            // Generate new tokens
            $tokens = $this->jwtHelper->generateTokenPair($userData);

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            throw new Exception('Invalid or expired refresh token', 401);
        }
    }

    /**
     * Logout user (invalidate tokens)
     */
    public function logout(string $userId): array
    {
        // In a more robust implementation, we would maintain a blacklist of tokens
        // For now, we'll just update the user's last logout time
        
        $user = User::find($userId);
        if ($user) {
            $user->ultimo_logout = date('Y-m-d H:i:s');
            $user->save();
        }

        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }

    /**
     * Change user password
     */
    public function changePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->senha)) {
            throw new Exception('Current password is incorrect', 400);
        }

        // Validate new password
        if (!$this->isPasswordStrong($newPassword)) {
            throw new InvalidArgumentException(
                'New password must be at least 8 characters with uppercase, lowercase, number and special character'
            );
        }

        // Update password
        $user->senha = password_hash($newPassword, PASSWORD_ARGON2ID);
        $user->senha_alterada_em = date('Y-m-d H:i:s');
        $user->save();

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

    /**
     * Request password reset token
     */
    public function requestPasswordReset(string $email): array
    {
        $user = User::where('email', $email)
            ->where('ativo', true)
            ->first();

        if (!$user) {
            // Don't reveal if email exists for security
            return [
                'success' => true,
                'message' => 'If email exists, reset instructions will be sent'
            ];
        }

        // Generate reset token
        $resetToken = $this->jwtHelper->generatePasswordResetToken($user->id, $user->email);

        // In a real implementation, send email with reset link
        // For now, we'll just return the token for testing
        
        return [
            'success' => true,
            'message' => 'Password reset token generated',
            'reset_token' => $resetToken // Remove in production
        ];
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        try {
            // Validate reset token
            $tokenData = $this->jwtHelper->validatePasswordResetToken($token);
            
            $user = User::where('id', $tokenData['user_id'])
                ->where('email', $tokenData['email'])
                ->where('ativo', true)
                ->first();

            if (!$user) {
                throw new Exception('Invalid reset token', 400);
            }

            // Validate new password
            if (!$this->isPasswordStrong($newPassword)) {
                throw new InvalidArgumentException(
                    'Password must be at least 8 characters with uppercase, lowercase, number and special character'
                );
            }

            // Update password
            $user->senha = password_hash($newPassword, PASSWORD_ARGON2ID);
            $user->senha_alterada_em = date('Y-m-d H:i:s');
            $user->tentativas_login = 0;
            $user->bloqueado_até = null;
            $user->save();

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];

        } catch (Exception $e) {
            throw new Exception('Invalid or expired reset token', 400);
        }
    }

    /**
     * Validate password strength
     */
    private function isPasswordStrong(string $password): bool
    {
        // At least 8 characters
        if (strlen($password) < 8) {
            return false;
        }

        // Must contain uppercase, lowercase, number and special character
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password) === 1;
    }

    /**
     * Get user profile information
     */
    public function getProfile(string $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'role' => $user->role,
                'telefone' => $user->telefone,
                'avatar' => $user->avatar,
                'ultimo_login' => $user->ultimo_login,
                'criado_em' => $user->criado_em,
                'permissions' => $user->getPermissions()
            ]
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(string $userId, array $updateData): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        // Only allow certain fields to be updated
        $allowedFields = ['nome', 'telefone', 'avatar'];
        $updateFields = array_intersect_key($updateData, array_flip($allowedFields));

        if (empty($updateFields)) {
            throw new InvalidArgumentException('No valid fields provided for update');
        }

        $user->fill($updateFields);
        $user->save();

        return [
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'role' => $user->role,
                'telefone' => $user->telefone,
                'avatar' => $user->avatar,
                'permissions' => $user->getPermissions()
            ]
        ];
    }
}
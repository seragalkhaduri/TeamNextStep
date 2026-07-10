<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Models\RefreshToken;
use App\Domain\Auth\Models\User;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * AuthService — all auth business logic (SDD §3.1, §6.2).
 *
 * Handles: login, lockout, refresh tokens, password reset, audit logging.
 * Controllers delegate here (Architecture Rule §2.2 — thin controllers).
 */
class AuthService extends BaseService
{
    // ─── Login ───────────────────────────────────────────────────────

    /**
     * Authenticate a user and issue access + refresh tokens.
     *
     * @return array{accessToken: string, refreshToken: string, user: User}
     * @throws \App\Domain\Auth\Exceptions\AccountLockedException
     * @throws \App\Domain\Auth\Exceptions\InvalidCredentialsException
     */
    public function login(string $username, string $password): array
    {
        $user = User::where('username', $username)
                    ->whereNull('deleted_at')
                    ->first();

        // Check if user exists
        if (!$user) {
            $this->auditLoginFailure($username);
            throw new \App\Domain\Auth\Exceptions\InvalidCredentialsException();
        }

        // Check if account is locked (SDD §6.2: 5 failed attempts → 15-min lock)
        if ($user->isLocked()) {
            $this->auditLoginFailure($username, $user->id);
            throw new \App\Domain\Auth\Exceptions\AccountLockedException(
                $user->locked_until
            );
        }

        // Check if account is active
        if (!$user->is_active) {
            $this->auditLoginFailure($username, $user->id);
            throw new \App\Domain\Auth\Exceptions\InvalidCredentialsException();
        }

        // Verify password
        if (!Hash::check($password, $user->password_hash)) {
            $this->handleFailedLogin($user);
            $this->auditLoginFailure($username, $user->id);
            throw new \App\Domain\Auth\Exceptions\InvalidCredentialsException();
        }

        // Success — reset failed login count and issue tokens
        $this->resetFailedLoginCount($user);

        $accessToken = $this->issueAccessToken($user);
        $refreshToken = $this->issueRefreshToken($user);

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Audit successful login
        $this->auditLogin($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => $user,
        ];
    }

    // ─── Lockout Logic (SDD §6.2) ───────────────────────────────────

    /**
     * Handle a failed login attempt.
     * After 5 failures within 15 minutes → lock for 15 minutes.
     */
    protected function handleFailedLogin(User $user): void
    {
        $maxAttempts = (int) config('uimp.lockout.attempts', 5);
        $lockoutDuration = (int) config('uimp.lockout.duration_minutes', 15);

        $user->increment('failed_login_count');

        if ($user->failed_login_count >= $maxAttempts) {
            $user->update([
                'locked_until' => now()->addMinutes($lockoutDuration),
                'failed_login_count' => 0, // Reset counter after locking
            ]);
        }
    }

    /**
     * Reset the failed login counter on successful authentication.
     */
    protected function resetFailedLoginCount(User $user): void
    {
        if ($user->failed_login_count > 0 || $user->locked_until !== null) {
            $user->update([
                'failed_login_count' => 0,
                'locked_until' => null,
            ]);
        }
    }

    // ─── Access Token (Sanctum, 15-min) ──────────────────────────────

    /**
     * Issue a 15-minute Sanctum access token.
     */
    protected function issueAccessToken(User $user): string
    {
        // Delete any existing tokens for this user (single active session per SDD)
        $user->tokens()->delete();

        $expiresAt = now()->addMinutes(
            (int) config('sanctum.expiration', 15)
        );

        $token = $user->createToken(
            'access-token',
            ['*'], // abilities — can be scoped per role later
            $expiresAt
        );

        return $token->plainTextToken;
    }

    // ─── Refresh Token (Custom, 7-day) ───────────────────────────────

    /**
     * Issue a 7-day refresh token.
     * Returns the plain-text token (shown once, then only the hash is stored).
     */
    protected function issueRefreshToken(User $user): string
    {
        // Revoke any existing refresh tokens for this user
        RefreshToken::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);

        $plainToken = Str::random(64); // 256-bit random token
        $expiryDays = (int) config('uimp.refresh_token_days', 7);

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addDays($expiryDays),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        return $plainToken;
    }

    /**
     * Exchange a valid refresh token for a new access token.
     *
     * @return array{accessToken: string, refreshToken: string}
     * @throws \App\Domain\Auth\Exceptions\InvalidRefreshTokenException
     */
    public function refresh(string $refreshTokenPlain): array
    {
        // Find valid (non-revoked, non-expired) refresh tokens
        $validTokens = RefreshToken::valid()
            ->with('user')
            ->get();

        $matchedToken = null;
        foreach ($validTokens as $token) {
            if (Hash::check($refreshTokenPlain, $token->token_hash)) {
                $matchedToken = $token;
                break;
            }
        }

        if (!$matchedToken || !$matchedToken->user || !$matchedToken->user->is_active) {
            throw new \App\Domain\Auth\Exceptions\InvalidRefreshTokenException();
        }

        // Revoke the used token (single-use)
        $matchedToken->revoke();

        $user = $matchedToken->user;

        // Issue new tokens
        $accessToken = $this->issueAccessToken($user);
        $newRefreshToken = $this->issueRefreshToken($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $newRefreshToken,
        ];
    }

    // ─── Logout ──────────────────────────────────────────────────────

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function logout(User $user): void
    {
        // Revoke Sanctum access tokens
        $user->tokens()->delete();

        // Revoke refresh tokens
        RefreshToken::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);

        // Audit logout
        AuditLog::create([
            'event_type' => 'AUTHENTICATION',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action' => 'LOGOUT',
            'actor_user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    // ─── Password Reset (SDD §6.2) ──────────────────────────────────

    /**
     * Generate a password reset token.
     * 256-bit random, single-use, 1-hour expiry.
     */
    public function requestPasswordReset(string $email): ?string
    {
        $user = User::where('email', $email)
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->first();

        if (!$user) {
            // Don't reveal whether the email exists (security best practice)
            return null;
        }

        $token = bin2hex(random_bytes(32)); // 256-bit random token
        $expiryMinutes = (int) config('uimp.password_reset_expiry_minutes', 60);

        $user->update([
            'password_reset_token' => hash('sha256', $token), // Store hashed
            'password_reset_expires' => now()->addMinutes($expiryMinutes),
        ]);

        // Audit
        AuditLog::create([
            'event_type' => 'AUTHENTICATION',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action' => 'PASSWORD_RESET',
            'actor_user_id' => $user->id,
            'new_value' => ['email' => $email],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        return $token; // Return plain token to send via email/SMS
    }

    /**
     * Reset password using a valid token.
     *
     * @throws \App\Domain\Auth\Exceptions\InvalidResetTokenException
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $hashedToken = hash('sha256', $token);

        $user = User::where('password_reset_token', $hashedToken)
                    ->where('password_reset_expires', '>', now())
                    ->whereNull('deleted_at')
                    ->first();

        if (!$user) {
            throw new \App\Domain\Auth\Exceptions\InvalidResetTokenException();
        }

        $user->update([
            'password_hash' => Hash::make($newPassword),
            'password_reset_token' => null,     // Invalidate immediately (single-use)
            'password_reset_expires' => null,
            'failed_login_count' => 0,          // Reset lockout on password change
            'locked_until' => null,
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->update(['is_revoked' => true]);
    }

    // ─── Role Management ─────────────────────────────────────────────

    /**
     * Update roles for a user (admin-only, SDD §7).
     *
     * @param array $roleIds
     */
    public function updateUserRoles(User $user, array $roleIds): User
    {
        $user->syncRoles(
            \Spatie\Permission\Models\Role::whereIn('id', $roleIds)->pluck('name')->toArray()
        );

        // Audit role assignment
        AuditLog::create([
            'event_type' => 'USER_MANAGEMENT',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action' => 'ROLE_ASSIGNED',
            'actor_user_id' => auth()->id(),
            'new_value' => ['role_ids' => $roleIds],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        return $user->load('roles');
    }

    // ─── Audit Helpers ───────────────────────────────────────────────

    protected function auditLogin(User $user): void
    {
        AuditLog::create([
            'event_type' => 'AUTHENTICATION',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action' => 'LOGIN',
            'actor_user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function auditLoginFailure(string $username, ?string $userId = null): void
    {
        AuditLog::create([
            'event_type' => 'AUTHENTICATION',
            'entity_type' => 'User',
            'entity_id' => $userId,
            'action' => 'LOGIN_FAILED',
            'actor_user_id' => $userId,
            'new_value' => ['username' => $username],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}

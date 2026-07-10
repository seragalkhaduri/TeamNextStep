<?php

namespace App\Domain\Auth\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model (SDD §4.2 — users table).
 *
 * Domain: Auth
 * UUID primary keys, soft deletes, RBAC via Spatie, API tokens via Sanctum.
 * Full implementation in Phase 2; this establishes the model location and traits.
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;
    use Auditable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'preferred_language',
        'is_active',
        'failed_login_count',
        'locked_until',
        'last_login_at',
        'password_reset_token',
        'password_reset_expires',
    ];

    protected $hidden = [
        'password_hash',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'failed_login_count' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_reset_expires' => 'datetime',
            'preferred_language' => 'string',
        ];
    }

    /**
     * Get the password for authentication.
     * Laravel expects getAuthPassword() — we use password_hash column.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Check if the account is currently locked (SDD §6.2).
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Audit event type override.
     */
    protected static function getAuditEventType(): string
    {
        return 'USER_MANAGEMENT';
    }
}

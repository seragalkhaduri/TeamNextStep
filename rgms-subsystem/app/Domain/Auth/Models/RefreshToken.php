<?php

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * RefreshToken model (SDD §6.2).
 *
 * Custom refresh token mechanism since Sanctum doesn't have native refresh tokens.
 * Tokens are bcrypt-hashed and single-use.
 */
class RefreshToken extends Model
{
    use HasUuids;

    protected $table = 'refresh_tokens';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'is_revoked',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_revoked' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Query Scopes ────────────────────────────────────────────────

    public function scopeValid($query)
    {
        return $query->where('is_revoked', false)
                     ->where('expires_at', '>', now());
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->is_revoked;
    }

    public function revoke(): void
    {
        $this->update(['is_revoked' => true]);
    }
}

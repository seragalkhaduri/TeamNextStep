<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * UimpAuthenticatable
 *
 * Stateless Authenticatable wrapper around decoded UIMP JWT claims.
 * RGMS has no local users table — authentication is delegated
 * entirely to UIMP (master architectural constraint). This object is
 * bound via Auth::setUser() by UimpJwtMiddleware on every
 * authenticated request and is never persisted or queried.
 *
 * SDD Reference: RGMS SDD §3.14.4
 *
 * @property-read string $id UIMP user UUID (JWT 'sub' claim)
 * @property-read list<string> $roles JWT 'roles[]' claim
 * @property-read string|null $primaryRole First role in roles[], used
 *                for AuditLog::record()'s user_role column
 * @property-read string|null $preferredLanguage JWT 'preferred_language'
 *                claim ('ar' | 'en')
 */
final class UimpAuthenticatable implements Authenticatable
{
    public readonly string $id;

    /**
     * @var list<string>
     */
    public readonly array $roles;

    public readonly ?string $primaryRole;

    public readonly ?string $preferredLanguage;

    /**
     * @param array<string, mixed> $claims Decoded JWT claims.
     */
    public function __construct(array $claims)
    {
        $this->id = (string) ($claims['sub'] ?? '');
        $this->roles = array_values((array) ($claims['roles'] ?? []));
        $this->primaryRole = $this->roles[0] ?? null;
        $this->preferredLanguage = $claims['preferred_language'] ?? null;
    }

    public function getAuthIdentifierName(): string
    {
        return 'sub';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    /**
     * RGMS never authenticates locally — no password hash exists.
     */
    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // No-op: RGMS delegates all session/token lifecycle to UIMP.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
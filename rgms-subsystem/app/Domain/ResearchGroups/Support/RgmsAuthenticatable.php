<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Support;

use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * RgmsAuthenticatable
 *
 * Adapter that wraps a UIMP User (Eloquent model authenticated via Sanctum)
 * and exposes the same interface that RGMS Policies, Services and Repositories
 * previously expected from the old JWT-based UimpAuthenticatable.
 *
 * Since UIMP and RGMS are now one application, we read roles directly from
 * Spatie\Permission rather than from JWT claims.
 *
 * @property-read string      $id               UIMP user UUID
 * @property-read list<string> $roles           Spatie role names for this user
 * @property-read string|null  $primaryRole     First role, used by AuditLog
 * @property-read string|null  $preferredLanguage User's preferred locale
 */
final class RgmsAuthenticatable implements Authenticatable
{
    public readonly string $id;

    /** @var list<string> */
    public readonly array $roles;

    public readonly ?string $primaryRole;

    public readonly ?string $preferredLanguage;

    public function __construct(User $user)
    {
        $this->id = (string) $user->id;
        $this->roles = $user->getRoleNames()->values()->all();
        $this->primaryRole = $this->roles[0] ?? null;
        $this->preferredLanguage = $user->preferred_language ?? 'en';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

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
        // No-op: session lifecycle managed by UIMP/Sanctum.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}

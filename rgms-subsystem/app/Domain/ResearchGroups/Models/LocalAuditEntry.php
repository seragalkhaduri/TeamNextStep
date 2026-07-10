<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LocalAuditEntry
 *
 * Immutable, INSERT-ONLY local audit trail entry (SDD §4.2.13).
 * Written synchronously by AuditLog::record() before the same event
 * is dispatched asynchronously to the UIMP central Audit Service.
 *
 * Application DB user has UPDATE/DELETE/TRUNCATE revoked on this
 * table at the database level — no application-layer guard is
 * substituted for that here.
 *
 * SDD Reference: RGMS SDD §3.14.3, §4.2.13
 *
 * @property string $id
 * @property string $action
 * @property string $entity_type
 * @property string|null $entity_id
 * @property array|null $old_value
 * @property array|null $new_value
 * @property string $user_id
 * @property string|null $user_role
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $recorded_at
 */
final class LocalAuditEntry extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Allowed action ENUM values (SDD §4.2.13).
     */
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_TRANSITION = 'TRANSITION';
    public const ACTION_ACCESS = 'ACCESS';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_LOGIN = 'LOGIN';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'local_audit_log_rgms';

    /**
     * The primary key type (UUID via HasUuids trait).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates the primary key is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * This table has a single recorded_at timestamp — not the
     * standard created_at/updated_at pair — and it is always
     * populated by the database DEFAULT CURRENT_TIMESTAMP, never
     * by the application (SDD §4.2.13: "never client-provided").
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'old_value',
        'new_value',
        'user_id',
        'user_role',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
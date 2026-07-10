<?php

namespace App\Domain\Audit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use App\Domain\Subsystems\Jobs\WebhookDispatchJob;

/**
 * AuditLog model — immutable, append-only (SDD §3.4, FR-AUD-002).
 *
 * Immutability enforced at TWO levels:
 * 1. Database level: restricted DB user with INSERT/SELECT only on audit_logs
 * 2. Application level: update()/delete() throw exceptions unconditionally
 *
 * This model has NO updated_at, NO soft deletes — it is insert-only.
 */
class AuditLog extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::created(function (AuditLog $log) {
            if ($log->event_type === 'DATA_CHANGE') {
                WebhookDispatchJob::dispatch(
                    $log->id,
                    $log->event_type,
                    $log->entity_type,
                    $log->entity_id ?? '',
                    $log->action,
                    $log->new_value,
                    $log->old_value,
                    $log->actor_user_id,
                    $log->actor_subsystem_id,
                    $log->created_at->toIso8601String()
                );
            }
        });
    }

    protected $table = 'audit_logs';

    /**
     * Disable updated_at — this table is append-only.
     */
    const UPDATED_AT = null;

    public $timestamps = true; // Only created_at is used

    protected $fillable = [
        'event_type',
        'entity_type',
        'entity_id',
        'action',
        'actor_user_id',
        'actor_subsystem_id',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'record_hash',
        'prev_record_hash',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * IMMUTABILITY ENFORCEMENT (Application level — defense in depth).
     * Updates are NEVER allowed on audit logs.
     *
     * @throws \RuntimeException
     */
    public function update(array $attributes = [], array $options = []): never
    {
        throw new \RuntimeException(
            'AuditLog records are immutable. UPDATE operations are forbidden (SDD §3.4, FR-AUD-002).'
        );
    }

    /**
     * IMMUTABILITY ENFORCEMENT (Application level — defense in depth).
     * Deletes are NEVER allowed on audit logs.
     *
     * @throws \RuntimeException
     */
    public function delete(): never
    {
        throw new \RuntimeException(
            'AuditLog records are immutable. DELETE operations are forbidden (SDD §3.4, FR-AUD-002).'
        );
    }

    /**
     * Also prevent force delete.
     *
     * @throws \RuntimeException
     */
    public function forceDelete(): never
    {
        throw new \RuntimeException(
            'AuditLog records are immutable. DELETE operations are forbidden (SDD §3.4, FR-AUD-002).'
        );
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function actorUser()
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class, 'actor_user_id');
    }

    public function actorSubsystem()
    {
        return $this->belongsTo(\App\Domain\Subsystems\Models\Subsystem::class, 'actor_subsystem_id');
    }
}

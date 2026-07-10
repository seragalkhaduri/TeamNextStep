<?php

namespace App\Domain\Audit\Traits;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait Auditable
 *
 * Attach to any Eloquent model to automatically log CREATE, UPDATE, DELETE
 * events to the audit_logs table (SDD §3.4, FR-AUD-001).
 *
 * Every write to a shared entity is audited — no exceptions (Architecture Rule §2.3).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::logAuditEvent($model, 'CREATE');
        });

        static::updated(function (Model $model) {
            static::logAuditEvent($model, 'UPDATE');
        });

        static::deleted(function (Model $model) {
            static::logAuditEvent($model, 'DELETE');
        });
    }

    protected static function logAuditEvent(Model $model, string $action): void
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'UPDATE') {
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();
            // Remove timestamps from audit diff — they add noise
            unset($oldValues['updated_at'], $newValues['updated_at']);
            if (empty($newValues)) {
                return; // No meaningful changes
            }
        } elseif ($action === 'CREATE') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'DELETE') {
            $oldValues = $model->getOriginal();
        }

        // Remove sensitive fields from audit data
        $sensitiveFields = ['password_hash', 'password', 'api_key_hash', 'password_reset_token'];
        foreach ($sensitiveFields as $field) {
            if ($oldValues && isset($oldValues[$field])) {
                $oldValues[$field] = '[REDACTED]';
            }
            if ($newValues && isset($newValues[$field])) {
                $newValues[$field] = '[REDACTED]';
            }
        }

        // Determine the actor (user or subsystem)
        $actorUserId = null;
        $actorSubsystemId = null;

        if (Auth::check()) {
            $actorUserId = Auth::id();
        }

        // If the request has a subsystem context (set by subsystem auth middleware)
        if (request()->attributes->has('authenticated_subsystem')) {
            $actorSubsystemId = request()->attributes->get('authenticated_subsystem')->id;
        } elseif (Request::has('_subsystem_id')) {
            $actorSubsystemId = Request::input('_subsystem_id');
        }

        AuditLog::create([
            'event_type' => static::getAuditEventType(),
            'entity_type' => static::getAuditEntityType(),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'actor_user_id' => $actorUserId,
            'actor_subsystem_id' => $actorSubsystemId,
            'old_value' => $oldValues,
            'new_value' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Override in the model to customize the event type string.
     */
    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    /**
     * Override in the model to customize the entity type string.
     * Defaults to the short class name.
     */
    protected static function getAuditEntityType(): string
    {
        return class_basename(static::class);
    }
}

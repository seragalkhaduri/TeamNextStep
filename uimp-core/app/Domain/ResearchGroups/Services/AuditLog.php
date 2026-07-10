<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Jobs\SendUimpAuditEvent;
use App\Domain\ResearchGroups\Models\LocalAuditEntry;
use Illuminate\Support\Facades\Auth;

/**
 * AuditLog
 *
 * Static helper invoked from every Service class mutating method.
 * Writes synchronously to local_audit_log_rgms first — ensuring the
 * local record is never lost even if UIMP is unreachable — then
 * dispatches an asynchronous queued job to forward the same event to
 * the UIMP central Audit Logging Service.
 *
 * SDD Reference: RGMS SDD §3.14.3
 */
final class AuditLog
{
    /**
     * Record an audit event locally and queue it for forwarding to
     * UIMP.
     *
     * @param array<string, mixed>|null $oldValue
     * @param array<string, mixed>|null $newValue
     */
    public static function record(
        string $action,
        string $entityType,
        string $entityId,
        ?array $oldValue,
        ?array $newValue,
    ): void {
        // Step 1: write synchronously to the local RGMS audit table.
        LocalAuditEntry::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => (string) Auth::id(),
            'user_role' => Auth::user()?->primaryRole,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Step 2: dispatch async job to forward the event to UIMP.
        dispatch(new SendUimpAuditEvent([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'actor_id' => (string) Auth::id(),
            'ip_address' => request()->ip(),
            'occurred_at' => now()->toIso8601String(),
        ]));
    }
}
<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Jobs;

use App\Domain\ResearchGroups\Services\Clients\UimpAuditClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SendUimpAuditEvent
 *
 * Forwards a single RGMS audit event (already persisted locally in
 * local_audit_log_rgms by AuditLog::record()) to UIMP's central
 * Audit Logging Service. Runs on the low-priority uimp-audit queue —
 * eventual delivery is acceptable (SDD §3.14.6).
 *
 * SDD Reference: RGMS SDD §3.14.6
 */
final class SendUimpAuditEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The queue this job is dispatched on.
     *
     * @var string
     */
    public string $queue = 'uimp-audit';

    /**
     * The number of times the job may be attempted before being
     * placed on the failed_jobs table (SDD §3.14.6: retried up to 3
     * times with exponential backoff).
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * @param array<string, mixed> $event
     */
    public function __construct(
        private readonly array $event,
    ) {
    }

    /**
     * Calculate the number of seconds to wait before retrying the
     * job, per attempt (exponential backoff).
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    /**
     * Execute the job.
     */
    public function handle(UimpAuditClient $auditClient): void
    {
        $auditClient->submitAuditEvent($this->event);
    }
}
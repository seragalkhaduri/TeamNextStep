<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Jobs;

use App\Domain\ResearchGroups\Services\Clients\UimpNotificationClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SendUimpNotification
 *
 * Dispatches a notification to one or more UIMP recipients via
 * UimpNotificationClient. Runs on the medium-priority
 * uimp-notifications queue — should deliver within 30 seconds
 * (SDD §3.14.6).
 *
 * Channel default (not specified in SDD, documented assumption):
 * every event is sent via both In-App and Email. Adjust per-event
 * channel selection here if a future requirement specifies otherwise.
 *
 * SDD Reference: RGMS SDD §3.14.6
 */
final class SendUimpNotification implements ShouldQueue
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
    public string $queue = 'uimp-notifications';

    /**
     * The number of times the job may be attempted before being
     * placed on the failed_jobs table (SDD §3.14.6: retried up to 3
     * times with exponential backoff).
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * @param list<string> $recipients UIMP user IDs.
     * @param array<string, mixed> $context Template context variables.
     */
    public function __construct(
        private readonly array $recipients,
        private readonly string $eventKey,
        private readonly array $context = [],
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
        return [5, 15, 45];
    }

    /**
     * Execute the job.
     */
    public function handle(UimpNotificationClient $notificationClient): void
    {
        foreach ($this->recipients as $recipientUimpId) {
            $notificationClient->sendInApp($recipientUimpId, $this->eventKey, $this->context);
            $notificationClient->sendEmail($recipientUimpId, $this->eventKey, $this->context);
        }
    }
}
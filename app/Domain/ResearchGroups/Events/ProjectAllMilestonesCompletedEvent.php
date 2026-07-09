<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ProjectAllMilestonesCompletedEvent
 *
 * Dispatched by MilestoneService::complete() when completing a
 * milestone results in every milestone on its parent project being
 * Completed. Decouples MilestoneService from ProjectService — a
 * listener (registered in EventServiceProvider) is responsible for
 * acting on this signal (e.g. notifying the PI that the project may
 * be ready for a Completed status transition).
 *
 * SDD Reference: RGMS SDD §3.4.6
 */
final class ProjectAllMilestonesCompletedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $projectId,
    ) {
    }
}
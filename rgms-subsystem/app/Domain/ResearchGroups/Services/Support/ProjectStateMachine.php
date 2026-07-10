<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Support;

use App\Domain\ResearchGroups\Exceptions\InvalidStateTransitionException;

/**
 * ProjectStateMachine
 *
 * Enforces the research_projects lifecycle transition rules
 * (FR-PROJ-002): Planning -> Active -> On-Hold -> Completed ->
 * Terminated. Completed and Terminated are terminal states.
 *
 * SDD Reference: RGMS SDD §3.3.7
 */
final class ProjectStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'Planning' => ['Active', 'Terminated'],
        'Active' => ['On-Hold', 'Completed', 'Terminated'],
        'On-Hold' => ['Active', 'Terminated'],
        'Completed' => [], // terminal state
        'Terminated' => [], // terminal state
    ];

    /**
     * Validate that a transition from $from to $to is permitted.
     *
     * @throws InvalidStateTransitionException if the transition is not permitted.
     */
    public function validateTransition(string $from, string $to): void
    {
        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new InvalidStateTransitionException(
                "Transition from {$from} to {$to} is not permitted.",
                from: $from,
                to: $to,
            );
        }
    }
}
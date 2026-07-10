<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Models\ResearchProject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidMilestoneDateRange
 *
 * Verifies that a milestone's due_date falls within its parent
 * project's start_date/end_date window (inclusive).
 *
 * SDD Reference: RGMS SDD §3.4.5, §3.4.7
 */
final class ValidMilestoneDateRange implements ValidationRule
{
    public function __construct(
        private readonly ResearchProject $project,
    ) {
    }

    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dueDate = is_string($value) ? \Illuminate\Support\Carbon::parse($value) : null;

        if ($dueDate === null) {
            $fail('The :attribute must be a valid date.');

            return;
        }

        if ($dueDate->lt($this->project->start_date)) {
            $fail('The :attribute must be on or after the project start date.');

            return;
        }

        if ($dueDate->gt($this->project->end_date)) {
            $fail('The :attribute must be on or before the project end date.');
        }
    }
}
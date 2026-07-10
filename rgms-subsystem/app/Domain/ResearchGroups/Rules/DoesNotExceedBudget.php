<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Rules;

use App\Domain\ResearchGroups\Models\BudgetExpenditure;
use App\Domain\ResearchGroups\Models\ResearchProject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * DoesNotExceedBudget
 *
 * Verifies that a new expenditure amount does not exceed the
 * project's remaining budget balance (project.budget minus the sum
 * of all prior expenditures against it), unless override_authorization
 * is true and the requesting user holds the research_admin role
 * (FR-FUND-006).
 *
 * SDD Reference: RGMS SDD §3.5.5, §3.5.6
 */
final class DoesNotExceedBudget implements ValidationRule
{
    public function __construct(
        private readonly ResearchProject $project,
        private readonly bool $override,
    ) {
    }

    /**
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $amount = is_numeric($value) ? (float) $value : null;

        if ($amount === null) {
            $fail('The :attribute must be a valid amount.');

            return;
        }

        $spent = (float) BudgetExpenditure::query()
            ->where('project_id', $this->project->id)
            ->sum('amount');

        $remaining = (float) $this->project->budget - $spent;

        if ($amount <= $remaining) {
            return;
        }

        $isAuthorizedOverride = $this->override && in_array('research_admin', Auth::user()->roles ?? [], true);

        if (! $isAuthorizedOverride) {
            $fail(sprintf(
                'The :attribute (%.2f) exceeds the project\'s remaining budget balance (%.2f).',
                $amount,
                $remaining,
            ));
        }
    }
}
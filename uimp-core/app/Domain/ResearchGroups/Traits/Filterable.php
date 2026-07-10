<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filterable
 *
 * Provides a generic filter() query scope consumed by
 * BaseRepository::paginate() (SDD §3.14.1). Each model using this
 * trait must declare a protected array $filterable listing the only
 * columns that may be filtered on — any request key not present in
 * that whitelist is silently ignored, preventing arbitrary-column
 * filtering from unvalidated request input.
 *
 * Example (in a Model):
 *
 *     protected array $filterable = ['status', 'research_field'];
 *
 * SDD Reference: RGMS SDD §3.14.1
 */
trait Filterable
{
    /**
     * Scope a query to apply only the whitelisted filters present in
     * $filters. Scalar values are matched with an exact WHERE
     * equality; array values are matched with WHERE IN.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $whitelist = $this->filterable ?? [];

        foreach ($filters as $column => $value) {
            if (! in_array($column, $whitelist, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);

                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }
}
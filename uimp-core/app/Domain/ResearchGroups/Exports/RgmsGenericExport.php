<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * RgmsGenericExport
 *
 * Parameterized Maatwebsite/Excel export shared across all report
 * types. Column headings are derived automatically from each row's
 * serialized attribute keys (via Eloquent's toArray()), since the SDD
 * does not define a distinct column layout per report type (SDD
 * §3.12.5, §3.12.6).
 *
 * SDD Reference: RGMS SDD §3.12.5
 */
final class RgmsGenericExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Collection $data,
        private readonly string $reportType,
    ) {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->data->map(fn ($row): array => is_array($row) ? $row : $row->toArray());
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        $first = $this->data->first();

        if ($first === null) {
            return [];
        }

        $row = is_array($first) ? $first : $first->toArray();

        return array_keys($row);
    }
}
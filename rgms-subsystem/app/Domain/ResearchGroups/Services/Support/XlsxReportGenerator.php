<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Support;

use App\Domain\ResearchGroups\Exports\RgmsGenericExport;
use App\Domain\ResearchGroups\Repositories\ReportRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * XlsxReportGenerator
 *
 * Generates XLSX reports via Maatwebsite/Excel. SDD §3.12.5
 * specifies "a dedicated Export class per report type" — implemented
 * here via a single parameterized RgmsGenericExport (constructed with
 * the report's column map) rather than seven near-identical Export
 * classes, per DRY (master architectural rule); each report type
 * still gets its own column mapping.
 *
 * SDD Reference: RGMS SDD §3.12.5
 */
final class XlsxReportGenerator
{
    public function __construct(
        private readonly ReportRepository $repository,
    ) {
    }

    /**
     * Generate an XLSX for a named report type, saving to
     * storage/app/reports/{year}/{month}/{uuid}.xlsx (SDD §3.12.7).
     *
     * @param array<string, mixed> $filters
     */
    public function generateAndStore(string $reportType, array $filters): string
    {
        $data = $this->repository->loadData($reportType, $filters);

        $path = sprintf('reports/%s/%s/%s.xlsx', now()->format('Y'), now()->format('m'), Str::uuid());

        Excel::store(new RgmsGenericExport($data, $reportType), $path, 'local');

        return $path;
    }

    /**
     * Generate an XLSX directly from an already-loaded Collection
     * and stream it as a download response — used by
     * ResearchGroupService::export() (Module 3.1).
     */
    public function generate(Collection $data, string $filename): StreamedResponse
    {
        return Excel::download(new RgmsGenericExport($data, 'ResearchGroupSummary'), "{$filename}.xlsx");
    }
}
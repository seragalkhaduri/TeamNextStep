<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services\Support;

use App\Domain\ResearchGroups\Repositories\ReportRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PdfReportGenerator
 *
 * Generates PDF reports via Barryvdh/DomPDF, rendering a Blade
 * template per report type. Used both by the full Reporting Engine
 * (ReportService::generatePdf()) and directly by simpler synchronous
 * module exports (e.g. ResearchGroupService::export()).
 *
 * SDD Reference: RGMS SDD §3.12.5
 */
final class PdfReportGenerator
{
    public function __construct(
        private readonly ReportRepository $repository,
    ) {
    }

    /**
     * Generate a PDF for a named report type, saving to
     * storage/app/reports/{year}/{month}/{uuid}.pdf and returning the
     * relative file path (SDD §3.12.7).
     *
     * @param array<string, mixed> $filters
     */
    public function generateAndStore(string $reportType, array $filters): string
    {
        $data = $this->repository->loadData($reportType, $filters);

        $pdf = Pdf::loadView($this->templateFor($reportType), ['data' => $data, 'filters' => $filters]);

        $path = sprintf('reports/%s/%s/%s.pdf', now()->format('Y'), now()->format('m'), Str::uuid());

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate a PDF directly from an already-loaded Collection and
     * stream it as a download response — used by
     * ResearchGroupService::export() (Module 3.1), which does not go
     * through the async ReportExecutionHistory pipeline.
     */
    public function generate(Collection $data, string $filename): StreamedResponse
    {
        $pdf = Pdf::loadView('reports.research-groups-export', ['data' => $data]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "{$filename}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Resolve the Blade template path for a given report type.
     */
    private function templateFor(string $reportType): string
    {
        return 'reports.' . Str::kebab($reportType);
    }
}
<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class StudentExportService
{
    /**
     * Export the list of students to Excel using OpenSpout.
     */
    public function exportExcel(Collection $students): string
    {
        $fileName = 'students_export_' . now()->format('Ymd_His') . '.xlsx';
        $tempDir = storage_path('app/temp');
        
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/' . $fileName;

        $writer = new Writer();
        $writer->openToFile($filePath);

        // Header Row
        $headerCells = [
            Cell::fromValue('Institutional ID'),
            Cell::fromValue('National ID'),
            Cell::fromValue('Full Name (EN)'),
            Cell::fromValue('Full Name (AR)'),
            Cell::fromValue('Email'),
            Cell::fromValue('Gender'),
            Cell::fromValue('Enrollment Status'),
            Cell::fromValue('Admission Date')
        ];
        $writer->addRow(new Row($headerCells));

        // Data Rows
        foreach ($students as $student) {
            $cells = [
                Cell::fromValue($student->institutional_id),
                Cell::fromValue($student->national_id),
                Cell::fromValue($student->name_en),
                Cell::fromValue($student->name_ar),
                Cell::fromValue($student->email),
                Cell::fromValue($student->gender?->value),
                Cell::fromValue($student->enrollment_status?->value),
                Cell::fromValue($student->admission_date?->toDateString())
            ];
            $writer->addRow(new Row($cells));
        }

        $writer->close();

        return $filePath;
    }

    /**
     * Export the student profiles list to PDF using Barryvdh DomPDF.
     */
    public function exportPdf(Collection $students): string
    {
        $pdf = Pdf::loadView('reports.students', [
            'students' => $students,
            'generatedAt' => now()->toDateTimeString(),
        ]);

        $fileName = 'students_report_' . now()->format('Ymd_His') . '.pdf';
        $tempDir = storage_path('app/temp');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/' . $fileName;
        $pdf->save($filePath);

        return $filePath;
    }
}

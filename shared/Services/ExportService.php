<?php

namespace Shared\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

/**
 * Export Service
 * 
 * Provides data export capabilities in various formats (CSV, JSON, Excel-compatible CSV).
 * Uses Laravel's service container for dependency injection.
 */
class ExportService
{
    /**
     * Export data to CSV
     *
     * @param Collection|array $data Data to export
     * @param array $headers Column headers
     * @param string $filename Output filename
     * @param callable|null $transformer Optional data transformer
     * @return \Illuminate\Http\Response
     */
    public function toCsv(
        $data,
        array $headers,
        string $filename = 'export.csv',
        ?callable $transformer = null
    ) {
        return $this->buildCsvResponse($data, $headers, $filename, $transformer, false);
    }

    /**
     * Export data to Excel-compatible CSV (UTF-8 with BOM)
     *
     * @param Collection|array $data Data to export
     * @param array $headers Column headers
     * @param string $filename Output filename
     * @param callable|null $transformer Optional data transformer
     * @return \Illuminate\Http\Response
     */
    public function toExcelCsv(
        $data,
        array $headers,
        string $filename = 'export.csv',
        ?callable $transformer = null
    ) {
        return $this->buildCsvResponse($data, $headers, $filename, $transformer, true);
    }

    /**
     * Export data to JSON
     *
     * @param Collection|array $data Data to export
     * @param string $filename Output filename
     * @param callable|null $transformer Optional data transformer
     * @return \Illuminate\Http\Response
     */
    public function toJson(
        $data,
        string $filename = 'export.json',
        ?callable $transformer = null
    ) {
        $collection = $data instanceof Collection ? $data : collect($data);

        if ($transformer) {
            $collection = $collection->map($transformer);
        }

        return Response::json($collection, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate CSV content as string (for storage or further processing)
     *
     * @param Collection|array $data Data to export
     * @param array $headers Column headers
     * @param callable|null $transformer Optional data transformer
     * @param bool $excelBom Whether to include Excel BOM
     * @return string CSV content
     */
    public function generateCsvString(
        $data,
        array $headers,
        ?callable $transformer = null,
        bool $excelBom = false
    ): string {
        $collection = $data instanceof Collection ? $data : collect($data);

        $output = fopen('php://temp', 'r+');

        // Write UTF-8 BOM for Excel compatibility if requested
        if ($excelBom) {
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        }

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($collection as $item) {
            $row = $transformer ? $transformer($item) : (array) $item;
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export candidates to CSV
     *
     * @param Collection $candidates Candidate collection
     * @param string $filename Output filename
     * @return \Illuminate\Http\Response
     */
    public function exportCandidates(Collection $candidates, string $filename = 'candidates.csv')
    {
        $headers = [
            'ID', 'Name', 'Email', 'Phone', 'Status', 'Location', 'Created At', 'Updated At'
        ];

        $transformer = fn($candidate) => [
            $candidate->id ?? '',
            $candidate->name ?? '',
            $candidate->email ?? '',
            $candidate->phone ?? '',
            $candidate->status ?? '',
            $candidate->location ?? '',
            $candidate->created_at ?? '',
            $candidate->updated_at ?? ''
        ];

        return $this->toExcelCsv($candidates, $headers, $filename, $transformer);
    }

    /**
     * Export vacancies to CSV
     *
     * @param Collection $vacancies Vacancy collection
     * @param string $filename Output filename
     * @return \Illuminate\Http\Response
     */
    public function exportVacancies(Collection $vacancies, string $filename = 'vacancies.csv')
    {
        $headers = [
            'ID', 'Title', 'Department', 'Location', 'Employment Type',
            'Status', 'Salary Min', 'Salary Max', 'Created At'
        ];

        $transformer = fn($vacancy) => [
            $vacancy->id ?? '',
            $vacancy->title ?? '',
            $vacancy->department ?? '',
            $vacancy->location ?? '',
            $vacancy->employment_type ?? '',
            $vacancy->status ?? '',
            $vacancy->salary_min ?? '',
            $vacancy->salary_max ?? '',
            $vacancy->created_at ?? ''
        ];

        return $this->toExcelCsv($vacancies, $headers, $filename, $transformer);
    }

    /**
     * Export matches to CSV
     *
     * @param Collection $matches Match collection
     * @param string $filename Output filename
     * @return \Illuminate\Http\Response
     */
    public function exportMatches(Collection $matches, string $filename = 'matches.csv')
    {
        $headers = [
            'Match ID', 'Candidate Name', 'Candidate Email', 'Vacancy Title',
            'Match Score', 'Status', 'Created At'
        ];

        $transformer = fn($match) => [
            $match->id ?? '',
            $match->candidate->name ?? '',
            $match->candidate->email ?? '',
            $match->vacancy->title ?? '',
            $match->match_score ?? '',
            $match->status ?? '',
            $match->created_at ?? ''
        ];

        return $this->toExcelCsv($matches, $headers, $filename, $transformer);
    }

    /**
     * Build CSV response with common logic
     */
    private function buildCsvResponse(
        $data,
        array $headers,
        string $filename,
        ?callable $transformer,
        bool $excelBom
    ): \Illuminate\Http\Response {
        $collection = $data instanceof Collection ? $data : collect($data);

        $callback = function() use ($collection, $headers, $transformer, $excelBom) {
            $file = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility if requested
            if ($excelBom) {
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            }

            // Write headers
            fputcsv($file, $headers);

            // Write data rows
            foreach ($collection as $item) {
                $row = $transformer ? $transformer($item) : (array) $item;
                fputcsv($file, $row);
            }

            fclose($file);
        };

        $contentType = $excelBom ? 'text/csv; charset=UTF-8' : 'text/csv';

        return Response::stream($callback, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
}

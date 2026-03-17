<?php

namespace Shared\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Bulk Operations Service
 * 
 * Provides batch processing capabilities for bulk updates, imports, and operations.
 * Uses Laravel's service container for dependency injection.
 */
class BulkOperationService
{
    /**
     * Process bulk updates for a model
     *
     * @param string $modelClass The model class to update
     * @param array $updates Array of ['id' => [...fields to update]]
     * @param callable|null $validator Optional validation callback
     * @return array Results with success/failure counts
     */
    public function bulkUpdate(string $modelClass, array $updates, ?callable $validator = null): array
    {
        $results = $this->initResults(count($updates));

        foreach ($updates as $id => $data) {
            $this->processUpdate($modelClass, $id, $data, $validator, $results);
        }

        return $results;
    }

    /**
     * Process bulk delete for a model
     *
     * @param string $modelClass The model class
     * @param array $ids Array of IDs to delete
     * @param bool $softDelete Use soft delete if available (default: true)
     * @return array Results with success/failure counts
     */
    public function bulkDelete(string $modelClass, array $ids, bool $softDelete = true): array
    {
        $results = $this->initResults(count($ids));

        foreach ($ids as $id) {
            $this->processDelete($modelClass, $id, $softDelete, $results);
        }

        return $results;
    }

    /**
     * Import data from CSV
     *
     * @param string $filePath Path to CSV file
     * @param callable $processor Callback to process each row
     * @param bool $hasHeader Whether CSV has header row
     * @return array Import results
     */
    public function importFromCsv(string $filePath, callable $processor, bool $hasHeader = true): array
    {
        $results = $this->initResults(0);

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \RuntimeException("Could not open file: {$filePath}");
        }

        $headers = null;
        $rowNumber = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($hasHeader && $rowNumber === 1) {
                    $headers = $this->validateHeaders($row);
                    continue;
                }

                $results['total']++;
                $this->processCsvRow($headers, $row, $rowNumber, $processor, $results);
            }
        } finally {
            fclose($handle);
        }

        return $results;
    }

    /**
     * Process items in batches
     *
     * @param Collection|array $items Items to process
     * @param callable $processor Callback to process each batch
     * @param int $batchSize Size of each batch
     * @return array Processing results
     */
    public function processBatch($items, callable $processor, int $batchSize = 100): array
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        
        $results = [
            'total' => $collection->count(),
            'batches' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $collection->chunk($batchSize)->each(function ($batch) use ($processor, &$results) {
            $results['batches']++;
            
            try {
                $processor($batch);
                $results['success'] += $batch->count();
            } catch (\Exception $e) {
                $results['failed'] += $batch->count();
                $results['errors'][] = $e->getMessage();
                
                Log::error('Batch processing failed', [
                    'batch' => $results['batches'],
                    'error' => $e->getMessage()
                ]);
            }
        });

        return $results;
    }

    /**
     * Bulk status update
     *
     * @param string $modelClass The model class
     * @param array $ids Array of IDs
     * @param string $status New status value
     * @param string $statusField Status field name (default: 'status')
     * @return array Results
     */
    public function bulkStatusUpdate(
        string $modelClass, 
        array $ids, 
        string $status,
        string $statusField = 'status'
    ): array {
        $updates = array_fill_keys($ids, [$statusField => $status]);

        return $this->bulkUpdate($modelClass, $updates);
    }

    /**
     * Bulk assign operation
     *
     * @param string $modelClass The model class
     * @param array $ids Array of IDs
     * @param string $field Field to update
     * @param mixed $value Value to assign
     * @return array Results
     */
    public function bulkAssign(
        string $modelClass,
        array $ids,
        string $field,
        $value
    ): array {
        $updates = array_fill_keys($ids, [$field => $value]);

        return $this->bulkUpdate($modelClass, $updates);
    }

    /**
     * Initialize results array
     */
    private function initResults(int $total): array
    {
        return [
            'total' => $total,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
    }

    /**
     * Process a single update operation
     */
    private function processUpdate(
        string $modelClass,
        int|string $id,
        array $data,
        ?callable $validator,
        array &$results
    ): void {
        try {
            if ($validator && !$validator($id, $data)) {
                $results['failed']++;
                $results['errors'][$id] = 'Validation failed';
                return;
            }

            $model = $modelClass::findOrFail($id);
            $model->update($data);
            $results['success']++;

        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][$id] = $e->getMessage();
            
            Log::warning('Bulk update failed for item', [
                'model' => $modelClass,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process a single delete operation
     */
    private function processDelete(
        string $modelClass,
        int|string $id,
        bool $softDelete,
        array &$results
    ): void {
        try {
            $model = $modelClass::findOrFail($id);
            
            // Check if model uses SoftDeletes trait for soft delete support
            if ($softDelete && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
                $model->delete();
            } else {
                $model->forceDelete();
            }
            
            $results['success']++;

        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][$id] = $e->getMessage();
            
            Log::warning('Bulk delete failed for item', [
                'model' => $modelClass,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate CSV headers
     */
    private function validateHeaders(array $row): ?array
    {
        // Filter out empty columns to handle malformed CSVs
        return array_filter($row, fn($header) => $header !== '');
    }

    /**
     * Process a single CSV row
     */
    private function processCsvRow(
        ?array $headers,
        array $row,
        int $rowNumber,
        callable $processor,
        array &$results
    ): void {
        try {
            $data = $headers ? array_combine($headers, $row) : $row;
            $processor($data, $rowNumber);
            $results['success']++;

        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][$rowNumber] = $e->getMessage();
            
            Log::warning('CSV import failed for row', [
                'row' => $rowNumber,
                'error' => $e->getMessage()
            ]);
        }
    }
}

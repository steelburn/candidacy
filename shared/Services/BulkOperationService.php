<?php

namespace Shared\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Bulk Operations Service
 * 
 * Provides batch processing capabilities for bulk updates, imports, and operations
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
    public static function bulkUpdate(string $modelClass, array $updates, ?callable $validator = null): array
    {
        $results = [
            'total' => count($updates),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($updates as $id => $data) {
            try {
                // Validate if validator provided
                if ($validator && !$validator($id, $data)) {
                    $results['failed']++;
                    $results['errors'][$id] = 'Validation failed';
                    continue;
                }

                // Find and update the model
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

        return $results;
    }

    /**
     * Process bulk delete for a model
     *
     * @param string $modelClass The model class
     * @param array $ids Array of IDs to delete
     * @param bool $softDelete Use soft delete if available
     * @return array Results with success/failure counts
     */
    public static function bulkDelete(string $modelClass, array $ids, bool $softDelete = true): array
    {
        $results = [
            'total' => count($ids),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($ids as $id) {
            try {
                $model = $modelClass::findOrFail($id);
                
                if ($softDelete && method_exists($model, 'delete')) {
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
    public static function importFromCsv(string $filePath, callable $processor, bool $hasHeader = true): array
    {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        $headers = null;
        $rowNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip header row
            if ($hasHeader && $rowNumber === 1) {
                $headers = $row;
                continue;
            }

            $results['total']++;

            try {
                // Convert row to associative array if headers exist
                $data = $headers ? array_combine($headers, $row) : $row;
                
                // Process the row
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

        fclose($handle);

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
    public static function processBatch($items, callable $processor, int $batchSize = 100): array
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
    public static function bulkStatusUpdate(
        string $modelClass, 
        array $ids, 
        string $status,
        string $statusField = 'status'
    ): array {
        $updates = [];
        foreach ($ids as $id) {
            $updates[$id] = [$statusField => $status];
        }

        return self::bulkUpdate($modelClass, $updates);
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
    public static function bulkAssign(
        string $modelClass,
        array $ids,
        string $field,
        $value
    ): array {
        $updates = [];
        foreach ($ids as $id) {
            $updates[$id] = [$field => $value];
        }

        return self::bulkUpdate($modelClass, $updates);
    }
}

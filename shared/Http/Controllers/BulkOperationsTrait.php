<?php

namespace Shared\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Shared\Services\BulkOperationService;
use Shared\Services\ExportService;

/**
 * Bulk Operations Controller Trait
 * 
 * Provides reusable bulk operation endpoints for any model
 */
trait BulkOperationsTrait
{
    /**
     * Bulk update records
     *
     * POST /api/{resource}/bulk-update
     * Body: { "updates": { "1": {"field": "value"}, "2": {"field": "value"} } }
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*' => 'required|array'
        ]);

        $modelClass = $this->getModelClass();
        $updates = $request->input('updates');

        $results = BulkOperationService::bulkUpdate($modelClass, $updates);

        return response()->json([
            'message' => 'Bulk update completed',
            'results' => $results
        ], $results['failed'] > 0 ? 207 : 200);
    }

    /**
     * Bulk delete records
     *
     * POST /api/{resource}/bulk-delete
     * Body: { "ids": [1, 2, 3] }
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        $modelClass = $this->getModelClass();
        $ids = $request->input('ids');

        $results = BulkOperationService::bulkDelete($modelClass, $ids);

        return response()->json([
            'message' => 'Bulk delete completed',
            'results' => $results
        ], $results['failed'] > 0 ? 207 : 200);
    }

    /**
     * Bulk status update
     *
     * POST /api/{resource}/bulk-status
     * Body: { "ids": [1, 2, 3], "status": "active" }
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
            'status' => 'required|string'
        ]);

        $modelClass = $this->getModelClass();
        $ids = $request->input('ids');
        $status = $request->input('status');

        $results = BulkOperationService::bulkStatusUpdate($modelClass, $ids, $status);

        return response()->json([
            'message' => 'Bulk status update completed',
            'results' => $results
        ], $results['failed'] > 0 ? 207 : 200);
    }

    /**
     * Export records to CSV
     *
     * GET /api/{resource}/export?format=csv&ids=1,2,3
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'sometimes|in:csv,json',
            'ids' => 'sometimes|string'
        ]);

        $modelClass = $this->getModelClass();
        $format = $request->input('format', 'csv');
        
        // Get records
        $query = $modelClass::query();
        
        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $query->whereIn('id', $ids);
        }

        $records = $query->get();

        // Get export method if defined
        $exportMethod = $this->getExportMethod();
        
        if ($exportMethod && method_exists(ExportService::class, $exportMethod)) {
            return ExportService::$exportMethod($records);
        }

        // Default export
        $headers = $this->getExportHeaders();
        $transformer = $this->getExportTransformer();
        $filename = $this->getExportFilename();

        if ($format === 'json') {
            return ExportService::toJson($records, $filename . '.json', $transformer);
        }

        return ExportService::toExcelCsv($records, $headers, $filename . '.csv', $transformer);
    }

    /**
     * Get the model class for this controller
     * Override this in your controller
     */
    protected function getModelClass(): string
    {
        throw new \Exception('getModelClass() must be implemented');
    }

    /**
     * Get export headers
     * Override this in your controller
     */
    protected function getExportHeaders(): array
    {
        return ['ID', 'Created At', 'Updated At'];
    }

    /**
     * Get export transformer callback
     * Override this in your controller
     */
    protected function getExportTransformer(): ?callable
    {
        return function($item) {
            return [
                $item->id ?? '',
                $item->created_at ?? '',
                $item->updated_at ?? ''
            ];
        };
    }

    /**
     * Get export filename
     * Override this in your controller
     */
    protected function getExportFilename(): string
    {
        return 'export';
    }

    /**
     * Get specialized export method name
     * Override this in your controller to use ExportService specialized methods
     */
    protected function getExportMethod(): ?string
    {
        return null;
    }
}

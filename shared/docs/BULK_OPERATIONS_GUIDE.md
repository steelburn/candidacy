# Bulk Operations and Export Features - Usage Guide

## Overview

Phase 3 introduces powerful bulk operations and export capabilities to the Candidacy platform, enabling efficient batch processing and data export functionality.

---

## 🔄 Bulk Operations Service

### Features
- Bulk updates for multiple records
- Bulk delete operations
- CSV import processing
- Batch processing with chunking
- Bulk status updates
- Bulk field assignments

### Usage Examples

#### 1. Bulk Update Records

```php
use Shared\Services\BulkOperationService;
use App\Models\Candidate;

// Update multiple candidates
$updates = [
    1 => ['status' => 'active', 'location' => 'New York'],
    2 => ['status' => 'active', 'location' => 'Boston'],
    3 => ['status' => 'inactive']
];

$results = BulkOperationService::bulkUpdate(Candidate::class, $updates);

// Results:
// [
//     'total' => 3,
//     'success' => 3,
//     'failed' => 0,
//     'errors' => []
// ]
```

#### 2. Bulk Delete with Validation

```php
// Delete multiple records
$ids = [1, 2, 3, 4, 5];

$results = BulkOperationService::bulkDelete(
    Candidate::class,
    $ids,
    $softDelete = true  // Use soft delete
);
```

#### 3. Import from CSV

```php
use App\Models\Candidate;

$results = BulkOperationService::importFromCsv(
    storage_path('imports/candidates.csv'),
    function($row, $rowNumber) {
        // Process each row
        Candidate::create([
            'name' => $row['Name'],
            'email' => $row['Email'],
            'phone' => $row['Phone'],
            'status' => $row['Status'] ?? 'active'
        ]);
    },
    $hasHeader = true
);

// Results include success/failure counts and errors
```

#### 4. Batch Processing

```php
use App\Models\Candidate;

$candidates = Candidate::where('status', 'pending')->get();

$results = BulkOperationService::processBatch(
    $candidates,
    function($batch) {
        // Process each batch of 100 candidates
        foreach ($batch as $candidate) {
            // Send email, update status, etc.
            $candidate->update(['status' => 'processed']);
        }
    },
    $batchSize = 100
);
```

#### 5. Bulk Status Update

```php
// Update status for multiple candidates
$candidateIds = [1, 2, 3, 4, 5];

$results = BulkOperationService::bulkStatusUpdate(
    Candidate::class,
    $candidateIds,
    'hired'  // New status
);
```

#### 6. Bulk Field Assignment

```php
// Assign recruiter to multiple candidates
$candidateIds = [1, 2, 3];

$results = BulkOperationService::bulkAssign(
    Candidate::class,
    $candidateIds,
    'recruiter_id',
    42  // Recruiter ID
);
```

---

## 📤 Export Service

### Features
- CSV export
- Excel-compatible CSV (with UTF-8 BOM)
- JSON export
- Specialized exports for candidates, vacancies, matches
- Custom transformers for data formatting

### Usage Examples

#### 1. Export to CSV

```php
use Shared\Services\ExportService;
use App\Models\Candidate;

$candidates = Candidate::where('status', 'active')->get();

$headers = ['ID', 'Name', 'Email', 'Status'];

$transformer = function($candidate) {
    return [
        $candidate->id,
        $candidate->name,
        $candidate->email,
        $candidate->status
    ];
};

return ExportService::toCsv($candidates, $headers, 'candidates.csv', $transformer);
```

#### 2. Export to Excel-Compatible CSV

```php
// Includes UTF-8 BOM for proper Excel encoding
return ExportService::toExcelCsv(
    $candidates,
    $headers,
    'candidates.csv',
    $transformer
);
```

#### 3. Export to JSON

```php
return ExportService::toJson(
    $candidates,
    'candidates.json',
    function($candidate) {
        return [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $candidate->email
        ];
    }
);
```

#### 4. Specialized Exports

```php
use App\Models\Candidate;
use App\Models\Vacancy;
use App\Models\Match;

// Export candidates with predefined format
$candidates = Candidate::all();
return ExportService::exportCandidates($candidates, 'all-candidates.csv');

// Export vacancies
$vacancies = Vacancy::where('status', 'open')->get();
return ExportService::exportVacancies($vacancies, 'open-vacancies.csv');

// Export matches
$matches = Match::with(['candidate', 'vacancy'])->get();
return ExportService::exportMatches($matches, 'candidate-matches.csv');
```

#### 5. Generate CSV String (for storage)

```php
// Generate CSV content without downloading
$csvContent = ExportService::generateCsvString(
    $candidates,
    $headers,
    $transformer
);

// Save to storage
Storage::put('exports/candidates.csv', $csvContent);
```

---

## 🎯 Bulk Operations Trait (Controller Integration)

### Usage in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Shared\Http\Controllers\BulkOperationsTrait;

class CandidateController extends Controller
{
    use BulkOperationsTrait;

    protected function getModelClass(): string
    {
        return Candidate::class;
    }

    protected function getExportHeaders(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Status', 'Created At'];
    }

    protected function getExportTransformer(): ?callable
    {
        return function($candidate) {
            return [
                $candidate->id,
                $candidate->name,
                $candidate->email,
                $candidate->phone,
                $candidate->status,
                $candidate->created_at
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'candidates-' . date('Y-m-d');
    }

    protected function getExportMethod(): ?string
    {
        return 'exportCandidates';  // Use specialized export method
    }
}
```

### Available Endpoints

Once the trait is added to a controller, these endpoints become available:

#### Bulk Update
```bash
POST /api/candidates/bulk-update
Content-Type: application/json

{
  "updates": {
    "1": {"status": "active", "location": "New York"},
    "2": {"status": "hired"}
  }
}
```

#### Bulk Delete
```bash
POST /api/candidates/bulk-delete
Content-Type: application/json

{
  "ids": [1, 2, 3, 4, 5]
}
```

#### Bulk Status Update
```bash
POST /api/candidates/bulk-status
Content-Type: application/json

{
  "ids": [1, 2, 3],
  "status": "active"
}
```

#### Export
```bash
# Export all candidates
GET /api/candidates/export?format=csv

# Export specific candidates
GET /api/candidates/export?format=csv&ids=1,2,3,4,5

# Export as JSON
GET /api/candidates/export?format=json
```

---

## 📊 API Routes Setup

Add these routes to your service's `routes/api.php`:

```php
use App\Http\Controllers\CandidateController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Bulk operations
    Route::post('/candidates/bulk-update', [CandidateController::class, 'bulkUpdate']);
    Route::post('/candidates/bulk-delete', [CandidateController::class, 'bulkDelete']);
    Route::post('/candidates/bulk-status', [CandidateController::class, 'bulkStatusUpdate']);
    
    // Export
    Route::get('/candidates/export', [CandidateController::class, 'export']);
});
```

---

## 🔒 Security Considerations

### Authorization

Always add authorization checks before bulk operations:

```php
public function bulkUpdate(Request $request)
{
    // Check if user has permission
    if (!auth()->user()->can('bulk-update-candidates')) {
        abort(403, 'Unauthorized');
    }

    // Continue with bulk update...
    return parent::bulkUpdate($request);
}
```

### Rate Limiting

Apply rate limiting to bulk endpoints:

```php
Route::middleware(['auth:sanctum', 'rate.limit:admin'])->group(function () {
    Route::post('/candidates/bulk-update', [CandidateController::class, 'bulkUpdate']);
});
```

### Validation

Add custom validation for bulk operations:

```php
public function bulkUpdate(Request $request)
{
    $request->validate([
        'updates' => 'required|array|max:1000',  // Limit to 1000 updates
        'updates.*.status' => 'sometimes|in:active,inactive,hired'
    ]);

    return parent::bulkUpdate($request);
}
```

---

## 📈 Performance Tips

1. **Batch Size**: Adjust batch size based on your server capacity
   ```php
   BulkOperationService::processBatch($items, $processor, 50); // Smaller batches
   ```

2. **Memory Management**: For large exports, use streaming
   ```php
   // ExportService automatically streams CSV exports
   return ExportService::toCsv($largeDataset, $headers, 'export.csv');
   ```

3. **Background Processing**: For very large operations, use queues
   ```php
   dispatch(new BulkUpdateJob($updates));
   ```

4. **Database Optimization**: Use database transactions for bulk updates
   ```php
   DB::transaction(function() use ($updates) {
       BulkOperationService::bulkUpdate(Candidate::class, $updates);
   });
   ```

---

## 🧪 Testing Examples

```php
use Tests\TestCase;
use App\Models\Candidate;
use Shared\Services\BulkOperationService;

class BulkOperationsTest extends TestCase
{
    public function test_bulk_update_candidates()
    {
        $candidates = Candidate::factory()->count(5)->create();
        
        $updates = [];
        foreach ($candidates as $candidate) {
            $updates[$candidate->id] = ['status' => 'active'];
        }

        $results = BulkOperationService::bulkUpdate(Candidate::class, $updates);

        $this->assertEquals(5, $results['success']);
        $this->assertEquals(0, $results['failed']);
    }

    public function test_export_candidates_to_csv()
    {
        $candidates = Candidate::factory()->count(10)->create();

        $response = ExportService::exportCandidates($candidates);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }
}
```

---

## 📝 Summary

**Bulk Operations:**
- ✅ Bulk updates with validation
- ✅ Bulk deletes (soft/hard)
- ✅ CSV import processing
- ✅ Batch processing with chunking
- ✅ Status and field assignments

**Export Functionality:**
- ✅ CSV export
- ✅ Excel-compatible CSV
- ✅ JSON export
- ✅ Specialized exports (candidates, vacancies, matches)
- ✅ Custom transformers

**Integration:**
- ✅ Reusable controller trait
- ✅ RESTful API endpoints
- ✅ Authorization support
- ✅ Rate limiting compatible

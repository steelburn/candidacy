<?php

use Illuminate\Contracts\Console\Kernel;
use App\Models\Candidate;
use App\Services\CvProcessingService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// --- Script Logic ---

$candidateId = 4;
$candidate = Candidate::find($candidateId);

if (!$candidate) {
    echo "Candidate $candidateId not found.\n";
    exit(1);
}

echo "Found Candidate: " . $candidate->name . "\n";

$latestCv = $candidate->latestCv;
if (!$latestCv) {
    echo "No CV file found.\n";
    exit(1);
}

echo "Latest CV ID: " . $latestCv->id . "\n";
echo "Fetching parsing details (parsed text)...\n";

$cvService = app(CvProcessingService::class);

// Initialize a new job to force re-parsing
$job = $cvService->createParsingJob(
    new \Illuminate\Http\UploadedFile(
        storage_path('app/public/' . $latestCv->file_path), 
        $latestCv->file_name ?? 'cv.pdf', 
        $latestCv->file_type ?? 'application/pdf'
    ),
    $candidateId
);

echo "Created new parsing job ID: " . $job['job_id'] . "\n";
echo "Status: " . $job['status'] . "\n";

// Force immediate processing if we can access the AI service
// But the job is queued.
// Let's try to manually trigger the AI extraction part if we can,
// or just wait for the queue. The queue worker is running.

echo "Job queued. Please check logs for 'cv-parsing' or 'ai-service'.\n";

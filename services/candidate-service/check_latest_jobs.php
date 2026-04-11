<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CvParsingJob;

echo "Checking latest 2 jobs...\n";
$jobs = CvParsingJob::orderBy('created_at', 'desc')->take(2)->get();

foreach ($jobs as $job) {
    echo "Job ID: {$job->id}\n";
    echo "Status: {$job->status}\n";
    echo "Created: {$job->created_at}\n";
    if ($job->status === 'failed') {
        echo "Error: {$job->error_message}\n";
    }
    if ($job->status === 'completed') {
        echo "Extracted Text Length: " . strlen($job->extracted_text ?? '') . "\n";
        echo "Parsed Name: " . ($job->parsed_data['name'] ?? 'N/A') . "\n";
    }
    echo "-------------------\n";
}
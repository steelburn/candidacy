<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = \App\Models\ParseJob::orderBy('created_at', 'desc')->take(5)->get();
foreach ($jobs as $job) {
    echo "Job ID: {$job->id}, Status: {$job->status}, Created: {$job->created_at}\n";
    if ($job->status === 'failed') {
        echo "Error: {$job->error_message}\n";
    }
}
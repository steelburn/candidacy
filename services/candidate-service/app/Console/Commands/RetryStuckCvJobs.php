<?php

namespace App\Console\Commands;

use App\Models\CvParsingJob;
use App\Jobs\ProcessCvParsingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryStuckCvJobs extends Command
{
    protected $signature = 'cv:retry-stuck {--minutes=5 : Jobs stuck for this many minutes}';
    protected $description = 'Retry CV parsing jobs stuck in parsing_document status';

    public function handle()
    {
        $minutes = $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        // Find jobs stuck in parsing_document for more than X minutes
        $stuckJobs = CvParsingJob::where('status', 'parsing_document')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($stuckJobs->isEmpty()) {
            $this->info('No stuck jobs found.');
            return 0;
        }

        $this->info("Found {$stuckJobs->count()} stuck job(s). Retrying...");

        foreach ($stuckJobs as $job) {
            try {
                // Dispatch to database queue for reliability
                ProcessCvParsingJob::dispatch($job->id)->onConnection('database');
                
                Log::info('Retry stuck job: Dispatched', [
                    'job_id' => $job->id,
                    'stuck_since' => $job->created_at->diffForHumans()
                ]);
                
                $this->line("✓ Retried job {$job->id} (stuck since {$job->created_at->diffForHumans()})");
            } catch (\Exception $e) {
                Log::error('Retry stuck job: Failed', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
                
                $this->error("✗ Failed to retry job {$job->id}: {$e->getMessage()}");
            }
        }

        $this->info('Done!');
        return 0;
    }
}

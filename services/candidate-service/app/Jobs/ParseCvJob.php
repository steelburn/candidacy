<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\JobStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CvExtractorService;

class ParseCvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected $jobStatusId;
    protected $filePath;
    protected $deleteFileAfter;

    /**
     * Create a new job instance.
     */
    public function __construct($jobStatusId, $filePath, $deleteFileAfter = true)
    {
        $this->jobStatusId = $jobStatusId;
        $this->filePath = $filePath;
        $this->deleteFileAfter = $deleteFileAfter;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobStatus = JobStatus::find($this->jobStatusId);
        if (!$jobStatus) {
            Log::error("ParseCvJob: JobStatus not found for ID {$this->jobStatusId}");
            return;
        }

        $jobStatus->update(['status' => 'processing']);
        Log::info("ParseCvJob: Starting processing for Job {$this->jobStatusId}");

        try {
            // Full path to file
            $fullPath = storage_path('app/' . $this->filePath);
            
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found at {$fullPath}");
            }

            // Extract Text
            Log::info("ParseCvJob: Extracting text...");
            $extractor = new CvExtractorService();
            $extractedText = $extractor->extractText($fullPath);
            Log::info("ParseCvJob: Text extracted. Length: " . strlen($extractedText));

            // Clean up file if needed
            if ($this->deleteFileAfter) {
                Storage::delete($this->filePath);
            }

            if (empty($extractedText)) {
                throw new \Exception("Could not extract text from file");
            }

            // Call AI Service
            Log::info("ParseCvJob: Calling AI Service...");
            // Use internal Docker network URL
            $aiResponse = Http::timeout(300)->post('http://ai-service:8080/api/parse-cv', [
                'text' => $extractedText
            ]);

            Log::info("ParseCvJob: AI Service response status: " . $aiResponse->status());

            if (!$aiResponse->successful()) {
                throw new \Exception("AI parsing failed: " . $aiResponse->body());
            }

            $parsedData = $aiResponse->json();
            $data = $parsedData['parsed_data'] ?? $parsedData;

            $jobStatus->update([
                'status' => 'completed',
                'result' => $data
            ]);
            
            Log::info("ParseCvJob: Completed successfully for Job {$this->jobStatusId}");

        } catch (\Exception $e) {
            Log::error("ParseCvJob Failed: " . $e->getMessage());
            $jobStatus->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}

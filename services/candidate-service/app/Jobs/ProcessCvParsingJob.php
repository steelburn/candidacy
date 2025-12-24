<?php

namespace App\Jobs;

use App\Models\CvParsingJob;
use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCvParsingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected $parsingJobId;

    public function __construct($parsingJobId)
    {
        $this->parsingJobId = $parsingJobId;
    }

    public function handle()
    {
        $parsingJob = CvParsingJob::find($this->parsingJobId);
        
        if (!$parsingJob) {
            Log::error('ProcessCvParsingJob: Job not found', ['id' => $this->parsingJobId]);
            return;
        }

        try {
            $parsingJob->markAsProcessing();

            Log::info('ProcessCvParsingJob: Starting AI parsing', [
                'job_id' => $parsingJob->id,
                'text_length' => strlen($parsingJob->extracted_text)
            ]);

            $aiResponse = Http::timeout(240)
                ->post('http://ai-service:8080/api/parse-cv', [
                    'text' => $parsingJob->extracted_text
                ]);

            if (!$aiResponse->successful()) {
                throw new \Exception('AI service failed: ' . $aiResponse->body());
            }

            $parsedData = $aiResponse->json();
            $aiData = $parsedData['parsed_data'] ?? $parsedData;

            Log::info('ProcessCvParsingJob: Completed', [
                'job_id' => $parsingJob->id,
                'name' => $aiData['name'] ?? 'Unknown'
            ]);

            $parsingJob->markAsCompleted($aiData);

            if ($parsingJob->candidate_id) {
                $this->updateCandidate($parsingJob->candidate_id, $aiData);
            }

        } catch (\Exception $e) {
            Log::error('ProcessCvParsingJob: Failed', [
                'job_id' => $parsingJob->id,
                'error' => $e->getMessage()
            ]);
            
            $parsingJob->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function updateCandidate($candidateId, $aiData)
    {
        try {
            $candidate = Candidate::find($candidateId);
            if (!$candidate) return;

            $candidate->update([
                'name' => $aiData['name'] ?? $candidate->name,
                'email' => $aiData['email'] ?? $candidate->email,
                'phone' => $aiData['phone'] ?? $candidate->phone,
                'summary' => $aiData['summary'] ?? $candidate->summary,
                'skills' => $aiData['skills'] ?? $candidate->skills,
                'experience' => $aiData['experience'] ?? $candidate->experience,
                'education' => $aiData['education'] ?? $candidate->education,
                'years_of_experience' => $aiData['years_of_experience'] ?? $candidate->years_of_experience,
            ]);

            Log::info('ProcessCvParsingJob: Candidate updated', ['candidate_id' => $candidateId]);
        } catch (\Exception $e) {
            Log::error('ProcessCvParsingJob: Failed to update candidate', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

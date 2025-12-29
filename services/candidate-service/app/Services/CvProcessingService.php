<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CvFile;
use App\Models\CvParsingJob;
use App\Jobs\ProcessCvParsingJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * CvProcessingService - Handles CV upload, parsing, and text extraction.
 * 
 * Extracted from CandidateController to improve separation of concerns.
 * 
 * @package App\Services
 */
class CvProcessingService
{
    /**
     * Upload a CV file for a candidate with AI parsing.
     *
     * @param Candidate $candidate
     * @param \Illuminate\Http\UploadedFile $file
     * @return CvFile
     */
    public function uploadCv(Candidate $candidate, $file): CvFile
    {
        $originalName = $file->getClientOriginalName();
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cvs', $storedName, 'public');
        
        $fullPath = storage_path('app/public/' . $path);
        $extractedText = null;
        $parsedData = null;

        try {
            Log::info('CV upload: Starting text extraction', [
                'candidate_id' => $candidate->id,
                'file_path' => $fullPath
            ]);
            
            $extractor = new CvExtractorService();
            $extractedText = $extractor->extractText($fullPath);
            
            Log::info('CV upload: Text extracted', [
                'candidate_id' => $candidate->id,
                'text_length' => strlen($extractedText ?? '')
            ]);

            if ($extractedText) {
                $parsedData = $this->parseWithAiService($extractedText, $candidate);
            }
        } catch (\Exception $e) {
            Log::error('CV extraction or AI parsing failed: ' . $e->getMessage());
        }

        return CvFile::create([
            'candidate_id' => $candidate->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'extracted_text' => $extractedText,
            'parsed_data' => $parsedData,
        ]);
    }

    /**
     * Parse extracted text with AI service.
     *
     * @param string $extractedText
     * @param Candidate $candidate
     * @return array|null
     */
    protected function parseWithAiService(string $extractedText, Candidate $candidate): ?array
    {
        Log::info('CV upload: Calling AI service for parsing', [
            'candidate_id' => $candidate->id,
            'text_length' => strlen($extractedText),
            'ai_service_url' => 'http://ai-service:8080/api/ai/parse-cv'
        ]);
        
        $aiResponse = Http::timeout(60)->post('http://ai-service:8080/api/ai/parse-cv', [
            'text' => $extractedText
        ]);
        
        Log::info('CV upload: AI service response received', [
            'candidate_id' => $candidate->id,
            'status' => $aiResponse->status(),
            'successful' => $aiResponse->successful()
        ]);
        
        if (!$aiResponse->successful()) {
            Log::error('AI CV parsing failed with status ' . $aiResponse->status() . ': ' . $aiResponse->body());
            return null;
        }

        $parsedData = $aiResponse->json();
        $aiData = $parsedData['parsed_data'] ?? $parsedData;
        
        Log::info('CV upload: Parsed data extracted', [
            'candidate_id' => $candidate->id,
            'name' => $aiData['name'] ?? 'Unknown',
            'email' => $aiData['email'] ?? 'Not found',
            'skills_count' => count($aiData['skills'] ?? []),
            'experience_count' => count($aiData['experience'] ?? [])
        ]);
        
        // Update candidate with parsed data
        $this->updateCandidateFromParsedData($candidate, $aiData);
        
        return $parsedData;
    }

    /**
     * Update candidate fields from parsed AI data.
     *
     * @param Candidate $candidate
     * @param array $aiData
     */
    protected function updateCandidateFromParsedData(Candidate $candidate, array $aiData): void
    {
        $updateData = [];
        
        if (!empty($aiData['skills'])) {
            $updateData['skills'] = json_encode($aiData['skills']);
        }
        if (!empty($aiData['experience'])) {
            $updateData['experience'] = json_encode($aiData['experience']);
        }
        if (!empty($aiData['education'])) {
            $updateData['education'] = json_encode($aiData['education']);
        }
        if (!empty($aiData['summary'])) {
            $updateData['summary'] = $aiData['summary'];
        }
        if (!empty($aiData['phone']) && empty($candidate->phone)) {
            $updateData['phone'] = $aiData['phone'];
        }
        if (isset($aiData['years_of_experience'])) {
            $updateData['years_of_experience'] = $aiData['years_of_experience'];
        }
        
        if (!empty($updateData)) {
            $candidate->update($updateData);
        }
    }

    /**
     * Create an async CV parsing job.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int|null $candidateId
     * @return array Job info with id and status
     */
    public function createParsingJob($file, ?int $candidateId = null): array
    {
        $originalName = $file->getClientOriginalName();
        $path = $file->store('cvs', 'public');
        
        Log::info('CV parse: File uploaded, creating job', [
            'file' => $originalName,
            'path' => $path
        ]);

        $parsingJob = CvParsingJob::create([
            'candidate_id' => $candidateId,
            'file_path' => $path,
            'extracted_text' => null,
            'status' => 'parsing_document',
        ]);

        $this->dispatchParsingJob($parsingJob);
        
        return [
            'job_id' => $parsingJob->id,
            'status' => 'parsing_document'
        ];
    }

    /**
     * Dispatch a parsing job with fallback to database queue.
     *
     * @param CvParsingJob $parsingJob
     */
    protected function dispatchParsingJob(CvParsingJob $parsingJob): void
    {
        try {
            ProcessCvParsingJob::dispatch($parsingJob->id);
            
            Log::info('CV parse: Job dispatched', [
                'job_id' => $parsingJob->id
            ]);
        } catch (\Exception $e) {
            Log::error('CV parse: Failed to dispatch job', [
                'job_id' => $parsingJob->id,
                'error' => $e->getMessage()
            ]);
            
            $parsingJob->update(['status' => 'failed']);
            throw new \Exception('Failed to queue CV processing job');
        }
    }

    /**
     * Handle bulk resume upload.
     *
     * @param array $files
     * @return array
     */
    public function bulkUploadResumes(array $files): array
    {
        $queuedJobs = [];
        $failedUploads = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            
            try {
                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $permanentPath = 'cvs/' . $storedName;
                
                Storage::disk('public')->put($permanentPath, file_get_contents($file));

                $parsingJob = CvParsingJob::create([
                    'candidate_id' => null,
                    'file_path' => $permanentPath,
                    'status' => 'pending',
                ]);

                try {
                    ProcessCvParsingJob::dispatch($parsingJob->id);
                    
                    $queuedJobs[] = [
                        'file' => $originalName,
                        'job_id' => $parsingJob->id,
                        'status' => 'pending'
                    ];

                    Log::info('Bulk upload: Job dispatched', [
                        'file' => $originalName,
                        'job_id' => $parsingJob->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Bulk upload: Failed to dispatch job', [
                        'file' => $originalName,
                        'error' => $e->getMessage()
                    ]);
                    
                    $parsingJob->update(['status' => 'failed', 'error_message' => 'Dispatch failed']);
                    
                    $failedUploads[] = [
                        'file' => $originalName,
                        'error' => 'Failed to queue processing'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Bulk upload: File storage failed', [
                    'file' => $originalName,
                    'error' => $e->getMessage()
                ]);
                
                $failedUploads[] = [
                    'file' => $originalName,
                    'error' => 'Storage failed: ' . $e->getMessage()
                ];
            }
        }

        return [
            'jobs' => $queuedJobs,
            'failures' => $failedUploads,
            'summary' => [
                'total' => count($files),
                'queued' => count($queuedJobs),
                'failed' => count($failedUploads)
            ]
        ];
    }

    /**
     * Get CV parsing job status.
     *
     * @param int $jobId
     * @return CvParsingJob|null
     */
    public function getJobStatus(int $jobId): ?CvParsingJob
    {
        return CvParsingJob::find($jobId);
    }

    /**
     * Get parsing details for a candidate.
     *
     * @param int $candidateId
     * @return CvParsingJob|null
     */
    public function getParsingDetails(int $candidateId): ?CvParsingJob
    {
        // First try to get completed job
        $job = CvParsingJob::where('candidate_id', $candidateId)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$job) {
            // Fall back to any job
            $job = CvParsingJob::where('candidate_id', $candidateId)
                ->latest()
                ->first();
        }

        return $job;
    }

    /**
     * Retry a failed CV parsing job.
     *
     * @param int $jobId
     * @param string $mode 'full' or 'parse_only'
     * @return CvParsingJob
     */
    public function retryJob(int $jobId, string $mode = 'full'): CvParsingJob
    {
        $job = CvParsingJob::findOrFail($jobId);
        
        $updateData = ['status' => 'parsing_document'];
        
        if ($mode === 'full') {
            $updateData['extracted_text'] = null;
        }
        
        $updateData['error_message'] = null;
        $updateData['parsed_data'] = null;

        $job->update($updateData);

        ProcessCvParsingJob::dispatch($job->id);
        
        Log::info('Admin retry: Job dispatched', [
            'job_id' => $job->id,
            'mode' => $mode
        ]);
        
        return $job;
    }

    /**
     * Delete a CV parsing job.
     *
     * @param int $jobId
     * @return bool
     */
    public function deleteJob(int $jobId): bool
    {
        $job = CvParsingJob::findOrFail($jobId);
        return $job->delete();
    }

    /**
     * Generate standardized CV content for matching.
     *
     * @param array $candidateData
     * @return string
     */
    public function generateCvContent(array $candidateData): string
    {
        // Parse JSON fields if they're strings
        $skills = $candidateData['skills'] ?? [];
        if (is_string($skills)) {
            $skills = json_decode($skills, true) ?? [];
        }
        $skillsList = is_array($skills) ? implode(', ', $skills) : $skills;
        
        $experience = $candidateData['experience'] ?? [];
        if (is_string($experience)) {
            $experience = json_decode($experience, true) ?? [];
        }
        
        $education = $candidateData['education'] ?? [];
        if (is_string($education)) {
            $education = json_decode($education, true) ?? [];
        }
        
        // Build concise experience summary
        $expText = '';
        if (is_array($experience) && !empty($experience)) {
            foreach ($experience as $exp) {
                $title = $exp['title'] ?? 'Role';
                if (is_array($title)) $title = implode(' ', $title);
                
                $company = $exp['company'] ?? 'Company';
                if (is_array($company)) $company = implode(' ', $company);
                
                $duration = $exp['duration'] ?? '';
                if (is_array($duration)) $duration = implode(' ', $duration);

                $desc = $exp['description'] ?? '';
                
                $shortDesc = '';
                if (!empty($desc)) {
                    if (is_array($desc)) {
                        $desc = implode(' ', $desc);
                    }
                    $firstSentence = preg_split('/[.!?]\s+/', $desc, 2)[0];
                    $shortDesc = strlen($firstSentence) > 150 
                        ? substr($firstSentence, 0, 150) . '...' 
                        : $firstSentence;
                }
                
                $expText .= "- {$title} at {$company}";
                if ($duration) $expText .= " ({$duration})";
                if ($shortDesc) $expText .= ": {$shortDesc}";
                $expText .= "\n";
            }
        }
        
        // Build education summary
        $eduText = '';
        if (is_array($education) && !empty($education)) {
            foreach ($education as $edu) {
                $degree = $edu['degree'] ?? 'Degree';
                $inst = $edu['institution'] ?? 'Institution';
                $year = $edu['year'] ?? '';
                $eduText .= "- {$degree} from {$inst}";
                if ($year) $eduText .= " ({$year})";
                $eduText .= "\n";
            }
        }
        
        $yearsExp = $candidateData['years_of_experience'] ?? 'Not specified';
        $summary = $candidateData['summary'] ?? '';
        
        if (strlen($summary) > 300) {
            $summary = substr($summary, 0, 300) . '...';
        }
        
        $name = $candidateData['name'] ?? '';
        
        return <<<PROFILE
Name: {$name}
Years of Experience: {$yearsExp}
Skills: {$skillsList}
Summary: {$summary}

Work Experience:
{$expText}
Education:
{$eduText}
PROFILE;
    }
}

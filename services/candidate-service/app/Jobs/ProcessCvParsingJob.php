<?php

namespace App\Jobs;

use App\Models\CvParsingJob;
use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CvFile;
use Illuminate\Support\Facades\Storage;
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
        $this->onQueue('candidate_queue');
    }

    public function handle()
    {
        $parsingJob = CvParsingJob::find($this->parsingJobId);
        
        if (!$parsingJob) {
            Log::error('ProcessCvParsingJob: Job not found', ['id' => $this->parsingJobId]);
            return;
        }

        try {
            // Step 1: Document parsing (if not already done)
            if (!$parsingJob->extracted_text) {
                Log::info('ProcessCvParsingJob: Starting document parsing', [
                    'job_id' => $parsingJob->id,
                    'file_path' => $parsingJob->file_path
                ]);

                $fullPath = storage_path('app/public/' . $parsingJob->file_path);
                $parser = new \App\Services\DocumentParserClient();
                $result = $parser->parseDocument($fullPath);
                $extractedText = $result['text'];

                if (!$extractedText) {
                    throw new \Exception('Could not extract text from document');
                }

                // Update job with extracted text
                $parsingJob->update([
                    'extracted_text' => $extractedText,
                    'status' => 'processing' // Now ready for AI parsing
                ]);

                Log::info('ProcessCvParsingJob: Document parsing completed', [
                    'job_id' => $parsingJob->id,
                    'text_length' => strlen($extractedText)
                ]);
            } else {
                $parsingJob->markAsProcessing();
            }

            // Step 2: AI parsing
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
            
            if (!$parsedData) {
                 Log::error('ProcessCvParsingJob: AI response is not valid JSON', [
                    'job_id' => $parsingJob->id,
                    'body' => $aiResponse->body()
                ]);
                throw new \Exception('AI service returned invalid JSON');
            }

            $aiData = $parsedData['parsed_data'] ?? $parsedData;
            
            if (empty($aiData)) {
                 Log::error('ProcessCvParsingJob: AI parsed data is empty', [
                    'job_id' => $parsingJob->id,
                    'response' => $parsedData
                ]);
                // Consider throwing exception here or just proceeding with warnings
                // For now, let's allow it but log clearly
            }

            Log::info('ProcessCvParsingJob: Completed', [
                'job_id' => $parsingJob->id,
                'name' => $aiData['name'] ?? 'Unknown'
            ]);

            $parsingJob->markAsCompleted($aiData);

            $finalCandidateId = null;

            if ($parsingJob->candidate_id) {
                // Update existing candidate
                $this->updateCandidate($parsingJob->candidate_id, $aiData);
                $finalCandidateId = $parsingJob->candidate_id;
            } else {
                // Auto-create draft candidate for new CV uploads
                $candidateId = $this->createDraftCandidate($parsingJob, $aiData);
                if ($candidateId) {
                    $parsingJob->update(['candidate_id' => $candidateId]);
                    $finalCandidateId = $candidateId;
                }
            }

            if ($finalCandidateId) {
                $this->createCvFileRecord($finalCandidateId, $parsingJob, $aiData);
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

    protected function createDraftCandidate($parsingJob, $aiData)
    {
        try {
            // Check for existing candidate with same email
            $email = $aiData['email'] ?? null;
            if ($email) {
                $existingCandidate = Candidate::where('email', $email)->first();
                if ($existingCandidate) {
                    Log::info('ProcessCvParsingJob: Candidate with email exists. Linking to existing.', [
                        'email' => $email,
                        'existing_id' => $existingCandidate->id,
                        'job_id' => $parsingJob->id
                    ]);
                    
                    // Optional: Update the existing candidate with new data?
                    // For now, let's just link it so we don't overwrite verified data with potentially messy AI data blindly.
                    // Or maybe we should update empty fields?
                    // Let's keep it safe: just return existing ID.
                    return $existingCandidate->id;
                }
            }

            $candidate = Candidate::create([
                'name' => $aiData['name'] ?? 'Unknown',
                'email' => $aiData['email'] ?? null,
                'phone' => $aiData['phone'] ?? null,
                'summary' => $aiData['summary'] ?? null,
                'skills' => is_array($aiData['skills'] ?? null) ? implode(', ', $aiData['skills']) : ($aiData['skills'] ?? null),
                'experience' => $aiData['experience'] ?? null,
                'education' => $aiData['education'] ?? null,
                'years_of_experience' => $aiData['years_of_experience'] ?? null,
                'cv_file_path' => $parsingJob->file_path,
                'status' => 'draft', // Mark as draft for review
            ]);

            Log::info('ProcessCvParsingJob: Draft candidate created', [
                'candidate_id' => $candidate->id,
                'name' => $candidate->name,
                'job_id' => $parsingJob->id
            ]);

            return $candidate->id;
        } catch (\Exception $e) {
            Log::error('ProcessCvParsingJob: Failed to create draft candidate', [
                'job_id' => $parsingJob->id,
                'error' => $e->getMessage()
            ]);
            return null;
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


    protected function createCvFileRecord($candidateId, $parsingJob, $aiData)
    {
        try {
            // Check if already exists to avoid duplicates
            $exists = CvFile::where('candidate_id', $candidateId)
                            ->where('file_path', $parsingJob->file_path)
                            ->exists();
            if ($exists) return;

            $mimeType = 'application/pdf'; // Default
            $fileSize = 0;
            
            if (Storage::disk('public')->exists($parsingJob->file_path)) {
                $mimeType = Storage::disk('public')->mimeType($parsingJob->file_path);
                $fileSize = Storage::disk('public')->size($parsingJob->file_path);
            }

            CvFile::create([
                'candidate_id' => $candidateId,
                'file_path' => $parsingJob->file_path,
                'file_name' => basename($parsingJob->file_path),
                'file_type' => $mimeType,
                'file_size' => $fileSize,
                'extracted_text' => $parsingJob->extracted_text,
                'parsed_data' => $aiData,
                'parsing_status' => 'completed',
            ]);

            Log::info('ProcessCvParsingJob: CvFile record created', ['candidate_id' => $candidateId]);
        } catch (\Exception $e) {
            Log::error('ProcessCvParsingJob: Failed to create CvFile record', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

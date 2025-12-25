<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\JobStatus;
use App\Http\Controllers\Api\MatchController;
use Shared\Constants\AppConstants;
use Shared\Services\ConfigurationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    protected $jobStatusId;
    protected $candidateId;
    protected $vacancyId;

    /**
     * Create a new job instance.
     * If vacancyId is null, it matches against all vacancies (or inverse).
     */
    public function __construct($jobStatusId, $candidateId = null, $vacancyId = null)
    {
        $this->jobStatusId = $jobStatusId;
        $this->candidateId = $candidateId ? (int)$candidateId : null;
        $this->vacancyId = $vacancyId ? (int)$vacancyId : null;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobStatus = JobStatus::find($this->jobStatusId);
        if (!$jobStatus) {
            return;
        }

        $jobStatus->update(['status' => 'processing']);
        Log::info("MatchJob: Processing {$this->jobStatusId}");

        try {
            // Service URLs - using defaults if env not set
            $candidateServiceUrl = env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080');
            $vacancyServiceUrl = env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080');
            $aiServiceUrl = ConfigurationService::get('services.ai_service_url', env('AI_SERVICE_URL', 'http://ai-service:8080'));
            $adminServiceUrl = env('ADMIN_SERVICE_URL', 'http://admin-service:8080');

            // 1. Fetch Candidate
            Log::info("Fetching candidate {$this->candidateId}");
            $candidateRes = Http::get("{$candidateServiceUrl}/api/candidates/{$this->candidateId}");
            if (!$candidateRes->successful()) {
                throw new \Exception("Could not fetch candidate: " . $candidateRes->status() . " " . $candidateRes->body());
            }
            $candidate = $candidateRes->json();

            // 2. Fetch Vacancies
            $vacancies = [];
            if ($this->vacancyId) {
                $vacRes = Http::get("{$vacancyServiceUrl}/api/vacancies/{$this->vacancyId}");
                if ($vacRes->successful()) $vacancies[] = $vacRes->json();
            } else {
                $vacRes = Http::get("{$vacancyServiceUrl}/api/vacancies?status=open");
                if ($vacRes->successful()) $vacancies = $vacRes->json()['data'] ?? [];
            }

            if (empty($vacancies)) {
                $jobStatus->update(['status' => 'completed', 'result' => ['matches_found' => 0, 'message' => 'No open vacancies found']]);
                return;
            }

            // 3. Get Threshold
            $threshold = 60; // Default
            try {
                $settingsRes = Http::get("{$adminServiceUrl}/api/settings/category/ai");
                if ($settingsRes->successful()) {
                    $settings = $settingsRes->json();
                    if (is_array($settings)) {
                        foreach($settings as $s) {
                            if (is_array($s) && isset($s['key']) && $s['key'] === 'match_threshold') {
                                $threshold = (int)$s['value'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Could not fetch settings, using default threshold: " . $e->getMessage());
            }

            // 4. Process Matches
            $matchController = new MatchController(); // Use controller for helper methods if needed, or just helpers here. 
            // Better to copy helper logic or rely on private methods if accessible? No.
            // Let's implement robust matching here.
            
            $results = [];

            foreach ($vacancies as $vacancy) {
                // Helper to safely encode to string - handles arrays, nested arrays, and JSON strings
                $safeEncode = function($val) {
                    if (is_null($val)) return '';
                    if (is_string($val)) {
                        // Try to decode if it's JSON
                        $decoded = json_decode($val, true);
                        if (is_array($decoded)) {
                            $val = $decoded;
                        } else {
                            return $val; // Return as-is if not JSON
                        }
                    }
                    if (is_array($val)) {
                        // For simple arrays, join with commas
                        if (count($val) > 0 && !is_array(reset($val))) {
                            return implode(', ', $val);
                        }
                        // For complex nested arrays (like experience/education), format nicely
                        $formatted = [];
                        foreach ($val as $item) {
                            if (is_array($item)) {
                                $formatted[] = json_encode($item);
                            } else {
                                $formatted[] = (string)$item;
                            }
                        }
                        return implode('; ', $formatted);
                    }
                    return (string)$val;
                };
                
                $candidateProfile = "Name: {$candidate['name']}\n";
                $candidateProfile .= "Summary: " . ($candidate['summary'] ?? '') . "\n";
                $candidateProfile .= "Skills: " . $safeEncode($candidate['skills'] ?? []) . "\n";
                $candidateProfile .= "Experience: " . $safeEncode($candidate['experience'] ?? []) . "\n";
                
                $jobReqs = "Position: {$vacancy['title']}\n";
                $jobReqs .= "Description: {$vacancy['description']}\n";
                $jobReqs .= "Skills: " . $safeEncode($vacancy['required_skills'] ?? []) . "\n";

                try {
                    $aiResponse = Http::timeout(AppConstants::API_TIMEOUT)->post("{$aiServiceUrl}/api/match", [
                        'candidate_profile' => $candidateProfile,
                        'job_requirements' => $jobReqs
                    ]);

                    if ($aiResponse->successful()) {
                        $matchResult = $aiResponse->json();
                        $score = $matchResult['match_score'] ?? 0;
                        
                        // Always save, regardless of threshold, so we can see low matches too? 
                        // Or only above threshold? Requirement usually implies filtering.
                        // But let's save all computed matches so we don't re-compute. status can be 'new' or 'rejected'.
                        
                        \App\Models\CandidateMatch::updateOrCreate(
                            [
                                'candidate_id' => $candidate['id'],
                                'vacancy_id' => $vacancy['id']
                            ],
                            [
                                'vacancy_title' => $vacancy['title'],
                                'match_score' => $score,
                                'analysis' => $matchResult['analysis'] ?? '',
                                'status' => 'pending' // Default status
                            ]
                        );
                        
                        if ($score >= $threshold) {
                            $results[] = [
                                'vacancy' => $vacancy['title'],
                                'score' => $score
                            ];
                        }
                    } else {
                        Log::error("AI match failed for vacancy {$vacancy['id']}: " . $aiResponse->body());
                    }
                } catch (\Exception $aiEx) {
                   Log::error("AI Service Error: " . $aiEx->getMessage()); 
                }
            }

            $jobStatus->update([
                'status' => 'completed', 
                'result' => [
                    'matches_processed' => count($vacancies),
                    'matches_found' => count($results)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("MatchJob Critical Error: " . $e->getMessage());
            $jobStatus->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}

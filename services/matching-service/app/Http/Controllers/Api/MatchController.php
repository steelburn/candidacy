<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\CandidateMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\JobStatus;
use App\Jobs\MatchJob;
use Shared\Services\ConfigurationService;

/**
 * MatchController - AI-powered candidate-vacancy matching.
 * 
 * Handles intelligent matching between candidates and job vacancies:
 * - Match calculation via AI service with configurable thresholds
 * - Interview question generation based on match analysis
 * - Match dismissal and restoration
 * - Configurable retry logic and score filtering
 * 
 * @package App\Http\Controllers\Api
 * @author Candidacy Development Team
 */
class MatchController extends BaseApiController
{
    protected $candidateServiceUrl;
    protected $vacancyServiceUrl;
    protected $aiServiceUrl;

    public function __construct()
    {
        $this->candidateServiceUrl = env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080');
        $this->vacancyServiceUrl = env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080');
        $this->aiServiceUrl = ConfigurationService::get('services.ai_service_url', env('AI_SERVICE_URL', 'http://ai-service:8080'));
    }

    public function matchCandidateToVacancies(Request $request, $id)
    {
        // Check for existing matches
        $existing = CandidateMatch::where('candidate_id', $id)
            ->orderBy('match_score', 'desc')
            ->get();

        // Enrich matches with vacancy data
        $enrichedMatches = $this->enrichMatchesWithVacancies($existing);

        // If 'refresh' param is present OR no matches exist, trigger background job
        if ($existing->isEmpty() || $request->has('refresh')) {
            $jobStatus = JobStatus::create([
                'type' => 'match_candidate',
                'status' => 'pending'
            ]);
            
            MatchJob::dispatch($jobStatus->id, $id, null);
            
            return response()->json([
                'status' => 'processing',
                'job_id' => $jobStatus->id,
                'message' => 'Matching in progress',
                'matches' => $enrichedMatches
            ]);
        }

        return response()->json(['data' => $enrichedMatches]);
    }

    protected function enrichMatchesWithVacancies($matches)
    {
        $vacancyServiceUrl = env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080');
        
        foreach ($matches as $match) {
            try {
                $response = Http::get("{$vacancyServiceUrl}/api/vacancies/{$match->vacancy_id}");
                if ($response->successful()) {
                    $vacancyData = $response->json();
                    $match->vacancy = $vacancyData;
                    // Add vacancy_title directly to match for easier frontend access
                    $match->vacancy_title = $vacancyData['title'] ?? 'Untitled Position';
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch vacancy {$match->vacancy_id}: " . $e->getMessage());
                $match->vacancy = null;
                $match->vacancy_title = 'Unknown Position';
            }
        }
        
        return $matches;
    }

    public function getJobStatus($id) {
        return response()->json(JobStatus::find($id));
    }

    public function matchVacancyToCandidates($vacancyId)
    {
        $vacancy = $this->fetchVacancy($vacancyId);
        if (!$vacancy) {
            return response()->json(['error' => 'Vacancy not found'], 404);
        }

        $candidates = $this->fetchAllCandidates();
        $matches = [];

        foreach ($candidates as $candidate) {
            $match = $this->calculateMatch($candidate, $vacancy);
            if ($match) {
                $matches[] = $match;
            }
        }

        usort($matches, fn($a, $b) => $b['match_score'] - $a['match_score']);

        return response()->json([
            'vacancy_id' => $vacancyId,
            'matches' => array_slice($matches, 0, 20) // Top 20 matches
        ]);
    }

    public function getMatches(Request $request)
    {
        $query = CandidateMatch::query();

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->has('vacancy_id')) {
            $query->where('vacancy_id', $request->vacancy_id);
        }

        if ($request->has('min_score')) {
            $query->byScore($request->min_score);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate allowed sort columns to prevent SQL injection or errors
        if (!in_array($sortBy, ['match_score', 'created_at', 'updated_at'])) {
            $sortBy = 'created_at';
        }
        
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $matches = $query->orderBy($sortBy, $sortOrder)->paginate(AppConstants::DEFAULT_PAGE_SIZE);

        return response()->json($matches);
    }

    public function generateQuestions($candidateId, $vacancyId)
    {
        $candidate = $this->fetchCandidate($candidateId);
        $vacancy = $this->fetchVacancy($vacancyId);

        if (!$candidate || !$vacancy) {
            return response()->json(['error' => 'Candidate or Vacancy not found'], 404);
        }

        // Get existing match to retrieve analysis
        $match = CandidateMatch::where('candidate_id', $candidateId)
            ->where('vacancy_id', $vacancyId)
            ->first();

        // If no match exists or no analysis, we proceed without analysis or try to calculate match first?
        // Let's use what we have. API allows nullable analysis.
        $analysis = $match ? $match->analysis : '';
        // If analysis is array (JSON cast), convert to string or extract relevant part? 
        $analysisText = is_array($analysis) ? json_encode($analysis) : (string)$analysis;

        $candidateProfile = $this->buildCandidateProfile($candidate);
        $jobRequirements = $this->buildJobRequirements($vacancy);

        try {
            $response = Http::timeout(60)->post($this->aiServiceUrl . '/api/generate-questions', [
                'candidate_profile' => $candidateProfile,
                'job_description' => $jobRequirements,
                'match_analysis' => $analysisText,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $questions = $data['questions'];

                // Get model info from AI service response or use default
                $modelUsed = $data['model_used'] ?? 'default';

                // TEMPORARY: Save without metadata due to persistent cache issue

                CandidateMatch::updateOrCreate(
                    [
                        'candidate_id' => $candidateId, 
                        'vacancy_id' => $vacancyId
                    ],
                    [
                        'interview_questions' => $questions
                        // 'questions_generated_at' => now(),
                        // 'questions_model' => $modelUsed
                    ]
                );

                return response()->json($questions);
            }
            
            Log::error("AI generation failed: " . $response->body());
            return response()->json(['error' => 'Failed to generate questions'], 500);

        } catch (\Exception $e) {
            Log::error('Question generation error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Save a discussion for a specific interview question
     */
    public function saveDiscussion(Request $request, $candidateId, $vacancyId, $questionIndex)
    {
        $request->validate([
            'discussion' => 'required|string',
        ]);

        $match = CandidateMatch::where('candidate_id', $candidateId)
            ->where('vacancy_id', $vacancyId)
            ->first();

        if (!$match) {
            return response()->json(['error' => 'Match not found'], 404);
        }

        // Get existing questions
        $questions = $match->interview_questions ?? [];
        
        // Ensure it's an array
        if (is_string($questions)) {
            $questions = json_decode($questions, true) ?? [];
        }

        // Check if question index exists
        if (!isset($questions[$questionIndex])) {
            return response()->json(['error' => 'Question not found at index ' . $questionIndex], 404);
        }

        // Add discussion to the question
        $questions[$questionIndex]['discussion'] = $request->discussion;

        // Save back to database
        $match->interview_questions = $questions;
        $match->save();

        Log::info("Saved discussion for question", [
            'candidate_id' => $candidateId,
            'vacancy_id' => $vacancyId,
            'question_index' => $questionIndex
        ]);

        return response()->json([
            'message' => 'Discussion saved successfully',
            'question' => $questions[$questionIndex]
        ]);
    }

    public function apply(Request $request) 
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'candidate_id' => 'required|integer',
            'vacancy_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidateId = $request->candidate_id;
        $vacancyId = $request->vacancy_id;

        // Fetch candidate/vacancy to ensure they exist
        $candidate = $this->fetchCandidate($candidateId);
        $vacancy = $this->fetchVacancy($vacancyId);

        if (!$candidate || !$vacancy) {
            return response()->json(['error' => 'Candidate or Vacancy not found'], 404);
        }

        // Calculate match (or retrieve from cache/DB)
        $matchData = $this->calculateMatch($candidate, $vacancy);
        
        if (!$matchData) {
            return response()->json(['error' => 'Could not process application'], 500);
        }

        // Explicitly set status to 'applied'
        CandidateMatch::updateOrCreate(
            [
                'candidate_id' => $candidateId,
                'vacancy_id' => $vacancyId
            ],
            ['status' => 'applied']
        );
        
        Cache::forget("match_{$candidateId}_{$vacancyId}");

        return response()->json(['message' => 'Application submitted successfully', 'match' => $matchData]);
    }

    public function dismissMatch($candidateId, $vacancyId)
    {
        CandidateMatch::updateOrCreate(
            [
                'candidate_id' => $candidateId,
                'vacancy_id' => $vacancyId
            ],
            ['status' => 'dismissed']
        );
        
        // Clear cache so next fetch sees it as dismissed (though logic handles DB state too)
        Cache::forget("match_{$candidateId}_{$vacancyId}");

        return response()->json(['message' => 'Match dismissed']);
    }

    public function restoreMatch($candidateId, $vacancyId)
    {
        CandidateMatch::updateOrCreate(
            [
                'candidate_id' => $candidateId,
                'vacancy_id' => $vacancyId
            ],
            ['status' => 'pending'] // Revert to pending
        );
        
        Cache::forget("match_{$candidateId}_{$vacancyId}");

        return response()->json(['message' => 'Match restored']);
    }

    public function clearMatches()
    {
        Log::info("Clearing all matches - database and cache");
        
        // Clear from database
        $deletedCount = CandidateMatch::count();
        CandidateMatch::truncate();
        
        // Clear Redis cache
        Cache::flush();
        
        Log::info("Matches cleared", ['deleted_count' => $deletedCount]);
        
        return response()->json([
            'message' => 'All matches cleared successfully',
            'deleted_count' => $deletedCount
        ]);
    }

    protected function calculateMatch($candidate, $vacancy)
    {
        $cacheKey = "match_{$candidate['id']}_{$vacancy['id']}";

        // First check database for existing status to avoid re-evaluating dismissed matches
        $existingMatch = CandidateMatch::where('candidate_id', $candidate['id'])
            ->where('vacancy_id', $vacancy['id'])
            ->first();

        if ($existingMatch && $existingMatch->status === 'dismissed') {
            Log::info("Skipping dismissed match", ['candidate' => $candidate['id'], 'vacancy' => $vacancy['id']]);
            return $existingMatch->toArray();
        }

        return Cache::remember($cacheKey, 3600, function () use ($candidate, $vacancy, $existingMatch) {
            // Double check inside cache closure just in case
            if ($existingMatch && $existingMatch->status === 'dismissed') {
                return $existingMatch->toArray();
            }

            $candidateProfile = $this->buildCandidateProfile($candidate);
            $jobRequirements = $this->buildJobRequirements($vacancy);

            $maxRetries = (int) ConfigurationService::get('matching.max_retry_attempts', 3);
            $minScoreThreshold = (int) ConfigurationService::get('matching.min_score_threshold', 40);
            $finalMatchData = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(AppConstants::API_TIMEOUT)->post($this->aiServiceUrl . '/api/match', [
                        'candidate_profile' => $candidateProfile,
                        'job_requirements' => $jobRequirements,
                    ]);

                    if ($response->successful()) {
                        $aiResult = $response->json();
                        
                        $score = isset($aiResult['match_score']) ? (int)$aiResult['match_score'] : 0;
                        
                        // Filter out low scores immediately, no need to retry
                        if ($score < $minScoreThreshold) {
                            return null;
                        }

                        $analysis = $aiResult['analysis'] ?? '';
                        
                        // Check if we have a recommendation
                        $hasRecommendation = stripos($analysis, 'RECOMMENDATION:') !== false;

                        // Prepare match data
                        $matchData = [
                            'candidate_id' => $candidate['id'],
                            'vacancy_id' => $vacancy['id'],
                            'vacancy_title' => $vacancy['title'] ?? 'Untitled Vacancy',
                            'match_score' => $score,
                            'analysis' => $analysis,
                            'status' => 'pending',
                        ];

                        // Keep this result as a candidate
                        $finalMatchData = $matchData;

                        if ($hasRecommendation) {
                            // Perfect result, stop retrying
                            break;
                        }

                        Log::warning("Match analysis missing RECOMMENDATION. Retrying...", [
                            'candidate' => $candidate['id'], 
                            'attempt' => $attempt
                        ]);
                        
                    }
                } catch (\Exception $e) {
                    Log::error('Matching error (Attempt ' . $attempt . '): ' . $e->getMessage());
                }
            }

            if ($finalMatchData) {
                // Try to store in database
                try {
                    CandidateMatch::updateOrCreate(
                        [
                            'candidate_id' => $candidate['id'],
                            'vacancy_id' => $vacancy['id']
                        ],
                        $finalMatchData
                    );
                } catch (\Exception $dbError) {
                    Log::warning('Failed to save match to database: ' . $dbError->getMessage());
                }

                return $finalMatchData;
            }

            return null;
        });
    }

    protected function fetchCandidate($id)
    {
        try {
            $response = Http::get("{$this->candidateServiceUrl}/api/candidates/{$id}");
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function fetchVacancy($id)
    {
        try {
            $response = Http::get("{$this->vacancyServiceUrl}/api/vacancies/{$id}");
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function fetchAllOpenVacancies()
    {
        try {
            $response = Http::get("{$this->vacancyServiceUrl}/api/vacancies?status=open");
            return $response->successful() ? $response->json()['data'] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function fetchAllCandidates()
    {
        try {
            $response = Http::get("{$this->candidateServiceUrl}/api/candidates");
            return $response->successful() ? $response->json()['data'] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function buildCandidateProfile($candidate)
    {
        // Parse JSON fields if they're strings
        $skills = $candidate['skills'] ?? [];
        if (is_string($skills)) {
            $skills = json_decode($skills, true) ?? [];
        }
        $skillsList = is_array($skills) ? implode(', ', $skills) : '';
        
        $experience = $candidate['experience'] ?? [];
        if (is_string($experience)) {
            $experience = json_decode($experience, true) ?? [];
        }
        
        $education = $candidate['education'] ?? [];
        if (is_string($education)) {
            $education = json_decode($education, true) ?? [];
        }
        
        // Build experience summary
        $expText = '';
        if (is_array($experience) && !empty($experience)) {
            foreach ($experience as $exp) {
                $title = $exp['title'] ?? 'Unknown Role';
                $company = $exp['company'] ?? 'Unknown Company';
                $duration = $exp['duration'] ?? '';
                $expText .= "- {$title} at {$company} ({$duration})\n";
            }
        }
        
        // Build education summary
        $eduText = '';
        if (is_array($education) && !empty($education)) {
            foreach ($education as $edu) {
                $degree = $edu['degree'] ?? 'Unknown Degree';
                $institution = $edu['institution'] ?? 'Unknown Institution';
                $year = $edu['year'] ?? '';
                $eduText .= "- {$degree} from {$institution} ({$year})\n";
            }
        }
        
        $yearsExp = $candidate['years_of_experience'] ?? 'Not specified';
        $name = $candidate['name'] ?? 'Candidate';
        $summary = $candidate['summary'] ?? '';
        
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

    protected function buildJobRequirements($vacancy)
    {
        $skills = $vacancy['required_skills'] ?? [];
        if (is_string($skills)) {
            $skills = json_decode($skills, true) ?? [];
        }
        $skillsList = is_array($skills) ? implode(', ', $skills) : '';
        
        return <<<REQUIREMENTS
        Position: {$vacancy['title']}
        Level: {$vacancy['experience_level']}
        Required Skills: {$skillsList}
        Requirements: {$vacancy['description']}
        REQUIREMENTS;
    }

    protected function extractStrengths($analysis)
    {
        preg_match('/STRENGTHS:\s*(.+?)(?=GAPS:|$)/s', $analysis, $matches);
        return $matches[1] ?? '';
    }

    protected function extractGaps($analysis)
    {
        preg_match('/GAPS:\s*(.+?)(?=RECOMMENDATION:|$)/s', $analysis, $matches);
        return $matches[1] ?? '';
    }

    protected function extractRecommendation($analysis)
    {
        preg_match('/RECOMMENDATION:\s*(.+?)$/s', $analysis, $matches);
        return $matches[1] ?? '';
    }
}

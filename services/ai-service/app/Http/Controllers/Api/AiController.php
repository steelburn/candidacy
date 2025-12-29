<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIProviderFactory;
use App\Services\JsonParsingService;
use App\Services\PromptFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AiController - Centralized AI operations for the Candidacy platform.
 * 
 * Provides AI-powered functionality including:
 * - CV/Resume parsing and data extraction
 * - Job description generation
 * - Candidate-vacancy matching with scoring
 * - Interview question generation
 * - Question discussion/analysis
 * 
 * Supports multiple AI providers via AIProviderFactory (Ollama, OpenRouter).
 * Refactored to use dedicated services for Parsing and Prompts.
 * 
 * @package App\Http\Controllers\Api
 * @author Candidacy Development Team
 */
class AiController extends Controller
{
    protected $aiProvider;
    protected $jsonParser;
    protected $promptFactory;

    public function __construct(JsonParsingService $jsonParser, PromptFactory $promptFactory)
    {
        $this->aiProvider = new AIProviderFactory();
        $this->jsonParser = $jsonParser;
        $this->promptFactory = $promptFactory;
    }

    public function parseCv(Request $request)
    {
        Log::info("CV parsing request received", [
            'text_length' => strlen($request->text ?? '')
        ]);
        
        $request->validate([
            'text' => 'required|string',
        ]);

        // Clean up Docling/XML tags from the input text to reduce noise for the AI
        $cleanedText = $this->stripDoclingTags($request->text);

        $prompt = $this->promptFactory->buildCvParsingPrompt($cleanedText);
        
        $response = $this->aiProvider->generate($prompt);

        Log::info("CV parsing completed", [
            'response_length' => strlen($response ?? ''),
            'has_response' => !empty($response)
        ]);

        $parsedData = $this->jsonParser->extractJsonFromResponse($response);
        
        // If extraction failed, try to parse the raw response directly after stripping comments
        if ($parsedData === null && !empty($response)) {
            try {
                $cleanedResponse = $this->jsonParser->stripJsonComments($response);
                // Also apply repair/balance in fallback
                $repairedResponse = $this->jsonParser->repairJson($cleanedResponse);
                $parsedData = json_decode($repairedResponse, true);
            } catch (\Exception $e) {
                // Squelch here, will handle below
            }
        }
        
        // Final fallback: Ask AI to repair its own output (Self-Healing)
        // This handles cases like Gemma returning Markdown text instead of JSON
        if ($parsedData === null && !empty($response)) {
             Log::warning("Initial parsing failed. Attempting AI-based repair loop.", [
                 'excerpt' => substr($response, 0, 100)
             ]);
             $parsedData = $this->attemptJsonRepairWithAi($response);
        }
        
        Log::info("CV data extracted", [
            'has_parsed_data' => $parsedData !== null,
            'name' => $parsedData['name'] ?? 'Unknown',
            'email' => $parsedData['email'] ?? 'Not found',
            'skills_count' => count($parsedData['skills'] ?? []),
            'experience_count' => count($parsedData['experience'] ?? [])
        ]);

        return response()->json([
            'parsed_data' => $parsedData,
            'raw_response' => $parsedData === null ? $response : null // Only include raw if parsing failed
        ]);
    }

    /**
     * Uses the AI to convert malformed text/JSON into valid JSON.
     * Useful for models like Gemma that sometimes output Markdown summaries.
     */
    protected function attemptJsonRepairWithAi($malformedText)
    {
        try {
            // Limit text to avoid context window overflow if massive garbage
            $textToRepair = substr($malformedText, 0, 8000); 
            
            $prompt = $this->promptFactory->buildJsonRepairPrompt($textToRepair);
            
            // Use generating model again (or could use a smarter model if available)
            $response = $this->aiProvider->generate($prompt);
            
            Log::info("AI Repair Loop Completed", [
                'repair_response_length' => strlen($response)
            ]);
            
            // Try extracting from repair response
            return $this->jsonParser->extractJsonFromResponse($response);
            
        } catch (\Exception $e) {
            Log::error("AI Repair Loop Failed", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function generateJobDescription(Request $request)
    {
        Log::info("Job description generation request", [
            'title' => $request->title,
            'department' => $request->department ?? 'Not specified'
        ]);
        
        $request->validate([
            'title' => 'required|string',
            'department' => 'nullable|string',
            'location' => 'nullable|string',
            'work_mode' => 'nullable',
            'type' => 'nullable|string',
            'level' => 'nullable|string',
            'skills' => 'nullable|array',
        ]);

        $prompt = $this->promptFactory->buildJdGenerationPrompt($request->all());
        
        $response = $this->aiProvider->generate($prompt);

        Log::info("Job description generated", [
            'title' => $request->title,
            'response_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'job_description' => $response,
        ]);
    }

    public function generateScreeningQuestions(Request $request)
    {
        Log::info("Screening questions generation request", [
            'title' => $request->title
        ]);

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $prompt = $this->promptFactory->buildScreeningQuestionsPrompt($request->all());

        $response = $this->aiProvider->generate($prompt);

        Log::info("Screening questions generated", [
            'response_length' => strlen($response ?? '')
        ]);

        // Use robust text parsing
        $questions = $this->jsonParser->parseScreeningQuestionsFromText($response);

        // Fallback: If parsing return less than 1 question, try to just dump raw if it looks like there's content
        if (empty($questions) && !empty($response)) {
             Log::warning("Failed to parse screening questions text block", ['response' => $response]);
             // Last resort fallback
             $questions[] = [
                'question_text' => "Generated (Raw): " . substr($response, 0, 200) . "...",
                'question_type' => 'text',
                'options' => null
            ];
        }

        return response()->json($questions ?? []);
    }

    public function matchCandidateToVacancy(Request $request)
    {
        Log::info("Match request received", [
            'candidate_profile_length' => strlen($request->candidate_profile ?? ''),
            'job_requirements_length' => strlen($request->job_requirements ?? '')
        ]);
        
        $request->validate([
            'candidate_profile' => 'required|string',
            'job_requirements' => 'required|string',
        ]);

        $candidateProfile = $this->cleanInput($request->candidate_profile);
        $jobRequirements = $this->cleanInput($request->job_requirements);

        Log::info("Match request processing", [
            'candidate_cleaned_length' => strlen($candidateProfile),
            'job_cleaned_length' => strlen($jobRequirements)
        ]);

        $prompt = $this->promptFactory->buildMatchingPrompt(
            $candidateProfile,
            $jobRequirements
        );
        
        // Use the faster matching model for quicker results
        $response = $this->aiProvider->generateForMatching($prompt);

        $matchScore = $this->jsonParser->extractMatchScore($response);
        
        Log::info("Match calculation completed", [
            'match_score' => $matchScore,
            'analysis_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'match_score' => $matchScore,
            'analysis' => $response
        ]);
    }

    /**
     * Generate AI discussion points for an interview question
     */
    public function discussQuestion(Request $request)
    {
        Log::info("Question discussion request", [
            'question_length' => strlen($request->question ?? '')
        ]);
        
        $request->validate([
            'question' => 'required|string',
            'question_type' => 'nullable|string',
            'job_title' => 'nullable|string',
            'candidate_name' => 'nullable|string',
            'context' => 'nullable|string',
        ]);

        $prompt = $this->promptFactory->buildQuestionDiscussionPrompt($request->all());
        
        $response = $this->aiProvider->generate($prompt);

        Log::info("Question discussion generated", [
            'response_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'discussion' => $response,
        ]);
    }


    public function generateInterviewQuestions(Request $request)
    {
        Log::info("Interview questions generation request", [
            'candidate_profile_length' => strlen($request->candidate_profile ?? ''),
            'match_analysis_length' => strlen($request->match_analysis ?? '')
        ]);
        
        $request->validate([
            'candidate_profile' => 'required|string',
            'job_description' => 'required|string',
            'match_analysis' => 'nullable|string',
        ]);

        $prompt = $this->promptFactory->buildInterviewQuestionsPrompt(
            $request->candidate_profile,
            $request->job_description,
            $request->match_analysis
        );
        
        // Use questionnaire-specific model
        $response = $this->aiProvider->generateForQuestionnaire($prompt);
        
        // Get the model name that was used
        $provider = $this->aiProvider->getProvider();
        $modelUsed = 'default';
        if (method_exists($provider, 'getQuestionnaireModelName')) {
            $modelUsed = $provider->getQuestionnaireModelName();
        } elseif (method_exists($provider, 'getModelName')) {
            $modelUsed = $provider->getModelName();
        }

        Log::info("Interview questions generated", [
            'response_length' => strlen($response ?? ''),
            'model_used' => $modelUsed
        ]);
        
        // Use text parser instead of JSON extraction
        $questions = $this->jsonParser->parseQuestionsFromText($response);
        
        if (empty($questions)) {
            // Fallback if parsing fails (try JSON as last resort or error)
            $jsonParsed = $this->jsonParser->extractJsonFromResponse($response);
            if ($jsonParsed) {
                $questions = $jsonParsed;
            } else {
                 $questions = [['question' => 'Could not structure questions. Raw response: ' . $response]];
            }
        }

        return response()->json([
            'questions' => $questions,
            'model_used' => $modelUsed,
            'raw_response' => $response
        ]);
    }

    private function cleanInput($input)
    {
        if (empty($input)) return '';
        // Remove non-printable characters except newlines, tabs, carriage returns
        // Also remove weird control characters that might confuse LLM
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        return trim($cleaned);
    }

    /**
     * Remove XML-like tags (e.g. <text>, <loc_...>) from document parser output.
     */
    private function stripDoclingTags($text)
    {
        if (empty($text)) return '';
        
        // Remove all tags <...>
        $text = preg_replace('/<[^>]+>/', ' ', $text);
        
        // Collapse multiple spaces but preserve newlines
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        return trim($text);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
 * 
 * @package App\Http\Controllers\Api
 * @author Candidacy Development Team
 */
class AiController extends Controller
{
    protected $aiProvider;

    public function __construct()
    {
        $this->aiProvider = new AIProviderFactory();
    }

    public function parseCv(Request $request)
    {
        Log::info("CV parsing request received", [
            'text_length' => strlen($request->text ?? '')
        ]);
        
        $request->validate([
            'text' => 'required|string',
        ]);

        $prompt = $this->buildCvParsingPrompt($request->text);
        

        
        $response = $this->aiProvider->generate($prompt);

        Log::info("CV parsing completed", [
            'response_length' => strlen($response ?? ''),
            'has_response' => !empty($response)
        ]);

        $parsedData = $this->extractJsonFromResponse($response);
        
        // If extraction failed, try to parse the raw response directly after stripping comments
        if ($parsedData === null && !empty($response)) {
            try {
                $cleanedResponse = $this->stripJsonComments($response);
                $parsedData = json_decode($cleanedResponse, true);
            } catch (\Exception $e) {
                Log::warning("Failed to parse AI response after comment stripping", [
                    'error' => $e->getMessage()
                ]);
            }
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

        $prompt = $this->buildJdGenerationPrompt($request->all());
        

        
        $response = $this->aiProvider->generate($prompt);

        Log::info("Job description generated", [
            'title' => $request->title,
            'response_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'job_description' => $response,
        ]);
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

        $prompt = $this->buildMatchingPrompt(
            $candidateProfile,
            $jobRequirements
        );
        

        
        // Use the faster matching model for quicker results
        $response = $this->aiProvider->generateForMatching($prompt);

        $matchScore = $this->extractMatchScore($response);
        
        Log::info("Match calculation completed", [
            'match_score' => $matchScore,
            'analysis_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'match_score' => $matchScore,
            'analysis' => $response
        ]);
    }

    protected function buildCvParsingPrompt($cvText)
    {
        return <<<PROMPT
You are a CV/Resume parser. Extract information and return ONLY valid JSON.

CRITICAL RULES:
1. Return ONLY the JSON object - no explanations, no comments, no markdown
2. Do NOT use // comments or /* */ comments anywhere
3. Do NOT use placeholders like "..." or "{ ... }"
4. Do NOT write "Additional samples not shown" or similar text
5. If data is missing, use null or empty array []
6. ALL strings must use proper escaping for quotes

REQUIRED JSON STRUCTURE:
{
  "name": "Full Name or null",
  "email": "email@example.com or null",
  "phone": "+1234567890 or null",
  "summary": "Professional summary or null",
  "skills": ["skill1", "skill2"] or [],
  "experience": [
    {
      "title": "Job Title",
      "company": "Company Name",
      "duration": "Jan 2020 - Present",
      "description": "Job description"
    }
  ] or [],
  "education": [
    {
      "degree": "Degree Name",
      "institution": "University Name",
      "year": "2019"
    }
  ] or [],
  "certifications": ["Cert1", "Cert2"] or [],
  "years_of_experience": 5 or null
}

EXTRACT ALL EXPERIENCES AND EDUCATION - do not limit to samples.
If you cannot extract a field, use null or [].

CV TEXT:
{$cvText}

Return the JSON now:
PROMPT;
    }

    protected function buildJdGenerationPrompt($data)
    {
        $title = $data['title'];
        $department = $data['department'] ?? 'General';
        $level = $data['level'] ?? 'Mid-level';
        $skills = isset($data['skills']) ? implode(', ', $data['skills']) : 'relevant skills';

        $location = $data['location'] ?? 'Not specified';
        $workMode = is_array($data['work_mode']) ? implode(', ', $data['work_mode']) : ($data['work_mode'] ?? 'Not specified');
        $type = $data['type'] ?? 'Full-time';

        return <<<PROMPT
Generate a comprehensive job description for the following position:

Position: {$title}
Department: {$department}
Location: {$location}
Work Mode: {$workMode}
Employment Type: {$type}
Level: {$level}
Required Skills: {$skills}

Please create a professional job description including:
1. Job Overview (2-3 sentences, tailored to a {$level} position, mentioning {$location} and {$workMode} setup)
2. Key Responsibilities (5-7 bullet points)
3. Required Qualifications (5-7 bullet points)
4. Preferred Qualifications (3-5 bullet points)
5. What We Offer (3-5 bullet points)

Make it engaging and professional.
PROMPT;
    }

    protected function buildMatchingPrompt($candidateProfile, $jobRequirements)
    {
        return <<<PROMPT
You are a recruitment expert. Evaluate how well the candidate matches the job requirements.

JOB REQUIREMENTS:
{$jobRequirements}

CANDIDATE PROFILE:
{$candidateProfile}

CRITICAL RULES:
1. Base your evaluation ONLY on the Candidate Profile provided.
2. Do NOT hallucinate or invent skills that are not explicitly mentioned in the profile.
3. If the candidate lacks a required skill, explicitly state it as a GAP.

CRITICAL: You MUST provide your evaluation in EXACTLY this format. Do not skip any section:

SCORE: [number from 0 to 100]

STRENGTHS:
- [first key strength/match]
- [second key strength/match]
- [third key strength/match]

GAPS:
- [first missing skill or gap]
- [second missing skill or gap]
- [third missing skill or gap]

RECOMMENDATION:
[Your detailed hiring recommendation - whether to hire, interview, or reject, and why]

IMPORTANT: Start your response with "SCORE:" and include ALL four sections (SCORE, STRENGTHS, GAPS, RECOMMENDATION). Use bullet points (-) for STRENGTHS and GAPS sections.
PROMPT;
    }

    protected function extractMatchScore($response)
    {
        // Extract score from response (case insensitive, handle optional max score like 80/100)
        if (preg_match('/SCORE:\s*(\d+)/i', $response, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
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

        $prompt = $this->buildQuestionDiscussionPrompt($request->all());
        
        $response = $this->aiProvider->generate($prompt);

        Log::info("Question discussion generated", [
            'response_length' => strlen($response ?? '')
        ]);

        return response()->json([
            'discussion' => $response,
        ]);
    }

    /**
     * Build prompt for question discussion
     */
    protected function buildQuestionDiscussionPrompt($data)
    {
        $question = $data['question'];
        $type = $data['question_type'] ?? 'general';
        $jobTitle = $data['job_title'] ?? 'the position';
        $candidateName = $data['candidate_name'] ?? 'the candidate';
        $context = $data['context'] ?? '';

        $contextSection = $context ? "\nAdditional Context: {$context}" : '';

        return <<<PROMPT
You are an expert interviewer helping prepare for a candidate interview.

INTERVIEW QUESTION: "{$question}"

Question Type: {$type}
Position: {$jobTitle}
Candidate: {$candidateName}{$contextSection}

Please provide a comprehensive discussion guide for this interview question:

1. **Purpose**: What is this question designed to assess? (1-2 sentences)

2. **What to Listen For**: Key indicators of a strong answer (3-4 bullet points)

3. **Red Flags**: Warning signs in weak or concerning answers (2-3 bullet points)

4. **Follow-up Questions**: Suggested probing questions to dig deeper (2-3 examples)

5. **Scoring Guide**:
   - Excellent (9-10): Characteristics of an outstanding response
   - Good (7-8): What a solid answer looks like
   - Acceptable (5-6): Minimum acceptable response
   - Poor (1-4): Indicators of an inadequate answer

Keep your response concise but actionable. Use markdown formatting.
PROMPT;
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

        $prompt = $this->buildInterviewQuestionsPrompt(
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
        $questions = $this->parseQuestionsFromText($response);
        
        if (empty($questions)) {
            // Fallback if parsing fails (try JSON as last resort or error)
            $jsonParsed = $this->extractJsonFromResponse($response);
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

    protected function buildInterviewQuestionsPrompt($profile, $jd, $analysis)
    {
        return <<<PROMPT
You are an expert technical interviewer. Create a tailored interview questionnaire for a candidate.

JOB DESCRIPTION:
{$jd}

CANDIDATE PROFILE:
{$profile}

MATCH ANALYSIS:
{$analysis}

CRITICAL: Generate EXACTLY 6 questions.
Use the following format for EACH question (do not use JSON):

QUESTION: <The question text>
TYPE: <technical|behavioral|situational>
DIFFICULTY: <easy|medium|hard>
CONTEXT: <Why this question matters>
HINT: <What to look for in the answer>
---

Example:
QUESTION: Can you explain dependency injection?
TYPE: technical
DIFFICULTY: easy
CONTEXT: Core concept needed for our backend.
HINT: Look for decoupling and testability.
---

Requirements:
1. No Markdown or bold text.
2. Strictly follow the labels (QUESTION, TYPE, DIFFICULTY, CONTEXT, HINT).
3. Separate questions with "---".
PROMPT;
    }

    protected function parseQuestionsFromText($text)
    {
        $questions = [];
        // Split by separator
        $blocks = explode('---', $text);
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            $item = [
                'question' => $this->extractLine($block, 'QUESTION'),
                'type' => strtolower($this->extractLine($block, 'TYPE') ?: 'technical'),
                'difficulty' => strtolower($this->extractLine($block, 'DIFFICULTY') ?: 'medium'),
                'context' => $this->extractLine($block, 'CONTEXT'),
                'hint' => $this->extractLine($block, 'HINT'),
            ];
            
            if (!empty($item['question'])) {
                $questions[] = $item;
            }
        }
        
        return $questions;
    }

    private function extractLine($block, $label)
    {
        if (preg_match('/' . $label . ':\s*(.+)/i', $block, $matches)) {
            return trim($matches[1]);
        }
        return '';
    } 

    protected function extractJsonFromResponse($response)
    {
        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        
        // Find the start of JSON (either { or [)
        if (preg_match('/[\{\[]/', $response, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            
            // Find the last matching closing bracket/brace
            $lastClosing = max(strrpos($response, '}'), strrpos($response, ']'));
            
            if ($lastClosing !== false && $lastClosing > $start) {
                $jsonString = substr($response, $start, $lastClosing - $start + 1);
                
                // 1. Try decoding raw extracted string
                $decoded = json_decode($jsonString, true);
                if ($decoded !== null) {
                    return $decoded;
                }
                
                // 2. Clean comments and trailing commas
                $cleanJson = $this->stripJsonComments($jsonString);
                $decoded = json_decode($cleanJson, true);
                if ($decoded !== null) {
                    return $decoded;
                }
                
                // 3. Aggressive repair for common LLM syntax errors
                $repairedJson = $this->repairJson($cleanJson);
                $decoded = json_decode($repairedJson, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        }
        
        return null;
    }
    
    protected function stripJsonComments($jsonString)
    {
        // Remove single-line comments (// ...)
        // Use negative lookbehind to avoid removing // inside strings
        $jsonString = preg_replace('/(?<!")\/\/[^\n]*/', '', $jsonString);
        
        // Remove multi-line comments (/* ... */)
        $jsonString = preg_replace('/\/\*.*?\*\//s', '', $jsonString);
        
        // Clean up any trailing commas before closing braces/brackets
        $jsonString = preg_replace('/,\s*([}\]])/s', '$1', $jsonString);
        
        // Remove placeholder patterns like "{ ... }" or "..."
        $jsonString = preg_replace('/\{\s*\.\.\.\s*\}/s', '{}', $jsonString);
        $jsonString = preg_replace('/"\.\.\."/s', 'null', $jsonString);
        
        // Remove incomplete array/object markers
        $jsonString = preg_replace('/,\s*\]\s*\/\/[^\n]*Additional[^\n]*/', ']', $jsonString);
        
        return trim($jsonString);
    }
    
    protected function repairJson($json)
    {
        // 0. Fix smart quotes (curly quotes)
        $json = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $json);

        // 1. Fix single-quoted property names: 'key': -> "key":
        $json = preg_replace("/\s*'([^']+)'\s*:/", '"$1":', $json);
        
        // 2. Fix single-quoted values: : 'value' -> : "value"
        $json = preg_replace("/:\s*'([^']*)'(\s*[,}\]])/", ': "$1"$2', $json);
        
        // 3. Fix double-single-quoted values: ''value'' -> "value"
        $json = preg_replace("/:\s*''([^']*)''(\s*[,}\]])/", ': "$1"$2', $json);
        
        // 4. Ensure property names are quoted if they aren't (alphanumeric_dashed only)
        // key: value -> "key": value
        $json = preg_replace('/([{,]\s*)([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $json);
        
        // 5. Fix common encoding issues or escaped quotes that are double escaped
        $json = str_replace('\\"', '"', $json); // sometimes they double escape
        
        // 6. Fix double-double quotes: ""value"" -> "value"
        $json = preg_replace('/""([^"]*)""/', '"$1"', $json);
        
        return $json;
    }



    private function cleanInput($input)
    {
        if (empty($input)) return '';
        // Remove non-printable characters except newlines, tabs, carriage returns
        // Also remove weird control characters that might confuse LLM
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        return trim($cleaned);
    }
}

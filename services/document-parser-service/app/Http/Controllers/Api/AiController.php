<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        
        Log::info("CV data extracted", [
            'has_parsed_data' => $parsedData !== null,
            'name' => $parsedData['name'] ?? 'Unknown',
            'email' => $parsedData['email'] ?? 'Not found',
            'skills_count' => count($parsedData['skills'] ?? []),
            'experience_count' => count($parsedData['experience'] ?? [])
        ]);

        return response()->json([
            'parsed_data' => $parsedData,
            'raw_response' => $response
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
        
        $questions = $this->extractJsonFromResponse($response);
        
        if (!$questions) {
            // Fallback for when JSON extraction fails
             $questions = [['question' => 'Could not structure questions. Raw response: ' . $response]];
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

CRITICAL REQUIREMENTS:
1. Generate EXACTLY 6-8 questions
2. Question distribution: 40% technical, 30% behavioral, 30% situational
3. Difficulty progression: 2 easy, 3-4 medium, 2 hard
4. Return ONLY valid JSON - NO markdown code blocks, NO explanations, NO additional text

MANDATORY JSON STRUCTURE (copy this exact format):
[
  {
    "question": "Your question text here",
    "type": "technical",
    "difficulty": "easy",
    "context": "Why this question matters - what you're assessing",
    "hint": "What to listen for in a good answer"
  }
]

FIELD REQUIREMENTS:
- "question": String, 10-200 characters, ends with question mark
- "type": MUST be exactly one of: "technical", "behavioral", "situational"
- "difficulty": MUST be exactly one of: "easy", "medium", "hard"
- "context": String, 20-150 characters, explains assessment purpose
- "hint": String, 30-200 characters, interviewer guidance

VALIDATION EXAMPLE:
[
  {
    "question": "Can you describe your experience with microservices architecture?",
    "type": "technical",
    "difficulty": "medium",
    "context": "Candidate claims 5 years microservices experience but analysis shows limited documentation",
    "hint": "Listen for: specific technologies (Docker, Kubernetes), service communication patterns, database strategies, challenges overcome"
  },
  {
    "question": "Tell me about a time when you had to make a difficult technical decision under pressure?",
    "type": "behavioral",
    "difficulty": "medium",
    "context": "Assessing decision-making skills and ability to handle pressure in technical scenarios",
    "hint": "Look for: structured thinking process, consideration of trade-offs, stakeholder communication, outcome and lessons learned"
  }
]

CRITICAL: Your response must start with [ and end with ]. Return ONLY the JSON array. No other text.
PROMPT;
    }

    protected function buildCvParsingPrompt($cvText)
    {
        return <<<PROMPT
You are an AI assistant that extracts structured information from CVs/resumes.
Extract the following information from the CV text and return ONLY a valid JSON object:

{
  "name": "Full Name",
  "email": "email@example.com",
  "phone": "+1234567890",
  "summary": "Professional summary",
  "skills": ["skill1", "skill2", "skill3"],
  "experience": [
    {
      "title": "Job Title",
      "company": "Company Name",
      "duration": "Jan 2020 - Present",
      "description": "Job description"
    }
  ],
  "education": [
    {
      "degree": "Degree Name",
      "institution": "University Name",
      "year": "2019"
    }
  ],
  "certifications": ["Cert1", "Cert2"],
  "years_of_experience": 5
}

CV Text:
{$cvText}

Return ONLY the JSON object, no additional text:
PROMPT;
    }

    protected function buildJdGenerationPrompt($data)
    {
        $title = $data['title'];
        $department = $data['department'] ?? 'General';
        $level = $data['level'] ?? 'Mid-level';
        $skills = isset($data['skills']) ? implode(', ', $data['skills']) : 'relevant skills';

        return <<<PROMPT
Generate a comprehensive job description for the following position:

Position: {$title}
Department: {$department}
Level: {$level}
Required Skills: {$skills}

Please create a professional job description including:
1. Job Overview (2-3 sentences)
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

    protected function extractJsonFromResponse($response)
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);
        
        // Try to extract JSON array first (for interview questions)
        preg_match('/\[.*\]/s', $response, $arrayMatches);
        
        if (!empty($arrayMatches[0])) {
            $decoded = json_decode($arrayMatches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }
        
        // Fallback to object extraction
        preg_match('/\{.*\}/s', $response, $objectMatches);
        
        if (!empty($objectMatches[0])) {
            return json_decode($objectMatches[0], true);
        }

        return null;
    }

    protected function extractMatchScore($response)
    {
        // Extract score from response
        preg_match('/SCORE:\s*(\d+)/', $response, $matches);
        
        return isset($matches[1]) ? (int)$matches[1] : 0;
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

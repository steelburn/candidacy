<?php

namespace App\Services;

class PromptFactory
{
    public function buildCvParsingPrompt($cvText)
    {
        return <<<PROMPT
You are a CV/Resume parser. Extract information from the text below and return ONLY valid JSON.
Do not include comments, conversational text, markdown formatting, or explanations.

CRITICAL INSTRUCTIONS:
1. Output ONLY a valid JSON object. Start with { and end with }.
2. NO comments (`//` or `/* */`).
3. NO conversational fillers (e.g., "Here is the JSON").
4. NO ellipses (`...`) for brevity. Extract ALL data found in the text.
5. If a field is missing, use null or [], do not explain why it is missing.
6. Do NOT escape underscores in keys (e.g., use "years_of_experience", NOT "years\_of\_experience").
7. Ensure all property names and string values are enclosed in double quotes (").

REQUIRED JSON STRUCTURE:
{
  "name": "Full Name or null",
  "email": "email@example.com or null",
  "phone": "phone number or null",
  "summary": "Professional summary or null",
  "skills": ["skill1", "skill2"],
  "experience": [
    {
      "title": "Job Title",
      "company": "Company Name",
      "duration": "Duration/Dates",
      "description": "Job description"
    }
  ],
  "education": [
    {
      "degree": "Degree Name",
      "institution": "University Name",
      "year": "Year"
    }
  ],
  "certifications": ["Cert1", "Cert2"],
  "years_of_experience": 5
}

CV TEXT:
{$cvText}
PROMPT;
    }

    public function buildJdGenerationPrompt($data)
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

    public function buildScreeningQuestionsPrompt($data)
    {
        $title = $data['title'];
        $description = $data['description'] ?? '';
        
        // Truncate description if too long to avoid token limits
        if (strlen($description) > 1000) {
            $description = substr($description, 0, 1000) . "...";
        }

        return <<<PROMPT
You are a technical recruiter. Create 3-5 screening questions for the position of "{$title}".
Base the questions on the following job description context (if available):
"{$description}"

CRITICAL: Generate EXACTLY 3 to 5 questions.
Use the following format for EACH question (do not use JSON, do not use Markdown code blocks):

QUESTION: <The question text>
TYPE: <text|boolean|multiple_choice>
OPTIONS: <comma separated options, only if type is multiple_choice>
---

Example:
QUESTION: do you have 3 years of react experience?
TYPE: boolean
OPTIONS: 
---
QUESTION: which cloud provider do you use?
TYPE: multiple_choice
OPTIONS: AWS, Azure, GCP
---

Requirements:
1. No Markdown or bold text.
2. Strictly follow the labels (QUESTION, TYPE, OPTIONS).
3. Separate questions with "---".
PROMPT;
    }

    public function buildMatchingPrompt($candidateProfile, $jobRequirements)
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

    public function buildQuestionDiscussionPrompt($data)
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

    public function buildInterviewQuestionsPrompt($profile, $jd, $analysis)
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

    public function buildJsonRepairPrompt($text)
    {
        return <<<PROMPT
You are a Data converter. The following text contains CV/Resume data but is formatted as text/markdown instead of JSON.
Convert it into the STRICT JSON format below.

CRITICAL:
1. Return ONLY valid JSON.
2. No text, no markdown code blocks, no explanations.
3. Start with { and end with }.

REQUIRED JSON STRUCTURE:
{
  "name": "Full Name",
  "email": "email",
  "phone": "phone",
  "summary": "summary text",
  "skills": ["skill1", "skill2"],
  "experience": [
    {
      "title": "Title",
      "company": "Company",
      "duration": "Dates",
      "description": "Description"
    }
  ],
  "education": [
    {
      "degree": "Degree",
      "institution": "Institution",
      "year": "Year"
    }
  ],
  "certifications": ["Cert"],
  "years_of_experience": number
}

INPUT TEXT TO CONVERT:
{$text}
PROMPT;
    }
}

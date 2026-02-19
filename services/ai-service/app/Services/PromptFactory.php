<?php

namespace App\Services;

class PromptFactory
{
    public function buildCvParsingPrompt($cvText)
    {
        return "You are a CV/Resume parser. Extract information from the text below and return ONLY valid JSON.\n" .
               "Do not include comments, conversational text, markdown formatting, or explanations.\n\n" .
               "RULES:\n" .
               "1. Output ONLY a valid JSON object.\n" .
               "2. NO comments, NO trailing commas, NO conversational fillers.\n" .
               "3. If a field is missing, use null or [].\n" .
               "4. \"years_of_experience\" must be a plain integer.\n" .
               "5. Extract EVERY skill and experience item found.\n\n" .
               "EXAMPLE:\n" .
               "CV TEXT:\n" .
               "Jane Smith\n" .
               "software@example.com\n" .
               "555-1234\n" .
               "San Francisco, CA\n" .
               "Senior Developer (2020-Present) at ACME Corp.\n" .
               "- Led team of 5\n" .
               "- Used React and Go\n" .
               "BS Computer Science, Stanford, 2015\n\n" .
               "JSON:\n" .
               "{\n" .
               "  \"name\": \"Jane Smith\",\n" .
               "  \"email\": \"software@example.com\",\n" .
               "  \"phone\": \"555-1234\",\n" .
               "  \"summary\": \"Senior Developer with experience in React and Go.\",\n" .
               "  \"skills\": [\"React\", \"Go\"],\n" .
               "  \"experience\": [\n" .
               "    {\n" .
               "      \"title\": \"Senior Developer\",\n" .
               "      \"company\": \"ACME Corp\",\n" .
               "      \"duration\": \"2020-Present\",\n" .
               "      \"description\": \"Led team of 5. Used React and Go.\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"education\": [\n" .
               "    {\n" .
               "      \"degree\": \"BS Computer Science\",\n" .
               "      \"institution\": \"Stanford University\",\n" .
               "      \"year\": \"2015\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"certifications\": [],\n" .
               "  \"years_of_experience\": 8\n" .
               "}\n\n" .
               "REQUIRED JSON STRUCTURE:\n" .
               "{\n" .
               "  \"name\": \"string or null\",\n" .
               "  \"email\": \"string or null\",\n" .
               "  \"phone\": \"string or null\",\n" .
               "  \"summary\": \"string or null\",\n" .
               "  \"skills\": [\"string\"],\n" .
               "  \"experience\": [\n" .
               "    {\n" .
               "      \"title\": \"string\",\n" .
               "      \"company\": \"string\",\n" .
               "      \"duration\": \"string\",\n" .
               "      \"description\": \"string\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"education\": [\n" .
               "    {\n" .
               "      \"degree\": \"string\",\n" .
               "      \"institution\": \"string\",\n" .
               "      \"year\": \"string\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"certifications\": [\"string\"],\n" .
               "  \"years_of_experience\": number\n" .
               "}\n\n" .
               "CV TEXT TO PARSE:\n" .
               $cvText;
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

        return "Generate a comprehensive job description for the following position:\n\n" .
               "Position: {$title}\n" .
               "Department: {$department}\n" .
               "Location: {$location}\n" .
               "Work Mode: {$workMode}\n" .
               "Employment Type: {$type}\n" .
               "Level: {$level}\n" .
               "Required Skills: {$skills}\n\n" .
               "Please create a professional job description including:\n" .
               "1. Job Overview (2-3 sentences, tailored to a {$level} position, mentioning {$location} and {$workMode} setup)\n" .
               "2. Key Responsibilities (5-7 bullet points)\n" .
               "3. Required Qualifications (5-7 bullet points)\n" .
               "4. Preferred Qualifications (3-5 bullet points)\n" .
               "5. What We Offer (3-5 bullet points)\n\n" .
               "Make it engaging and professional.";
    }

    public function buildScreeningQuestionsPrompt($data)
    {
        $title = $data['title'];
        $description = $data['description'] ?? '';
        
        // Truncate description if too long to avoid token limits
        if (strlen($description) > 1000) {
            $description = substr($description, 0, 1000) . "...";
        }

        return "You are a technical recruiter. Create 3-5 screening questions for the position of \"{$title}\".\n" .
               "Base the questions on the following job description context (if available):\n" .
               "\"{$description}\"\n\n" .
               "CRITICAL: Generate EXACTLY 3 to 5 questions.\n" .
               "Use the following format for EACH question (do not use JSON, do not use Markdown code blocks):\n\n" .
               "QUESTION: <The question text>\n" .
               "TYPE: <text|boolean|multiple_choice>\n" .
               "OPTIONS: <comma separated options, only if type is multiple_choice>\n" .
               "---\n\n" .
               "Example:\n" .
               "QUESTION: do you have 3 years of react experience?\n" .
               "TYPE: boolean\n" .
               "OPTIONS: \n" .
               "---\n" .
               "QUESTION: which cloud provider do you use?\n" .
               "TYPE: multiple_choice\n" .
               "OPTIONS: AWS, Azure, GCP\n" .
               "---\n\n" .
               "Requirements:\n" .
               "1. No Markdown or bold text.\n" .
               "2. Strictly follow the labels (QUESTION, TYPE, OPTIONS).\n" .
               "3. Separate questions with \"---\".";
    }

    public function buildMatchingPrompt($candidateProfile, $jobRequirements)
    {
        return "You are a recruitment expert. Evaluate how well the candidate matches the job requirements.\n\n" .
               "JOB REQUIREMENTS:\n" .
               "{$jobRequirements}\n\n" .
               "CANDIDATE PROFILE:\n" .
               "{$candidateProfile}\n\n" .
               "CRITICAL RULES:\n" .
               "1. Base your evaluation ONLY on the Candidate Profile provided.\n" .
               "2. Infer educational equivalence (e.g., BS = Bachelor of Science, CS = Computer Science).\n" .
               "3. Do not penalize for standard abbreviations.\n" .
               "4. Do NOT hallucinate skills that are clearly absent, but you may infer standard variations.\n" .
               "5. If the candidate lacks a required skill, explicitly state it as a GAP.\n\n" .
               "CRITICAL: You MUST provide your evaluation in EXACTLY this format. Do not skip any section:\n\n" .
               "SCORE: [number from 0 to 100]\n\n" .
               "STRENGTHS:\n" .
               "- [first key strength/match]\n" .
               "- [second key strength/match]\n" .
               "- [third key strength/match]\n\n" .
               "GAPS:\n" .
               "- [first missing skill or gap]\n" .
               "- [second missing skill or gap]\n" .
               "- [third missing skill or gap]\n\n" .
               "RECOMMENDATION:\n" .
               "[Your detailed hiring recommendation - whether to hire, interview, or reject, and why]\n\n" .
               "IMPORTANT: Start your response with \"SCORE:\" and include ALL four sections (SCORE, STRENGTHS, GAPS, RECOMMENDATION). Use bullet points (-) for STRENGTHS and GAPS sections.";
    }

    public function buildQuestionDiscussionPrompt($data)
    {
        $question = $data['question'];
        $type = $data['question_type'] ?? 'general';
        $jobTitle = $data['job_title'] ?? 'the position';
        $candidateName = $data['candidate_name'] ?? 'the candidate';
        $context = $data['context'] ?? '';

        $contextSection = $context ? "\nAdditional Context: {$context}" : '';

        return "You are an expert interviewer helping prepare for a candidate interview.\n\n" .
               "INTERVIEW QUESTION: \"{$question}\"\n\n" .
               "Question Type: {$type}\n" .
               "Position: {$jobTitle}\n" .
               "Candidate: {$candidateName}{$contextSection}\n\n" .
               "Please provide a comprehensive discussion guide for this interview question:\n\n" .
               "1. **Purpose**: What is this question designed to assess? (1-2 sentences)\n\n" .
               "2. **What to Listen For**: Key indicators of a strong answer (3-4 bullet points)\n\n" .
               "3. **Red Flags**: Warning signs in weak or concerning answers (2-3 bullet points)\n\n" .
               "4. **Follow-up Questions**: Suggested probing questions to dig deeper (2-3 examples)\n\n" .
               "5. **Scoring Guide**:\n" .
               "   - Excellent (9-10): Characteristics of an outstanding response\n" .
               "   - Good (7-8): What a solid answer looks like\n" .
               "   - Acceptable (5-6): Minimum acceptable response\n" .
               "   - Poor (1-4): Indicators of an inadequate answer\n\n" .
               "Keep your response concise but actionable. Use markdown formatting.";
    }

    public function buildInterviewQuestionsPrompt($profile, $jd, $analysis)
    {
        return "You are an expert technical interviewer. Create a tailored interview questionnaire for a candidate.\n\n" .
               "JOB DESCRIPTION:\n" .
               "{$jd}\n\n" .
               "CANDIDATE PROFILE:\n" .
               "{$profile}\n\n" .
               "MATCH ANALYSIS:\n" .
               "{$analysis}\n\n" .
               "CRITICAL: Generate EXACTLY 6 questions.\n" .
               "Use the following format for EACH question (do not use JSON):\n\n" .
               "QUESTION: <The question text>\n" .
               "TYPE: <technical|behavioral|situational>\n" .
               "DIFFICULTY: <easy|medium|hard>\n" .
               "CONTEXT: <Why this question matters>\n" .
               "HINT: <What to look for in the answer>\n" .
               "---\n\n" .
               "Example:\n" .
               "QUESTION: Can you explain dependency injection?\n" .
               "TYPE: technical\n" .
               "DIFFICULTY: easy\n" .
               "CONTEXT: Core concept needed for our backend.\n" .
               "HINT: Look for decoupling and testability.\n" .
               "---\n\n" .
               "Requirements:\n" .
               "1. No Markdown or bold text.\n" .
               "2. Strictly follow the labels (QUESTION, TYPE, DIFFICULTY, CONTEXT, HINT).\n" .
               "3. Separate questions with \"---\".";
    }

    public function buildJsonRepairPrompt($text)
    {
        return "You are a Data converter. The following text contains CV/Resume data but is formatted as text/markdown instead of JSON.\n" .
               "Convert it into the STRICT JSON format below.\n\n" .
               "CRITICAL:\n" .
               "1. Return ONLY valid JSON.\n" .
               "2. No text, no markdown code blocks, no explanations.\n" .
               "3. Start with { and end with }.\n\n" .
               "REQUIRED JSON STRUCTURE:\n" .
               "{\n" .
               "  \"name\": \"Full Name\",\n" .
               "  \"email\": \"email\",\n" .
               "  \"phone\": \"phone\",\n" .
               "  \"summary\": \"summary text\",\n" .
               "  \"skills\": [\"skill1\", \"skill2\"],\n" .
               "  \"experience\": [\n" .
               "    {\n" .
               "      \"title\": \"Title\",\n" .
               "      \"company\": \"Company\",\n" .
               "      \"duration\": \"Dates\",\n" .
               "      \"description\": \"Description\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"education\": [\n" .
               "    {\n" .
               "      \"degree\": \"Degree\",\n" .
               "      \"institution\": \"Institution\",\n" .
               "      \"year\": \"Year\"\n" .
               "    }\n" .
               "  ],\n" .
               "  \"certifications\": [\"Cert\"],\n" .
               "  \"years_of_experience\": number\n" .
               "}\n\n" .
               "INPUT TEXT TO CONVERT:\n" .
               $text;
    }
}
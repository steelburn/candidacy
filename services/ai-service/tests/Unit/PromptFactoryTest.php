<?php

namespace Tests\Unit;

use App\Services\PromptFactory;
use PHPUnit\Framework\TestCase;

class PromptFactoryTest extends TestCase
{
    protected $promptFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promptFactory = new PromptFactory();
    }

    public function test_build_matching_prompt_includes_equivalence_instructions()
    {
        $candidateProfile = "Name: John Doe\nSkills: React, Node.js\nEducation: BS Computer Science";
        $jobRequirements = "Position: Senior Dev\nRequired Skills: React, Node.js\nRequirements: Bachelor's degree in Computer Science";

        $prompt = $this->promptFactory->buildMatchingPrompt($candidateProfile, $jobRequirements);

        // Check for key instructions
        $this->assertStringContainsString('Infer educational equivalence', $prompt);
        $this->assertStringContainsString('BS = Bachelor of Science', $prompt);
        $this->assertStringContainsString('Do not penalize for standard abbreviations', $prompt);

        // specific check for the Do NOT hallucinate instruction update
        $this->assertStringContainsString('Do NOT hallucinate', $prompt);

        // Check for required sections
        $this->assertStringContainsString('SCORE:', $prompt);
        $this->assertStringContainsString('STRENGTHS:', $prompt);
        $this->assertStringContainsString('GAPS:', $prompt);
        $this->assertStringContainsString('RECOMMENDATION:', $prompt);
    }
}
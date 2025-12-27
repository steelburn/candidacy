<?php

namespace App\Services;

/**
 * AnalysisParserService - Parse AI match analysis with typo handling.
 * 
 * Handles common AI response typos like "STRENGHTHS" instead of "STRENGTHS"
 * and standardizes the analysis format at the server level.
 * 
 * @package App\Services
 */
class AnalysisParserService
{
    /**
     * Parse structured analysis from AI-generated text.
     * Handles common typos in section headers.
     *
     * @param string|null $text Raw analysis text from AI
     * @return array Parsed sections with strengths, gaps, recommendation
     */
    public static function parse(?string $text): array
    {
        if (empty($text)) {
            return [
                'strengths' => [],
                'gaps' => [],
                'recommendation' => '',
                'raw' => ''
            ];
        }

        $sections = [
            'strengths' => [],
            'gaps' => [],
            'recommendation' => '',
            'raw' => $text
        ];

        // Extract Strengths (handle common typos: STRENGHTHS, STRENTHS)
        $strengthsMatch = preg_match(
            '/(?:STRENGTHS?|STRENGHTHS?|STRENTHS?)\s*:([\s\S]*?)(?=(?:GAPS?|WEAKNESS(?:ES)?)\s*:|RECOMMENDATION\s*:|$)/i',
            $text,
            $matches
        );
        if ($strengthsMatch && !empty($matches[1])) {
            $sections['strengths'] = self::extractListItems($matches[1]);
        }

        // Extract Gaps (handle GAPS, GAP, WEAKNESSES, WEAKNESS)
        $gapsMatch = preg_match(
            '/(?:GAPS?|WEAKNESS(?:ES)?)\s*:([\s\S]*?)(?=RECOMMENDATION\s*:|$)/i',
            $text,
            $matches
        );
        if ($gapsMatch && !empty($matches[1])) {
            $sections['gaps'] = self::extractListItems($matches[1]);
        }

        // Extract Recommendation
        $recMatch = preg_match(
            '/RECOMMENDATION\s*:([\s\S]*)/i',
            $text,
            $matches
        );
        if ($recMatch && !empty($matches[1])) {
            $sections['recommendation'] = trim($matches[1]);
        }

        return $sections;
    }

    /**
     * Extract list items from a text block.
     * Handles various formats: -, •, *, 1., 1), a., a)
     *
     * @param string $block Text block containing list items
     * @return array Array of cleaned list items
     */
    protected static function extractListItems(string $block): array
    {
        $lines = array_filter(
            array_map('trim', explode("\n", $block)),
            fn($line) => strlen($line) > 2
        );

        $items = [];
        foreach ($lines as $line) {
            // Remove common list prefixes
            $cleaned = preg_replace('/^[-•*]\s*/', '', $line);           // - or • or *
            $cleaned = preg_replace('/^\d+[.)]\s*/', '', $cleaned);       // 1. or 1)
            $cleaned = preg_replace('/^[a-z][.)]\s*/i', '', $cleaned);    // a. or a)
            $cleaned = trim($cleaned);

            // Skip section headers that might have leaked in
            if (strlen($cleaned) > 2 && !preg_match('/^(GAPS?|STRENGTHS?|RECOMMENDATION|SCORE)/i', $cleaned)) {
                $items[] = $cleaned;
            }
        }

        return $items;
    }

    /**
     * Get parsed analysis as structured JSON for API response.
     * Convenience method for controller use.
     *
     * @param string|null $text Raw analysis text
     * @return array Structured analysis data
     */
    public static function toJson(?string $text): array
    {
        $parsed = self::parse($text);
        
        return [
            'strengths' => $parsed['strengths'],
            'gaps' => $parsed['gaps'],
            'recommendation' => $parsed['recommendation'],
            'has_structured_data' => !empty($parsed['strengths']) || !empty($parsed['gaps']) || !empty($parsed['recommendation'])
        ];
    }
}

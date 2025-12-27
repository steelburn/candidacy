<?php

namespace App\Services;

class JsonParsingService
{
    /**
     * Parse text to find and extract a valid JSON block.
     */
    public function extractJsonFromResponse($response)
    {
        if (empty($response)) {
            return null;
        }

        // 0. Explicitly look for markdown code blocks first
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $response, $matches)) {
            // Found a code block, use its content as the response source
            $response = $matches[1];
        } else {
            // Clean up standalone backticks if no block found
            $response = preg_replace('/```\s*/', '', $response);
        }
        
        // Find the start of JSON (either { or [)
        if (preg_match('/[\{\[]/', $response, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $openChar = $response[$start];
            $closeChar = ($openChar === '{') ? '}' : ']';
            
            $balance = 0;
            $inString = false;
            $escape = false;
            $len = strlen($response);
            $end = $len; // Default to end if truncated

            $inComment = false; // // style
            $inBlockComment = false; // /* style */

            for ($i = $start; $i < $len; $i++) {
                $char = $response[$i];
                $nextChar = ($i + 1 < $len) ? $response[$i + 1] : '';

                // Handle comments
                if (!$inString && !$inBlockComment && !$inComment) {
                    if ($char === '/' && $nextChar === '/') {
                        $inComment = true;
                        $i++; // skip next slash
                        continue;
                    }
                    if ($char === '/' && $nextChar === '*') {
                        $inBlockComment = true;
                        $i++; // skip next star
                        continue;
                    }
                }

                if ($inComment) {
                    if ($char === "\n") {
                        $inComment = false;
                    }
                    continue;
                }

                if ($inBlockComment) {
                    if ($char === '*' && $nextChar === '/') {
                        $inBlockComment = false;
                        $i++; // skip slash
                    }
                    continue;
                }

                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\') {
                    $escape = true;
                    continue;
                }

                if ($char === '"' || $char === "'") {
                    if (!$inString) {
                        $inString = $char;
                    } elseif ($inString === $char) {
                        $inString = false;
                    }
                    continue;
                }

                if ($inString) continue;

                if ($char === $openChar) {
                    $balance++;
                } elseif ($char === $closeChar) {
                    $balance--;
                    if ($balance === 0) {
                        $end = $i + 1;
                        break;
                    }
                }
            }
            
            $jsonString = substr($response, $start, $end - $start);

            // 1. Try decoding raw extracted string
            $decoded = json_decode($jsonString, true);
            if ($decoded !== null) {
                return $decoded;
            }
            
            // 2. Clean comments and placeholders
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
        
        return null; // Parsing failed
    }
    
    public function stripJsonComments($jsonString)
    {
        // Regex to match strings (group 1) OR single-line comments (group 2) OR multi-line comments (group 3) OR placeholders (group 4)
        // Group 1 now matches both double and single quoted strings to protect URLs in single-quoted JSON
        $pattern = '/(
            "(?:\\\\.|[^"\\\\])*" |
            \'(?:\\\\.|[^\'\\\\])*\'
        ) | (
            \/\/[^\n]*
        ) | (
            \/\*[\s\S]*?\*\/
        ) | (
            \.\.\.[^\n]*
        )/x';

        $jsonString = preg_replace_callback($pattern, function ($matches) {
            // If group 1 (string) is matched, preserve it
            if (!empty($matches[1])) {
                return $matches[1];
            }
            // Otherwise it's a comment or placeholder, return empty string
            return ''; 
        }, $jsonString);

        // Remove placeholder objects like { ... text ... } or { ... }
        $jsonString = preg_replace('/\{\s*\.\.\.[^}]*?\}/s', '{}', $jsonString);
        
        // Remove placeholder arrays [ ... ]
        $jsonString = preg_replace('/\[\s*\.\.\.[^\]]*?\]/s', '[]', $jsonString);

        // Clean up any trailing commas before closing braces/brackets
        $jsonString = preg_replace('/,\s*([}\]])/s', '$1', $jsonString);
        
        // Remove placeholder patterns like "..."
        $jsonString = preg_replace('/"\.\.\."/s', 'null', $jsonString);
        
        // Remove raw ellipses often used by AI at end of lists
        $jsonString = preg_replace('/,?\s*\.\.\.(\s*[,}\]])/', '$1', $jsonString);
        $jsonString = preg_replace('/\.\.\./', '', $jsonString); // Last resort for remaining ones
        
        // Remove incomplete array/object markers
        $jsonString = preg_replace('/,\s*\]\s*\/\/[^\n]*Additional[^\n]*/', ']', $jsonString);
        
        return trim($jsonString);
    }
    
    public function repairJson($json)
    {
         // 0a. Fix markdown escaped underscores (common in some models) e.g. years\_of\_experience
        $json = str_replace(['\\_', '\_'], '_', $json);
        
        // 0b. Fix other markdown escapes that break JSON
        $json = str_replace(['\\*', '\\#', '\\-'], ['*', '#', '-'], $json);

        // 0c. Fix weird " $ : " typos seen in some outputs
        $json = str_replace('"$ :', '":', $json);

        // 0d. Fix smart quotes (curly quotes)
        $json = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $json);

        // 0e. Fix specific corruption: "'value'"" -> "value"
        $json = str_replace("'\"\"", '"', $json);
        $json = str_replace("''\"", '"', $json);

        // 0f. Fix single quoted double quoted strings: ' "value" ' -> "value"
        $json = preg_replace("/'\s*\"([^\"]*)\"\s*'/", '"$1"', $json);
        
        // 0g. Fix weird "key": "'value'", pattern where value is double quoted inside single quotes
        $json = preg_replace('/:\s*\'\s*"([^"]*)"\s*\'\s*([,}\]])/', ': "$1"$2', $json);

        // 0h. Fix arithmetic fractions (GPA): 3.5 / 4 -> 3.5
        // Matches number (int or float) followed by / and another number
        $json = preg_replace('/(\d+(?:\.\d+)?)\s*\/\s*\d+/', '$1', $json);
        
        // --- Schema Normalization (for models that ignore prompt) ---
        // Map common alternative keys to our required schema
        $json = str_replace('"work_experience":', '"experience":', $json);
        $json = str_replace('"employmentHistory":', '"experience":', $json);
        $json = str_replace('"dates_employed":', '"duration":', $json);
        $json = str_replace('"position":', '"title":', $json);
        $json = str_replace('"school_name":', '"institution":', $json);
        $json = str_replace('"field_of_study":', '"degree":', $json);

        // 1. Fix single-quoted property names: 'key': -> "key":
        $json = preg_replace("/\s*'([^']+)'\s*:/", '"$1":', $json);
        
        // 2. Fix single-quoted values: : 'value' -> : "value"
        // Use callback to properly escape inner double quotes
        $json = preg_replace_callback('/:\s*\'(.*?)\'\s*([,}\]])/s', function($matches) {
            $content = $matches[1];
            $content = str_replace('"', '\"', $content);
            return ': "' . $content . '"' . $matches[2];
        }, $json);
        
        // 3. Fix double-single-quoted values: ''value'' -> "value"
        $json = preg_replace("/:\s*''([^']*)''(\s*[,}\]])/", ': "$1"$2', $json);

        // 4. Ensure property names are quoted if they aren't (alphanumeric_dashed only)
        // key: value -> "key": value
        $json = preg_replace('/([{,]\s*)([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $json);
        
        // 4b. Fix spaced keys: " key ": -> "key":
        // Use \s* to handle " key": or "key ":
        $json = preg_replace('/"\s*([a-zA-Z0-9_]+)\s*":/', '"$1":', $json);
        
        // 4c. Fix double commas (,,) or (, ,)
        $json = preg_replace('/,[\s,]*,/', ',', $json);

        // 5. Clean up mixed quoting inside values
        // " 'value' " -> "value" (remove outer double quotes if inner is single quoted string)
        $json = preg_replace('/:\s*"\s*\'([^\']*)\'\s*"\s*([,}\]])/', ': "$1"$2', $json);
        // " "value" " -> "value" (escaped)
        $json = preg_replace('/:\s*"\s*\\\"([^"]*)\\\"\s*"\s*([,}\]])/', ': "$1"$2', $json);

        // 6. Fix double-double quotes: ""value"" -> "value"
        $json = preg_replace('/""([^"]*)""/', '"$1"', $json);
        
        // 7. Fix keys ending in colon inside quotes: "key:": value -> "key": value
        $json = preg_replace('/"([^"]+):"\s*:/', '"$1":', $json);
        
        // 8. Fix keys with double quotes at end: "key"": value -> "key": value
        $json = preg_replace('/"([^"]+)""\s*:/', '"$1":', $json);
        
        // 9. Fix unclosed string values at end of object/array: :"value } -> :"value" }
        // Matches colon, quote, non-quotes, then lookahead for closing brace/bracket
        $json = preg_replace('/:"([^"]+?)(\s*[}\]])/', ':"$1"$2', $json);
        
        // 10. Fix unquoted keys starting with # (e.g. #text:) which happens in some XML-to-JSON hallucinations
        $json = preg_replace('/([{,]\s*)#([a-zA-Z0-9_]+)\s*:/', '$1"#$2":', $json);

        // 11. Fix missing values: "key": , -> "key": null,
        $json = preg_replace('/:\s*,/', ': null,', $json);
        
        // 12. Flatten XML-style text objects: { "#text": "value" } -> "value"
        // Run this in a loop in case of nesting, but usually one pass is enough for simple cases
        // We match strict { "#text": "..." } patterns
        $json = preg_replace('/\{\s*"#text"\s*:\s*"([^"]*)"\s*\}/', '"$1"', $json);
        
        return $this->balanceJson($json);
    }

    public function balanceJson($json)
    {
        $len = strlen($json);
        $stack = [];
        $inString = false;
        $escape = false;
        $lastNonSpacePos = -1;

        for ($i = 0; $i < $len; $i++) {
            $char = $json[$i];
            
            // Track last non-whitespace position
            if (!ctype_space($char)) {
                $lastNonSpacePos = $i;
            }

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($inString) {
                if ($char === $inString) {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = $char;
                continue;
            }

            if ($char === '{' || $char === '[') {
                $stack[] = $char;
            } elseif ($char === '}' || $char === ']') {
                // If we encounter a closer, try to match with stack
                if (!empty($stack)) {
                    $last = end($stack);
                    if (($char === '}' && $last === '{') || ($char === ']' && $last === '[')) {
                        array_pop($stack);
                    }
                }
            }
        }

        // Clean up trailing comma or colon before appending closers
        $json = rtrim($json);
        if (substr($json, -1) === ',') {
            $json = substr($json, 0, -1);
        } elseif (substr($json, -1) === ':') {
             $json .= ' null';
        }

        // Close remaining open structures in reverse order
        while (($opener = array_pop($stack)) !== null) {
            if ($opener === '{') {
                $json .= '}';
            } elseif ($opener === '[') {
                $json .= ']';
            }
        }

        return $json;
    }

    public function extractMatchScore($response)
    {
        // Extract score from response (case insensitive, handle optional max score like 80/100)
        if (preg_match('/SCORE:\s*(\d+)/i', $response, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }

    public function parseScreeningQuestionsFromText($text)
    {
        $questions = [];
        // Normalize newlines first
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = explode('---', $text);
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            $qText = $this->extractLine($block, 'QUESTION');
            $qType = strtolower($this->extractLine($block, 'TYPE') ?: 'text');
            $qOptions = $this->extractLine($block, 'OPTIONS');
            
            // Normalize type
            if (strpos($qType, 'boolean') !== false) {
                $qType = 'boolean';
            } elseif (strpos($qType, 'multiple') !== false) {
                $qType = 'multiple_choice';
            } else {
                $qType = 'text';
            }
            
            // Parse options if multiple choice
            $parsedOptions = null;
            if ($qType === 'multiple_choice' && !empty($qOptions)) {
                $parsedOptions = array_map('trim', explode(',', $qOptions));
            }

            if (!empty($qText)) {
                $questions[] = [
                    'question_text' => $qText,
                    'question_type' => $qType,
                    'options' => $parsedOptions
                ];
            }
        }
        
        return $questions;
    }

    public function parseQuestionsFromText($text)
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
}

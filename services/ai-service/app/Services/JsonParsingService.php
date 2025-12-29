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
                return $this->postProcessArray($decoded);
            }
            
            // 2. Clean comments and placeholders
            $cleanJson = $this->stripJsonComments($jsonString);
            $decoded = json_decode($cleanJson, true);
            if ($decoded !== null) {
                return $this->postProcessArray($decoded);
            }
            
            // 3. Aggressive repair for common LLM syntax errors
            $repairedJson = $this->repairJson($cleanJson);
            $decoded = json_decode($repairedJson, true);
            if ($decoded !== null) {
                return $this->postProcessArray($decoded);
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
        
        // 0b2. CRITICAL: Strip JavaScript-style // comments that AI sometimes adds
        // Pattern matches // followed by anything to end of line (but not inside URLs like http://)
        // We do this by checking for // not preceded by : (to avoid http://)
        $json = preg_replace('/(?<!:)\/\/[^\n\r]*/', '', $json);
        
        // 0b3. Strip asterisks used as bullet markers that break JSON
        // Pattern: *" at start of string value, "* at end, or standalone * between strings
        $json = preg_replace('/\*\s*"/', '"', $json);      // *" -> "
        $json = preg_replace('/"\s*\*/', '"', $json);      // "* -> "
        $json = preg_replace('/,\s*\*,/', ',', $json);     // ,*, -> ,
        $json = preg_replace('/\[\s*\*/', '[', $json);     // [* -> [
        $json = preg_replace('/\*\s*\]/', ']', $json);     // *] -> ]
        
        // 0b4. Fix malformed escape sequences like: *\"text\"* or \"text*
        $json = preg_replace('/\*\\\\\"/', '"', $json);    // *\" -> "
        $json = preg_replace('/\\\\\"\*/', '"', $json);    // \"* -> "
        $json = preg_replace('/\*\\\\\'/', "'", $json);    // *\' -> '
        $json = preg_replace('/\\\\\'\*/', "'", $json);    // \'* -> '

        // 0c. Fix weird " $ : " typos seen in some outputs
        $json = str_replace('"$ :', '":', $json);

        // 0d. Fix smart quotes (curly quotes) and dashes
        $json = str_replace(['“', '”', '‘', '’', '–', '—'], ['"', '"', "'", "'", '-', '-'], $json);

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
        
        // 0i. Fix Python tuple syntax: ("value",) -> "value"
        // Matches ("value",) or ("value", )
        $json = preg_replace('/\(\s*"([^"]*)"\s*,\s*\)/', '"$1"', $json);
        
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

        // 4. Ensure property names are quoted...
        $json = preg_replace('/(^|[{,])\s*([a-zA-Z0-9_\-]+)\s*:/', '$1"$2":', $json);

        // 5. Fix unquoted asterisk bullet points in arrays: [ "valid", *invalid, "valid" ]
        // Matches comma or bracket, whitespace, asterisk, text content until comma/bracket/quote
        $json = preg_replace_callback('/([\[,])\s*\*\s*([^"\[\],\}]+?)\s*([,\]])/', function($matches) {
            $content = trim($matches[2]);
            $content = str_replace('"', '\"', $content); // Escape inner quotes
            return $matches[1] . ' "' . $content . '"' . $matches[3];
        }, $json);
        

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
        
        // 13. Fix Python-style "".join([...]) or similar code artifacts
        // Replace "".join([ "a", "b" ]) with "a, b" or similar.
        // Simplify: just remove the wrapper and keep the list? Or try to join them?
        // If it's inside a string value: "desc": "".join([...]), we want "desc": "..."
        // Let's try to strip the wrapper to reveal the array, but that might break string expectation.
        // Better: Convert "".join(["a", "b"]) -> "ab" (approximate)
        // For now, let's just strip the pseudo-code wrapper if possible.
        // Pattern: "".join([ ... ])
        $json = preg_replace('/""\.join\(\s*(\[.*?\])\s*\)/s', '$1', $json);

        // 14. Fix invalid escape sequences that are not standard JSON
        // e.g. \u00a0 is valid, but \x is not.
        $json = preg_replace('/\\\x[0-9a-fA-F]{2}/', '', $json);

        // 15. Strip HTML tags (e.g. <b>, <strong>, <br>) that might have leaked into string values
        $json = strip_tags($json);

        // 16. Strip JavaScript expressions (e.g. Math.floor(...), new Date(), etc.)
        // Replace common JS function calls with null or 0
        $json = preg_replace('/Math\\.floor\\([^)]*\\)/', '0', $json);
        $json = preg_replace('/new\\s+Date\\([^)]*\\)/', 'null', $json);
        // Generic: Replace any remaining function call patterns (word followed by parentheses) with null
        // Be careful not to match JSON arrays. Only match things like func(...) outside of strings.
        // For safety, just remove common patterns:
        $json = preg_replace('/[a-zA-Z_][a-zA-Z0-9_]*\\.[a-zA-Z_][a-zA-Z0-9_]*\\([^)]*\\)/', 'null', $json);
        
        // 17. Fix unquoted keys that appear after newlines or whitespace (common AI output issue)
        // Pattern: newline/whitespace followed by unquoted key followed by colon
        // e.g. \n  description:"value" -> \n  "description":"value"
        $json = preg_replace('/(\\s)([a-zA-Z_][a-zA-Z0-9_]*)\\s*:/', '$1"$2":', $json);
        
        // 18. Fix invalid Unicode escape sequences
        // Remove \\ud835\\udc74 style (surrogate pairs that may be malformed)
        $json = preg_replace('/\\\\u[dD][89aAbB][0-9a-fA-F]{2}\\\\u[dD][cCdDeEfF][0-9a-fA-F]{2}/', '', $json);
        // Remove standalone invalid surrogates
        $json = preg_replace('/\\\\u[dD][89aAbBcCdDeEfF][0-9a-fA-F]{2}/', '', $json);
        
        // 19. Fix keys with colons embedded (e.g. "key:":"value" -> "key":"value")
        $json = preg_replace('/\"([^\"]+):\"\\s*:/', '"$1":', $json);
        
        // 20. Fix double colons (::) which can happen after removing code
        $json = preg_replace('/::+/', ':', $json);
        
        // 21. Remove Windows-1252 special characters that might have leaked through
        // Common: smart quotes, em-dash, etc.
        $json = preg_replace('/[\\x80-\\x9F]/', '', $json);
        
        // 22. Fix null values that became strings: "null" -> null (for specific fields)
        // But be careful not to break valid string "null" values
        // Only apply to known nullable fields at the end
        $json = preg_replace('/"years_of_experience"\\s*:\\s*"null"/', '"years_of_experience": 0', $json);
        $json = preg_replace('/"duration"\\s*:\\s*"null"/', '"duration": null', $json);
        
        // 23. Remove comment-like patterns the AI hallucinates
        // Pattern: "_comment_..._"):  or  "_content_"):  or similar XML-style garbage
        $json = preg_replace('/"_[a-zA-Z_*]+_"\\s*\\)?\\s*:?/', '', $json);
        
        // 24. Fix escaped underscores in keys: years\_of_experience -> years_of_experience
        $json = preg_replace('/"([^"]*)\\\\_([^"]*)"\\s*:/', '"$1_$2":', $json);
        
        // 25. Remove orphan closing parentheses that shouldn't be in JSON
        // Be careful to not break string content - only remove ): patterns outside strings
        $json = preg_replace('/\\)\\s*:/', ':', $json);
        
        // 26. Fix malformed object values: "key": { "_content_...": "value" } -> "key": "value"
        // If a value is an object with only _content_ or similar, flatten it
        $json = preg_replace('/(:\\s*)\\{\\s*"[^"]*"\\s*:\\s*"([^"]*)"\\s*\\}/', '$1"$2"', $json);
        
        // 27. Remove lines that are just commas or empty objects
        $json = preg_replace('/,\\s*,/', ',', $json);
        $json = preg_replace('/\\{\\s*,/', '{', $json);
        $json = preg_replace('/,\\s*\\}/', '}', $json);
        
        // 28. Fix arrays that start with just a colon: ":["  -> just "["
        $json = preg_replace('/:\\s*:\\s*\\[/', ': [', $json);

        // 29. Fix objects starting with a string value but no key...
        $json = preg_replace('/\{\s*"([^"]+)"\s*,/', '{ "value": "$1",', $json);
        
        // 31. Remove orphaned string values in objects (e.g., "key": "value", "orphaned", "key2":...)
        // Matches string literal NOT preceded by : and NOT followed by :
        // Note: Using preg_replace_callback to handle variable whitespace since PCRE doesn't support variable-length lookbehind
        $json = preg_replace_callback('/([,{])\s*"((?:[^"\\\\]|\\\\.)*)"\s*(?!:)([,}])/u', function($matches) {
            // Replace orphaned string value with just the delimiter
            return $matches[1] . $matches[3];
        }, $json);

        // 32. Fix invalid escape \&
        $json = str_replace('\\&', '&', $json);
        
        // 30. Remove trailing commas before closing braces/brackets
        $json = preg_replace('/,(\s*[}\]])/', '$1', $json);
        
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
    private function postProcessArray(array $data): array
    {
        // Recursively clean data
        foreach ($data as $key => $value) {
            if ($key === 'phone' && is_string($value)) {
                // Heuristic: Fix AI normalizing "01..." to "+1..."
                // US numbers (+1) usually have 11 digits (1 + 10).
                // Malaysia numbers (01...) have 10-11 digits (012-3456789).
                // If AI outputs +1... and valid digits < 11, it's likely a bad normalization.
                $digits = preg_replace('/\D/', '', $value);
                if (str_starts_with($value, '+1') && strlen($digits) < 11) {
                     // Replace leading "+" with "0"
                     $value = '0' . substr($value, 1);
                     $data[$key] = $value;
                }
            }

            if (is_string($value)) {
                // Strip " (inferred...)" or " (implied...)" or just any (...) if it looks like explanation?
                // Be careful not to strip legitimate parens like "C# (Intermediate)".
                // Only strip if it contains "implied" or "inferred" or "from" or "mentioned".
                if (stripos($value, 'implied') !== false || stripos($value, 'inferred') !== false || stripos($value, 'from') !== false || stripos($value, 'mentioned') !== false) {
                     $cleaned = preg_replace('/\s*\(.*?(?:implied|inferred|from|mentioned).*?\)/i', '', $value);
                     $data[$key] = trim($cleaned);
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->postProcessArray($value);
            }
        }
        return $data;
    }
}

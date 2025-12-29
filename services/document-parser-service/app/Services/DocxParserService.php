<?php
namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\TextBox;
use PhpOffice\PhpWord\Element\Frame;
use PhpOffice\PhpWord\Element\Shape;
use Illuminate\Support\Facades\Log;

class DocxParserService
{
    /**
     * Extract text from a DOCX file.
     */
    public function extractText(string $path): array
    {
        Log::info('DocxParserService: Starting extraction', ['path' => $path]);
        
        try {
            $phpWord = IOFactory::load($path);
            $text = '';
            $sectionCount = 0;
            
            // Process all sections
            foreach ($phpWord->getSections() as $section) {
                $sectionCount++;
                
                // Process header if exists
                $headers = $section->getHeaders();
                foreach ($headers as $header) {
                    $text .= $this->extractFromContainer($header);
                }
                
                // Process main content elements
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractFromElement($element);
                }
                
                // Process footer if exists
                $footers = $section->getFooters();
                foreach ($footers as $footer) {
                    $text .= $this->extractFromContainer($footer);
                }
                
                $text .= "\n";
            }
            
            $finalText = trim($text);
            
            // Supplement with raw XML extraction to catch text PhpWord might miss
            // (especially text after VML images like <w:pict>)
            $rawText = $this->extractRawTextFromXml($path);
            if ($rawText && strlen($rawText) > strlen($finalText)) {
                Log::info('DocxParserService: Raw XML extraction found more text', [
                    'phpword_length' => strlen($finalText),
                    'raw_length' => strlen($rawText)
                ]);
                $finalText = $rawText;
            }
            
            Log::info('DocxParserService: Extraction completed', [
                'path' => $path,
                'sections' => $sectionCount,
                'text_length' => strlen($finalText)
            ]);
            
            return [
                'text' => $finalText,
                'page_count' => $sectionCount,
                'parser' => 'phpword-enhanced'
            ];
            
        } catch (\Exception $e) {
            Log::error('DocxParserService: Extraction failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Extract raw text from DOCX XML as fallback.
     * This catches text that PhpWord might miss due to VML images or complex structures.
     */
    private function extractRawTextFromXml(string $path): ?string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) {
                return null;
            }
            
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            $text = '';
            if (!$xml) {
                return null;
            }
            
            // Extract <w:t> text elements AND structural tags (</w:p>, <w:br/>, <w:cr/>)
            // matching: 
            // 1. <w:t ...>Content</w:t> (capture content)
            // 2. </w:p> (paragraph end)
            // 3. <w:br/> or <w:cr/> (break)
            // 4. <w:tab/> (tab)
            preg_match_all('/(?:<w:t[^>]*>([^<]*)<\/w:t>)|(?:<\/(?:w:p|w:tr)>)|(?:<w:(?:br|cr)\/>)|(?:<w:tab\/>)/', $xml, $matches, PREG_OFFSET_CAPTURE);
            
            // $matches[0] contains full match (tag or tag+content)
            // $matches[1] contains text content if it's a <w:t>
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $index => $matchInfo) {
                    $fullMatch = $matchInfo[0];
                    
                    // Check if it is a text node
                    if (strpos($fullMatch, '<w:t') === 0) {
                        // Extract content from group 1
                        if (isset($matches[1][$index][0])) {
                            $fragment = html_entity_decode($matches[1][$index][0], ENT_QUOTES | ENT_XML1, 'UTF-8');
                             $text .= $fragment;
                        }
                    } 
                    // Check if it is a paragraph end or row end
                    elseif (strpos($fullMatch, '</w:p') === 0 || strpos($fullMatch, '</w:tr') === 0) {
                        $text .= "\n";
                    }
                    // Check if is a break
                    elseif (strpos($fullMatch, '<w:br') === 0 || strpos($fullMatch, '<w:cr') === 0) {
                        $text .= "\n";
                    }
                    // Check if tab
                    elseif (strpos($fullMatch, '<w:tab') === 0) {
                        $text .= "\t";
                    }
                }
            }
            
            // Clean up: Collapse horizontal whitespace (space, tab) but preserve newlines
            // [ \t\x0B\f]+ matches horizontal whitespace (approx) excluding \n\r
            $text = preg_replace('/[ \t\x0B\f]+/', ' ', $text);
            
            // Limit consecutive newlines to 2 (paragraph break)
            $text = preg_replace('/\n\s*\n/', "\n\n", $text);
            
            // Space usually missed after colon in headers
            $text = preg_replace('/:\s*/', ': ', $text);
            
            return trim($text);
            
        } catch (\Exception $e) {
            Log::warning('DocxParserService: Raw XML extraction failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Extract text from a container (header, footer, cell, etc.)
     */
    private function extractFromContainer($container): string
    {
        $text = '';
        if (method_exists($container, 'getElements')) {
            foreach ($container->getElements() as $element) {
                $text .= $this->extractFromElement($element);
            }
        }
        return $text;
    }
    
    /**
     * Extract text from any element type.
     */
    private function extractFromElement($element): string
    {
        $text = '';
        $elementClass = get_class($element);
        
        // Debug log for unhandled element types
        static $loggedTypes = [];
        if (!isset($loggedTypes[$elementClass])) {
            Log::debug('DocxParserService: Processing element type', ['type' => $elementClass]);
            $loggedTypes[$elementClass] = true;
        }
        
        // Handle Table
        if ($element instanceof Table) {
            $text .= $this->extractFromTable($element);
        }
        // Handle TextRun (paragraph with mixed formatting)
        elseif ($element instanceof TextRun) {
            $text .= $this->extractFromTextRun($element) . "\n";
        }
        // Handle ListItem
        elseif ($element instanceof ListItem) {
            $text .= "• " . $this->extractFromTextRun($element->getTextObject()) . "\n";
        }
        // Handle ListItemRun
        elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItemRun) {
            $text .= "• " . $this->extractFromTextRun($element) . "\n";
        }
        // Handle Title
        elseif ($element instanceof Title) {
            $titleText = $element->getText();
            if ($titleText instanceof TextRun) {
                $text .= $this->extractFromTextRun($titleText) . "\n";
            } else {
                $text .= $titleText . "\n";
            }
        }
        // Handle Link
        elseif ($element instanceof Link) {
            $text .= $element->getText() . " (" . $element->getSource() . ")\n";
        }
        // Handle plain Text
        elseif ($element instanceof Text) {
            $text .= $element->getText();
        }
        // Handle TextBox (floating text container)
        elseif ($element instanceof TextBox) {
            $text .= $this->extractFromContainer($element) . "\n";
        }
        // Handle Frame (container for various elements)
        elseif ($element instanceof Frame) {
            $text .= $this->extractFromContainer($element) . "\n";
        }
        // Handle AbstractContainer (generic container)
        elseif ($element instanceof AbstractContainer) {
            $text .= $this->extractFromContainer($element);
        }
        // Fallback: try getText() method
        elseif (method_exists($element, 'getText')) {
            $result = $element->getText();
            if (is_string($result)) {
                $text .= $result . "\n";
            } elseif ($result instanceof TextRun) {
                $text .= $this->extractFromTextRun($result) . "\n";
            }
        }
        // Try getElements() for nested containers
        elseif (method_exists($element, 'getElements')) {
            $text .= $this->extractFromContainer($element);
        }
        
        return $text;
    }
    
    /**
     * Extract text from a TextRun (paragraph with formatting).
     */
    private function extractFromTextRun(TextRun $textRun): string
    {
        $text = '';
        foreach ($textRun->getElements() as $element) {
            if ($element instanceof Text) {
                $text .= $element->getText();
            } elseif ($element instanceof Link) {
                $text .= $element->getText();
            } elseif (method_exists($element, 'getText')) {
                $result = $element->getText();
                if (is_string($result)) {
                    $text .= $result;
                }
            }
        }
        return $text;
    }
    
    /**
     * Extract text from a Table.
     */
    private function extractFromTable(Table $table): string
    {
        $text = '';
        
        foreach ($table->getRows() as $row) {
            $cellTexts = [];
            
            foreach ($row->getCells() as $cell) {
                $cellText = '';
                foreach ($cell->getElements() as $element) {
                    $cellText .= trim($this->extractFromElement($element));
                }
                if (!empty($cellText)) {
                    $cellTexts[] = trim($cellText);
                }
            }
            
            if (!empty($cellTexts)) {
                // Format table row with separators
                $text .= implode("\t:\t", $cellTexts) . "\n";
            }
        }
        
        $text .= "\n";
        return $text;
    }
}

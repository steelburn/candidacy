<?php
/**
 * Debug script to inspect DOCX structure
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

$docxPath = '/tmp/test.docx';

echo "Loading DOCX: $docxPath\n\n";

$phpWord = IOFactory::load($docxPath);

$sectionNum = 0;
foreach ($phpWord->getSections() as $section) {
    $sectionNum++;
    echo "=== Section $sectionNum ===\n";
    
    // Check headers
    $headers = $section->getHeaders();
    echo "Headers: " . count($headers) . "\n";
    foreach ($headers as $idx => $header) {
        echo "  Header $idx elements: " . count($header->getElements()) . "\n";
        foreach ($header->getElements() as $el) {
            echo "    - " . get_class($el) . "\n";
        }
    }
    
    // Check elements
    $elements = $section->getElements();
    echo "\nMain elements: " . count($elements) . "\n";
    
    foreach ($elements as $idx => $element) {
        $class = get_class($element);
        echo "\n[$idx] $class\n";
        
        // If table, show more detail
        if ($element instanceof PhpOffice\PhpWord\Element\Table) {
            $rows = $element->getRows();
            echo "  Table rows: " . count($rows) . "\n";
            foreach ($rows as $ridx => $row) {
                $cells = $row->getCells();
                echo "  Row $ridx cells: " . count($cells) . "\n";
                foreach ($cells as $cidx => $cell) {
                    echo "    Cell $cidx elements: " . count($cell->getElements()) . "\n";
                    foreach ($cell->getElements() as $ce) {
                        $ceClass = get_class($ce);
                        echo "      - $ceClass";
                        
                        // Try to get text
                        if (method_exists($ce, 'getText')) {
                            $txt = $ce->getText();
                            if (is_string($txt)) {
                                echo " => \"" . substr($txt, 0, 50) . "\"";
                            } elseif ($txt instanceof PhpOffice\PhpWord\Element\TextRun) {
                                $innerTxt = '';
                                foreach ($txt->getElements() as $te) {
                                    if (method_exists($te, 'getText')) {
                                        $innerTxt .= $te->getText();
                                    }
                                }
                                echo " => \"" . substr($innerTxt, 0, 50) . "\"";
                            }
                        }
                        
                        // If TextRun, show inner elements
                        if ($ce instanceof PhpOffice\PhpWord\Element\TextRun) {
                            $innerTxt = '';
                            foreach ($ce->getElements() as $te) {
                                if (method_exists($te, 'getText')) {
                                    $innerTxt .= $te->getText();
                                }
                            }
                            echo " => \"" . substr($innerTxt, 0, 80) . "\"";
                        }
                        
                        echo "\n";
                    }
                }
            }
        }
        
        // Only show first 5 elements in detail
        if ($idx >= 5) {
            echo "\n... " . (count($elements) - 5) . " more elements ...\n";
            break;
        }
    }
    
    echo "\n";
}

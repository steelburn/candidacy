<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\PdfParserService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test PDF path
$pdfPath = __DIR__ . '/test-resume.pdf';

if (!file_exists($pdfPath)) {
    echo "Error: PDF file not found at: $pdfPath\n";
    exit(1);
}

echo "Testing PDF Parser with Granite Docling\n";
echo "========================================\n\n";
echo "PDF: " . basename($pdfPath) . "\n";
echo "Size: " . filesize($pdfPath) . " bytes\n\n";

try {
    $parser = new PdfParserService();
    
    echo "Starting extraction...\n\n";
    $startTime = microtime(true);
    
    $result = $parser->extractText($pdfPath);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "Extraction completed in {$duration} seconds\n\n";
    echo "Results:\n";
    echo "--------\n";
    echo "Parser used: " . ($result['parser'] ?? 'unknown') . "\n";
    echo "Page count: " . ($result['page_count'] ?? 0) . "\n";
    echo "Text length: " . strlen($result['text']) . " characters\n\n";
    
    echo "First 800 characters of extracted text:\n";
    echo "----------------------------------------\n";
    echo substr($result['text'], 0, 800) . "...\n\n";
    
    if (isset($result['model'])) {
        echo "Model: " . $result['model'] . "\n";
    }
    
    echo "\nTest completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

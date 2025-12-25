<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;

class PdfParserService
{
    private $useDocling;
    private $ollamaUrl;
    private $model;
    private $timeout;
    private $imageResolution;
    
    public function __construct()
    {
        $this->useDocling = \Shared\Services\ConfigurationService::get('document_parser.use_granite_docling', env('USE_GRANITE_DOCLING', true));
        $this->ollamaUrl = \Shared\Services\ConfigurationService::get('ai.ollama.url', env('OLLAMA_URL', 'http://192.168.88.120:11434'));
        $this->model = \Shared\Services\ConfigurationService::get('document_parser.granite_model', env('GRANITE_DOCLING_MODEL', 'ibm/granite-docling:258m'));
        $this->timeout = \Shared\Services\ConfigurationService::get('document_parser.timeout', env('DOCLING_TIMEOUT', 60));
        $this->imageResolution = \Shared\Services\ConfigurationService::get('document_parser.image_resolution', env('DOCLING_IMAGE_RESOLUTION', 150));
    }
    
    public function extractText(string $path): array
    {
        if ($this->useDocling) {
            try {
                Log::info('Attempting PDF extraction with Granite Docling', [
                    'path' => $path,
                    'model' => $this->model
                ]);
                
                return $this->extractWithDocling($path);
            } catch (\Exception $e) {
                Log::warning('Docling parsing failed, falling back to basic parser', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return $this->extractWithBasicParser($path);
            }
        }
        
        return $this->extractWithBasicParser($path);
    }
    
    private function extractWithDocling(string $path): array
    {
        $startTime = microtime(true);
        
        // Convert PDF pages to images
        $images = $this->convertPdfToImages($path);
        
        if (empty($images)) {
            throw new \Exception('Failed to convert PDF to images');
        }
        
        $allText = [];
        $pageCount = count($images);
        $conversionTime = round(microtime(true) - $startTime, 2);
        
        Log::info('Processing PDF with Granite Docling', [
            'page_count' => $pageCount,
            'ollama_url' => $this->ollamaUrl,
            'conversion_time' => $conversionTime . 's'
        ]);
        
        foreach ($images as $pageNum => $imageBase64) {
            $pageStartTime = microtime(true);
            
            try {
                $response = Http::timeout($this->timeout)
                    ->post("{$this->ollamaUrl}/api/generate", [
                        'model' => $this->model,
                        'prompt' => 'Extract all text from this document page. Preserve the structure, formatting, tables, lists, and sections. Output clean, well-structured text that maintains the document hierarchy.',
                        'images' => [$imageBase64],
                        'stream' => false,
                        'options' => [
                            'temperature' => 0.1,
                            'num_ctx' => 4096, // Context window
                        ]
                    ]);
                
                if (!$response->successful()) {
                    Log::error('Ollama API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'page' => $pageNum + 1
                    ]);
                    throw new \Exception("Ollama API failed with status {$response->status()}");
                }
                
                $data = $response->json();
                $extractedText = $data['response'] ?? '';
                
                if (empty($extractedText)) {
                    Log::warning('Empty response from Granite Docling', ['page' => $pageNum + 1]);
                }
                
                $allText[] = $extractedText;
                
                $pageTime = round(microtime(true) - $pageStartTime, 2);
                
                Log::info('Page processed successfully', [
                    'page' => $pageNum + 1,
                    'text_length' => strlen($extractedText),
                    'processing_time' => $pageTime . 's'
                ]);
                
                // Free memory
                unset($imageBase64);
                gc_collect_cycles();
                
            } catch (\Exception $e) {
                Log::error('Error processing page with Granite Docling', [
                    'page' => $pageNum + 1,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        $finalText = implode("\n\n--- Page Break ---\n\n", $allText);
        $totalTime = round(microtime(true) - $startTime, 2);
        
        Log::info('PDF extraction completed with Granite Docling', [
            'page_count' => $pageCount,
            'total_text_length' => strlen($finalText),
            'total_time' => $totalTime . 's',
            'avg_time_per_page' => round($totalTime / $pageCount, 2) . 's'
        ]);
        
        // Free memory
        unset($images, $allText);
        gc_collect_cycles();
        
        return [
            'text' => $finalText,
            'page_count' => $pageCount,
            'parser' => 'granite-docling',
            'model' => $this->model
        ];
    }
    
    private function convertPdfToImages(string $path): array
    {
        try {
            $pdf = new Pdf($path);
            $pageCount = $pdf->pageCount();
            $images = [];
            
            Log::info('Converting PDF to images', [
                'path' => $path,
                'page_count' => $pageCount
            ]);
            
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                // Create temporary file for the image
                $tempImagePath = storage_path("app/temp/page_{$pageNumber}_" . uniqid() . '.jpg');
                
                // Ensure temp directory exists
                if (!file_exists(dirname($tempImagePath))) {
                    mkdir(dirname($tempImagePath), 0755, true);
                }
                
                // Convert page to image using Spatie API
                $pdf->selectPage($pageNumber)
                    ->format(\Spatie\PdfToImage\Enums\OutputFormat::Jpg)
                    ->resolution($this->imageResolution)
                    ->save($tempImagePath);
                
                // Convert to base64
                $imageData = file_get_contents($tempImagePath);
                $base64 = base64_encode($imageData);
                
                $images[] = $base64;
                
                // Clean up temporary image
                @unlink($tempImagePath);
                
                Log::debug('Converted page to image', [
                    'page' => $pageNumber,
                    'size' => strlen($imageData)
                ]);
            }
            
            return $images;
            
        } catch (\Exception $e) {
            Log::error('Failed to convert PDF to images', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('PDF to image conversion failed: ' . $e->getMessage());
        }
    }
    
    private function extractWithBasicParser(string $path): array
    {
        Log::info('Using basic PDF parser', ['path' => $path]);
        
        $parser = new Parser();
        $pdf = $parser->parseFile($path);
        
        return [
            'text' => $pdf->getText(),
            'page_count' => count($pdf->getPages()),
            'parser' => 'smalot-pdfparser'
        ];
    }
}

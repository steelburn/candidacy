<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentParserClient
{
    private string $baseUrl = 'http://document-parser-service:8080/api';
    private int $maxWaitSeconds = 120;
    private int $pollIntervalSeconds = 2;

    public function parseDocument(string $filePath): array
    {
        try {
            // Submit file for parsing
            $response = Http::attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->post($this->baseUrl . '/parse');

            if (!$response->successful()) {
                throw new \Exception('Failed to submit file: ' . $response->body());
            }

            $jobId = $response->json('job_id');
            Log::info('Document parser: Job submitted', ['job_id' => $jobId, 'file' => basename($filePath)]);

            // Poll for completion
            $startTime = time();
            while (time() - $startTime < $this->maxWaitSeconds) {
                $statusResponse = Http::get($this->baseUrl . "/parse/{$jobId}/status");
                
                if (!$statusResponse->successful()) {
                    throw new \Exception('Failed to check status');
                }

                $status = $statusResponse->json('status');

                if ($status === 'completed') {
                    $resultResponse = Http::get($this->baseUrl . "/parse/{$jobId}/result");
                    
                    if (!$resultResponse->successful()) {
                        throw new \Exception('Failed to get result');
                    }

                    $result = $resultResponse->json();
                    Log::info('Document parser: Completed', [
                        'job_id' => $jobId,
                        'text_length' => strlen($result['extracted_text'] ?? ''),
                        'page_count' => $result['page_count'] ?? 0
                    ]);

                    return [
                        'text' => $result['extracted_text'],
                        'page_count' => $result['page_count'] ?? null,
                    ];
                }

                if ($status === 'failed') {
                    $errorMessage = $statusResponse->json('error_message') ?? 'Unknown error';
                    throw new \Exception('Parsing failed: ' . $errorMessage);
                }

                sleep($this->pollIntervalSeconds);
            }

            throw new \Exception('Parsing timeout after ' . $this->maxWaitSeconds . ' seconds');

        } catch (\Exception $e) {
            Log::error('Document parser: Error', [
                'file' => basename($filePath),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

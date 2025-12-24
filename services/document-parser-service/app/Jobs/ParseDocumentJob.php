<?php
namespace App\Jobs;
use App\Models\ParseJob;
use App\Services\{PdfParserService, DocxParserService};
use Illuminate\{Bus\Queueable, Contracts\Queue\ShouldQueue, Foundation\Bus\Dispatchable, Queue\InteractsWithQueue, Queue\SerializesModels};
use Illuminate\Support\Facades\{Log, Storage};

class ParseDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 300;
    public $tries = 3;

    public function __construct(public int $parseJobId) {}

    public function handle(): void
    {
        $job = ParseJob::find($this->parseJobId);
        if (!$job) return;

        try {
            $job->markAsProcessing();
            $path = storage_path('app/' . $job->file_path);
            
            $result = match($job->file_type) {
                'pdf' => app(PdfParserService::class)->extractText($path),
                'docx', 'doc' => app(DocxParserService::class)->extractText($path),
                default => throw new \Exception('Unsupported: ' . $job->file_type)
            };

            $job->markAsCompleted($result['text'], $result['page_count'] ?? null);
            Storage::delete($job->file_path);
        } catch (\Exception $e) {
            $job->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ParseJob extends Model
{
    protected $fillable = ['file_path', 'file_type', 'original_filename', 'status', 'extracted_text', 'error_message', 'file_size', 'page_count'];
    
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function markAsProcessing(): void { $this->update(['status' => 'processing']); }
    public function markAsCompleted(string $text, ?int $pages = null): void { $this->update(['status' => 'completed', 'extracted_text' => $text, 'page_count' => $pages]); }
    public function markAsFailed(string $error): void { $this->update(['status' => 'failed', 'error_message' => $error]); }
}

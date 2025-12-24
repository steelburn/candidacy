<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvParsingJob extends Model
{
    protected $fillable = [
        'candidate_id',
        'file_path',
        'extracted_text',
        'status',
        'parsed_data',
        'error_message',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted($parsedData)
    {
        $this->update([
            'status' => 'completed',
            'parsed_data' => $parsedData,
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}

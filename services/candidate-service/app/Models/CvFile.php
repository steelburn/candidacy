<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_name', // Legacy support
        'file_type', // Legacy support
        'file_size',
        'extracted_text',
        'parsed_data',
        'status', // pending, completed, failed
        'error_message',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'is_parsed' => 'boolean',
        'parsed_at' => 'datetime',
    ];

    // Relationship with candidate
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
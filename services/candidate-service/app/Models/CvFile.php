<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'extracted_text',
        'parsed_data',
        'parsing_status', // pending, completed, failed
        'parsing_error',
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

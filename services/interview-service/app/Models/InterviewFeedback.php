<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shared\Traits\BelongsToTenant;

class InterviewFeedback extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'interview_feedback';

    protected $fillable = [
        'interview_id', 'reviewer_id', 'technical_score', 'communication_score',
        'cultural_fit_score', 'overall_score', 'strengths', 'weaknesses',
        'comments', 'recommendation'
    ];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }
}

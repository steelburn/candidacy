<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateMatch extends Model
{
    use HasFactory;
    use \Shared\Traits\HasJsonFields;

    protected $table = 'matches';

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'match_score',
        'analysis',
        'interview_questions',
        'questions_generated_at',
        'questions_model',
        'status',
    ];

    protected $casts = [
        'match_score' => 'integer',
        'analysis' => 'array',
        'interview_questions' => 'array',
        'questions_generated_at' => 'datetime',
    ];

    public function scopeByScore($query, $minScore = 0)
    {
        return $query->where('match_score', '>=', $minScore);
    }
}

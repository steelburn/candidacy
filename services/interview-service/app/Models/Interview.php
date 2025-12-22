<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;
    use \Shared\Traits\HasJsonFields;

    protected $fillable = [
        'candidate_id', 'vacancy_id', 'interviewer_id', 'interviewer_ids', 'stage',
        'scheduled_at', 'duration_minutes', 'location', 'type', 'status', 'notes'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'interviewer_ids' => 'array',
    ];

    public function feedback()
    {
        return $this->hasMany(InterviewFeedback::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at');
    }
}

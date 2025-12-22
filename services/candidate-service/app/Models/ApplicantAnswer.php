<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'question_id',
        'answer',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}

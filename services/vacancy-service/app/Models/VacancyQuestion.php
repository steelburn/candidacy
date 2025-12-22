<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacancyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'question_text',
        'question_type',
    ];

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}

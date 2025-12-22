<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacancy extends Model
{
    use HasFactory, SoftDeletes;
    use \Shared\Traits\HasJsonFields;

    protected $fillable = [
        'title',
        'description',
        'requirements',
        'responsibilities',
        'department',
        'location',
        'work_mode',
        'employment_type',
        'experience_level',
        'min_experience_years',
        'max_experience_years',
        'min_salary',
        'max_salary',
        'currency',
        'required_skills',
        'preferred_skills',
        'benefits',
        'status',
        'closing_date',
        'positions_available',
    ];

    protected $casts = [
        'work_mode' => 'array',
        'required_skills' => 'array',
        'preferred_skills' => 'array',
        'benefits' => 'array',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'closing_date' => 'date',
    ];

    public function questions()
    {
        return $this->hasMany(VacancyQuestion::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}

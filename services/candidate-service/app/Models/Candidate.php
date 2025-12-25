<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'summary',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'skills',
        'experience',
        'education',
        'status',
        'generated_cv_content',
        'notes',
        'years_of_experience',
        'current_location',
        'preferred_location',
        'expected_salary',
        'notice_period',
        'pin_code',
    ];

    protected $hidden = [
        'pin_code',
    ];

    protected $casts = [
        'skills' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'certifications' => 'array',
        'expected_salary' => 'decimal:2',
    ];

    // Relationship with CV files
    public function cvFiles()
    {
        return $this->hasMany(CvFile::class);
    }

    // Get the latest CV file
    public function latestCv()
    {
        return $this->hasOne(CvFile::class)->latestOfMany();
    }

    // Scope for filtering by status
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Helper to normalize JSON fields (array, string, or null)
     * 
     * @param mixed $value
     * @param bool $allowCommaSeparated For 'skills' field
     * @return array|null
     */
    public static function normalizeJsonField($value, $allowCommaSeparated = false)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            // If not valid JSON and allowCommaSeparated is true (e.g. skills)
            if ($allowCommaSeparated) {
                return array_map('trim', explode(',', $value));
            }
        }

        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id', 'vacancy_id', 'salary_offered', 'currency',
        'benefits', 'start_date', 'offer_date', 'expiry_date',
        'status', 'terms', 'candidate_response', 'responded_at'
    ];

    protected $casts = [
        'benefits' => 'array',
        'start_date' => 'date',
        'offer_date' => 'date',
        'expiry_date' => 'date',
        'responded_at' => 'datetime',
        'salary_offered' => 'decimal:2',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expiry_date', '>', now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    use HasFactory;
    use \Shared\Traits\HasJsonFields;

    protected $fillable = [
        'type',
        'status', // pending, processing, completed, failed
        'result', // json
        'error', // string
    ];

    protected $casts = [
        'result' => 'array',
    ];
}

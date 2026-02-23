<?php

namespace Malikad778\LaravelNexus\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NexusRateLimitLog extends Model
{
    use HasFactory;

    protected $table = 'nexus_rate_limit_logs';

    protected $guarded = [];

    protected $casts = [
        'was_limited' => 'boolean',
    ];
}

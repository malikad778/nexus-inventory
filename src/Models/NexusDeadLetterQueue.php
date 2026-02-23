<?php

namespace Malikad778\LaravelNexus\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NexusDeadLetterQueue extends Model
{
    use HasFactory;

    protected $table = 'nexus_dead_letter_queue';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}

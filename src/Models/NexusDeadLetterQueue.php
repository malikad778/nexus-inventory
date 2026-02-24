<?php

namespace Malikad778\LaravelNexus\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $channel
 * @property string $job_class
 * @property array<string, mixed>|null $payload
 * @property string $exception
 * @property string $status
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

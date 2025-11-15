<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeLock extends Model
{
    use HasFactory;

    protected $table = 'envelope_locks';

    protected $fillable = [
        'envelope_id', 'locked_by_user_id', 'locked_until', 'lock_token',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}

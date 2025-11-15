<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeAuditEvent extends Model
{
    use HasFactory;

    protected $table = 'envelope_audit_events';

    protected $fillable = [
        'envelope_id', 'event_type', 'event_description', 'user_id',
        'user_name', 'ip_address', 'event_timestamp',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EnvelopeRecipient extends Model
{
    use HasFactory;

    protected $table = 'envelope_recipients';

    protected $fillable = [
        'envelope_id', 'recipient_id', 'type', 'name', 'email',
        'routing_order', 'status', 'sent_date_time', 'delivered_date_time',
        'signed_date_time', 'declined_date_time', 'declined_reason',
    ];

    protected $casts = [
        'routing_order' => 'integer',
        'sent_date_time' => 'datetime',
        'delivered_date_time' => 'datetime',
        'signed_date_time' => 'datetime',
        'declined_date_time' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EnvelopeRecipient $recipient) {
            if (empty($recipient->recipient_id)) {
                $recipient->recipient_id = 'rec_' . Str::uuid()->toString();
            }
        });
    }

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }
}

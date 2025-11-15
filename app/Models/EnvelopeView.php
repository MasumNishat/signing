<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeView extends Model
{
    use HasFactory;

    protected $table = 'envelope_views';

    protected $fillable = [
        'envelope_id', 'url', 'return_url', 'authentication_method',
        'created_date_time', 'expire_date_time',
    ];

    protected $casts = [
        'created_date_time' => 'datetime',
        'expire_date_time' => 'datetime',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeTab extends Model
{
    use HasFactory;

    protected $table = 'envelope_tabs';

    protected $fillable = [
        'envelope_id', 'document_id', 'recipient_id', 'tab_id', 'type',
        'tab_label', 'value', 'required', 'locked', 'page_number',
        'x_position', 'y_position', 'width', 'height',
    ];

    protected $casts = [
        'required' => 'boolean',
        'locked' => 'boolean',
        'page_number' => 'integer',
        'x_position' => 'integer',
        'y_position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EnvelopeDocument::class, 'document_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EnvelopeRecipient::class, 'recipient_id');
    }
}

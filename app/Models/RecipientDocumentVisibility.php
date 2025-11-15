<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientDocumentVisibility extends Model
{
    use HasFactory;

    protected $table = 'recipient_document_visibility';

    public $timestamps = false;

    protected $fillable = [
        'envelope_id',
        'recipient_id',
        'document_id',
        'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    /**
     * Get the envelope
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    /**
     * Get the recipient
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EnvelopeRecipient::class, 'recipient_id');
    }

    /**
     * Get the document
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(EnvelopeDocument::class, 'document_id');
    }

    /**
     * Scope: For a specific envelope
     */
    public function scopeForEnvelope($query, int $envelopeId)
    {
        return $query->where('envelope_id', $envelopeId);
    }

    /**
     * Scope: For a specific recipient
     */
    public function scopeForRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Scope: Only visible documents
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }
}

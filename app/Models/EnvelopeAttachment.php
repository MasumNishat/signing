<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeAttachment extends Model
{
    use HasFactory;

    protected $table = 'envelope_attachments';

    protected $fillable = [
        'envelope_id', 'attachment_id', 'label', 'attachment_type',
        'data_base64', 'file_extension', 'name',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }
}

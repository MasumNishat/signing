<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeCustomField extends Model
{
    use HasFactory;

    protected $table = 'envelope_custom_fields';

    protected $fillable = [
        'envelope_id', 'template_id', 'name', 'value', 'type', 'required', 'show',
    ];

    protected $casts = [
        'required' => 'boolean',
        'show' => 'boolean',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}

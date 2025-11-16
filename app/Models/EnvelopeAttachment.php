<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EnvelopeAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'envelope_attachments';

    protected $fillable = [
        'envelope_id',
        'attachment_id',
        'label',
        'attachment_type',
        'data_base64',
        'remote_url',
        'file_extension',
        'name',
        'access_control',
        'display',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'data_base64',  // Don't expose base64 data in JSON responses
    ];

    // Attachment types
    public const TYPE_SIGNER = 'signer';
    public const TYPE_SENDER = 'sender';

    // Access control options
    public const ACCESS_SIGNER_ONLY = 'signer';
    public const ACCESS_SENDER_ONLY = 'sender';
    public const ACCESS_ALL = 'all';

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate attachment_id if not provided
        static::creating(function ($model) {
            if (empty($model->attachment_id)) {
                $model->attachment_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the envelope that owns this attachment
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    /**
     * Check if attachment has base64 data
     */
    public function hasBase64Data(): bool
    {
        return !empty($this->data_base64);
    }

    /**
     * Check if attachment has remote URL
     */
    public function hasRemoteUrl(): bool
    {
        return !empty($this->remote_url);
    }

    /**
     * Get attachment size in KB
     */
    public function getSizeInKB(): float
    {
        return round($this->size_bytes / 1024, 2);
    }

    /**
     * Get attachment size in MB
     */
    public function getSizeInMB(): float
    {
        return round($this->size_bytes / 1024 / 1024, 2);
    }

    /**
     * Scope: Filter by attachment type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('attachment_type', $type);
    }

    /**
     * Scope: Filter by envelope
     */
    public function scopeForEnvelope($query, int $envelopeId)
    {
        return $query->where('envelope_id', $envelopeId);
    }
}

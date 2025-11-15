<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChunkedUpload extends Model
{
    use HasFactory;

    protected $table = 'chunked_uploads';

    protected $fillable = [
        'account_id',
        'chunked_upload_id',
        'chunked_upload_uri',
        'committed',
        'expires_date_time',
        'max_chunk_size',
        'max_chunks',
        'total_parts',
    ];

    protected $casts = [
        'committed' => 'boolean',
        'expires_date_time' => 'datetime',
        'max_chunk_size' => 'integer',
        'max_chunks' => 'integer',
        'total_parts' => 'integer',
    ];

    /**
     * Get the account that owns the chunked upload
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if the upload has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_date_time && $this->expires_date_time->isPast();
    }

    /**
     * Check if the upload is committed
     */
    public function isCommitted(): bool
    {
        return $this->committed;
    }

    /**
     * Get the storage path for upload parts
     */
    public function getPartsDirectory(): string
    {
        return "chunked_uploads/{$this->chunked_upload_id}";
    }

    /**
     * Mark upload as committed
     */
    public function markAsCommitted(): void
    {
        $this->committed = true;
        $this->save();
    }
}

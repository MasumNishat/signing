<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * EnvelopeDocument Model
 *
 * Represents a document attached to an envelope or template.
 * Note: Either envelope_id OR template_id must be set (not both).
 *
 * @property int $id
 * @property int|null $envelope_id
 * @property int|null $template_id
 * @property string $document_id
 * @property string $name
 * @property string|null $document_base64
 * @property string|null $file_extension
 * @property int $order_number
 * @property string $display
 * @property bool $include_in_download
 * @property bool $signable
 * @property string|null $file_path
 * @property string|null $pdf_path
 * @property string|null $file_hash
 * @property string $conversion_status
 * @property string|null $conversion_error
 * @property \Carbon\Carbon|null $converted_at
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property int|null $pages
 * @property bool $transform_pdf_fields
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Envelope|null $envelope
 * @property-read Template|null $template
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeTab[] $tabs
 */
class EnvelopeDocument extends Model
{
    use HasFactory;

    protected $table = 'envelope_documents';

    protected $fillable = [
        'envelope_id',
        'template_id',
        'document_id',
        'name',
        'document_base64',
        'file_extension',
        'order_number',
        'display',
        'include_in_download',
        'signable',
        'file_path',
        'pdf_path',
        'file_hash',
        'conversion_status',
        'conversion_error',
        'converted_at',
        'file_size',
        'mime_type',
        'pages',
        'transform_pdf_fields',
        'visible_to_recipients',
        'document_rights',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'include_in_download' => 'boolean',
        'signable' => 'boolean',
        'file_size' => 'integer',
        'pages' => 'integer',
        'transform_pdf_fields' => 'boolean',
        'converted_at' => 'datetime',
        'visible_to_recipients' => 'array',
    ];

    protected $hidden = [
        'document_base64',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EnvelopeDocument $document) {
            if (empty($document->document_id)) {
                $document->document_id = 'doc_' . Str::uuid()->toString();
            }
        });
    }

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function tabs(): HasMany
    {
        return $this->hasMany(EnvelopeTab::class, 'document_id');
    }

    /**
     * Check if document belongs to an envelope
     */
    public function isEnvelopeDocument(): bool
    {
        return $this->envelope_id !== null;
    }

    /**
     * Check if document belongs to a template
     */
    public function isTemplateDocument(): bool
    {
        return $this->template_id !== null;
    }
}

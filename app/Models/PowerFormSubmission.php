<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PowerFormSubmission Model
 *
 * Tracks submissions made through PowerForms.
 * Each submission creates an envelope.
 *
 * @property int $id
 * @property int $powerform_id
 * @property int|null $envelope_id
 * @property string|null $submitter_name
 * @property string|null $submitter_email
 * @property string|null $submitter_ip_address
 * @property array|null $form_data
 * @property \Carbon\Carbon $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PowerForm $powerform
 * @property-read Envelope|null $envelope
 */
class PowerFormSubmission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'powerform_submissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'powerform_id',
        'envelope_id',
        'submitter_name',
        'submitter_email',
        'submitter_ip_address',
        'form_data',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'form_data' => 'array',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            if ($submission->submitted_at === null) {
                $submission->submitted_at = now();
            }
        });
    }

    /**
     * Get the PowerForm for this submission
     */
    public function powerform(): BelongsTo
    {
        return $this->belongsTo(PowerForm::class, 'powerform_id');
    }

    /**
     * Get the envelope created from this submission
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    /**
     * Get form data field
     */
    public function getFormDataField(string $key): mixed
    {
        return $this->form_data[$key] ?? null;
    }

    /**
     * Scope: Filter by PowerForm
     */
    public function scopeForPowerForm($query, int $powerformId)
    {
        return $query->where('powerform_id', $powerformId);
    }

    /**
     * Scope: Filter by submitter email
     */
    public function scopeBySubmitter($query, string $email)
    {
        return $query->where('submitter_email', $email);
    }

    /**
     * Scope: Submissions within date range
     */
    public function scopeSubmittedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
    }
}

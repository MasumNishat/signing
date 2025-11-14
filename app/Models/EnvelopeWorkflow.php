<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnvelopeWorkflow extends Model
{
    use HasFactory;

    protected $table = 'envelope_workflow';

    protected $fillable = [
        'envelope_id', 'workflow_status', 'current_step_id',
    ];

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EnvelopeWorkflowStep::class, 'workflow_id');
    }
}

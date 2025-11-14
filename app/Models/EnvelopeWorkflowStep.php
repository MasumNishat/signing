<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeWorkflowStep extends Model
{
    use HasFactory;

    protected $table = 'envelope_workflow_steps';

    protected $fillable = [
        'workflow_id', 'step_id', 'action', 'trigger_on_item', 'status',
        'triggered_date_time', 'completed_date_time',
    ];

    protected $casts = [
        'triggered_date_time' => 'datetime',
        'completed_date_time' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(EnvelopeWorkflow::class, 'workflow_id');
    }
}

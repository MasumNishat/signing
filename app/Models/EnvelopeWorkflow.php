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

    // Workflow status constants
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Routing type constants
    public const ROUTING_SEQUENTIAL = 'sequential';
    public const ROUTING_PARALLEL = 'parallel';
    public const ROUTING_MIXED = 'mixed';

    protected $fillable = [
        'envelope_id',
        'workflow_status',
        'current_step_id',
        'routing_type',
        'current_routing_order',
        'scheduled_sending_resume_date',
        'auto_navigation',
        'message_lock',
    ];

    protected $casts = [
        'scheduled_sending_resume_date' => 'datetime',
        'auto_navigation' => 'boolean',
        'message_lock' => 'boolean',
        'current_routing_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EnvelopeWorkflowStep::class, 'workflow_id');
    }

    /**
     * Helper Methods
     */

    /**
     * Check if workflow is in progress
     */
    public function isInProgress(): bool
    {
        return $this->workflow_status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if workflow is paused
     */
    public function isPaused(): bool
    {
        return $this->workflow_status === self::STATUS_PAUSED;
    }

    /**
     * Check if workflow is completed
     */
    public function isCompleted(): bool
    {
        return $this->workflow_status === self::STATUS_COMPLETED;
    }

    /**
     * Check if workflow is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->workflow_status === self::STATUS_CANCELLED;
    }

    /**
     * Check if workflow uses sequential routing
     */
    public function isSequential(): bool
    {
        return $this->routing_type === self::ROUTING_SEQUENTIAL;
    }

    /**
     * Check if workflow uses parallel routing
     */
    public function isParallel(): bool
    {
        return $this->routing_type === self::ROUTING_PARALLEL;
    }

    /**
     * Check if scheduled sending is set
     */
    public function hasScheduledSending(): bool
    {
        return $this->scheduled_sending_resume_date !== null;
    }

    /**
     * Check if scheduled time has arrived
     */
    public function isScheduledTimeReached(): bool
    {
        if (!$this->hasScheduledSending()) {
            return true;
        }

        return now()->greaterThanOrEqualTo($this->scheduled_sending_resume_date);
    }

    /**
     * Start workflow
     */
    public function start(): void
    {
        $this->workflow_status = self::STATUS_IN_PROGRESS;
        $this->current_routing_order = 1;
        $this->save();
    }

    /**
     * Pause workflow
     */
    public function pause(): void
    {
        $this->workflow_status = self::STATUS_PAUSED;
        $this->save();
    }

    /**
     * Resume workflow
     */
    public function resume(): void
    {
        $this->workflow_status = self::STATUS_IN_PROGRESS;
        $this->save();
    }

    /**
     * Complete workflow
     */
    public function complete(): void
    {
        $this->workflow_status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Cancel workflow
     */
    public function cancel(): void
    {
        $this->workflow_status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Move to next routing order
     */
    public function moveToNextRoutingOrder(): void
    {
        $this->current_routing_order++;
        $this->save();
    }

    /**
     * Query Scopes
     */

    /**
     * Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('workflow_status', $status);
    }

    /**
     * Filter in progress workflows
     */
    public function scopeInProgress($query)
    {
        return $query->where('workflow_status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Filter paused workflows
     */
    public function scopePaused($query)
    {
        return $query->where('workflow_status', self::STATUS_PAUSED);
    }

    /**
     * Filter workflows with scheduled sending
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_sending_resume_date');
    }

    /**
     * Filter workflows ready to resume (scheduled time reached)
     */
    public function scopeReadyToResume($query)
    {
        return $query->where('workflow_status', self::STATUS_PAUSED)
            ->whereNotNull('scheduled_sending_resume_date')
            ->where('scheduled_sending_resume_date', '<=', now());
    }
}

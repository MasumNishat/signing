<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopeWorkflowStep extends Model
{
    use HasFactory;

    protected $table = 'envelope_workflow_steps';

    // Step action constants
    public const ACTION_SIGN = 'sign';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_VIEW = 'view';
    public const ACTION_CERTIFY = 'certify';
    public const ACTION_DELEGATE = 'delegate';
    public const ACTION_RECEIVE_COPY = 'receive_copy';

    // Step status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'envelope_id',
        'workflow_id',
        'step_id',
        'action',
        'routing_order',
        'recipient_id',
        'item_id',
        'trigger_on_item',
        'status',
        'order',
        'triggered_date_time',
        'completed_date_time',
        'delay_for',
        'conditional_field',
        'conditional_value',
    ];

    protected $casts = [
        'triggered_date_time' => 'datetime',
        'completed_date_time' => 'datetime',
        'routing_order' => 'integer',
        'order' => 'integer',
        'delay_for' => 'integer',
    ];

    /**
     * Relationships
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(EnvelopeWorkflow::class, 'workflow_id');
    }

    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EnvelopeRecipient::class, 'recipient_id');
    }

    /**
     * Helper Methods
     */

    /**
     * Check if step is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if step is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if step is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if step is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if step is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if step has conditional logic
     */
    public function hasConditional(): bool
    {
        return !empty($this->conditional_field);
    }

    /**
     * Check if step has delay
     */
    public function hasDelay(): bool
    {
        return $this->delay_for > 0;
    }

    /**
     * Mark step as triggered
     */
    public function markAsTriggered(): void
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->triggered_date_time = now();
        $this->save();
    }

    /**
     * Mark step as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_date_time = now();
        $this->save();
    }

    /**
     * Mark step as declined
     */
    public function markAsDeclined(): void
    {
        $this->status = self::STATUS_DECLINED;
        $this->completed_date_time = now();
        $this->save();
    }

    /**
     * Mark step as failed
     */
    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }

    /**
     * Mark step as skipped
     */
    public function markAsSkipped(): void
    {
        $this->status = self::STATUS_SKIPPED;
        $this->completed_date_time = now();
        $this->save();
    }

    /**
     * Get all supported actions
     */
    public static function getSupportedActions(): array
    {
        return [
            self::ACTION_SIGN,
            self::ACTION_APPROVE,
            self::ACTION_VIEW,
            self::ACTION_CERTIFY,
            self::ACTION_DELEGATE,
            self::ACTION_RECEIVE_COPY,
        ];
    }

    /**
     * Query Scopes
     */

    /**
     * Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter pending steps
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Filter in progress steps
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Filter completed steps
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Filter by routing order
     */
    public function scopeByRoutingOrder($query, int $routingOrder)
    {
        return $query->where('routing_order', $routingOrder);
    }

    /**
     * Order steps by execution order
     */
    public function scopeOrderedByExecution($query)
    {
        return $query->orderBy('routing_order')
            ->orderBy('order');
    }
}

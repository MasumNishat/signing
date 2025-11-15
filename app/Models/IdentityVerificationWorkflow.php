<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IdentityVerificationWorkflow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'identity_verification_workflows';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'workflow_id',
        'workflow_name',
        'workflow_type',
        'workflow_status',
        'workflow_label',
        'default_name',
        'default_description',
        'signature_provider',
        'phone_auth_recipient_may_provide_number',
        'id_check_configuration_name',
        'sms_auth_configuration_name',
        'steps',
        'input_options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'phone_auth_recipient_may_provide_number' => 'boolean',
        'steps' => 'array',
        'input_options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Workflow status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Workflow type constants.
     */
    const TYPE_ID_CHECK = 'id_check';
    const TYPE_PHONE_AUTH = 'phone_auth';
    const TYPE_SMS_AUTH = 'sms_auth';
    const TYPE_KNOWLEDGE_BASED = 'knowledge_based';
    const TYPE_ID_LOOKUP = 'id_lookup';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workflow) {
            if (empty($workflow->workflow_id)) {
                $workflow->workflow_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns the workflow.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Check if workflow is active.
     */
    public function isActive(): bool
    {
        return $this->workflow_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if workflow is inactive.
     */
    public function isInactive(): bool
    {
        return $this->workflow_status === self::STATUS_INACTIVE;
    }

    /**
     * Scope a query to only include workflows of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('workflow_type', $type);
    }

    /**
     * Scope a query to only include active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('workflow_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include inactive workflows.
     */
    public function scopeInactive($query)
    {
        return $query->where('workflow_status', self::STATUS_INACTIVE);
    }

    /**
     * Scope a query to only include workflows for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query by workflow status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('workflow_status', $status);
        }
        return $query;
    }
}

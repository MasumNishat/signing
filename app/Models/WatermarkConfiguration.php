<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatermarkConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'enabled',
        'watermark_text',
        'watermark_font',
        'watermark_font_size',
        'watermark_font_color',
        'watermark_transparency',
        'horizontal_alignment',
        'vertical_alignment',
        'display_angle',
        'angle',
        'display_on_all_pages',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'watermark_font_size' => 'integer',
        'watermark_transparency' => 'integer',
        'display_angle' => 'boolean',
        'angle' => 'integer',
        'display_on_all_pages' => 'boolean',
    ];

    const HORIZONTAL_LEFT = 'left';
    const HORIZONTAL_CENTER = 'center';
    const HORIZONTAL_RIGHT = 'right';

    const VERTICAL_TOP = 'top';
    const VERTICAL_MIDDLE = 'middle';
    const VERTICAL_BOTTOM = 'bottom';

    /**
     * Get the account that owns the watermark configuration.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if watermark is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the watermark preview URL.
     */
    public function getPreviewUrl(): string
    {
        // In production, this would generate an actual preview image
        return route('api.v2.1.accounts.watermark.preview', [
            'accountId' => $this->account_id,
        ]);
    }
}

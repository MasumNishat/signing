<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'profile_image_uri',
        'biography',
        'company',
        'department',
        'office_location',
        'work_phone',
        'mobile_phone',
        'home_phone',
        'fax',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'social_links',
        'profile_last_modified',
    ];

    protected $casts = [
        'social_links' => 'array',
        'profile_last_modified' => 'datetime',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update the profile last modified timestamp.
     */
    public function touchProfileLastModified(): void
    {
        $this->profile_last_modified = now();
        $this->save();
    }
}

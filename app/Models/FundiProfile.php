<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundiProfile extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'full_name',
        'location_lat',
        'location_lng',
        'verification_status',
        'veta_certificate',
        'skills',
        'experience_years',
        'bio',
    ];

    protected $casts = [
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'experience_years' => 'integer',
    ];

    /**
     * Get the user that owns the fundi profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the primary category/profession for this fundi.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the portfolio items for the fundi.
     */
    public function portfolio(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'fundi_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioMedia extends Model
{
    protected $fillable = [
        'portfolio_id',
        'media_type',
        'file_path',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    /**
     * Get the portfolio item that owns the media.
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}

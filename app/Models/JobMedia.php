<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMedia extends Model
{
    protected $fillable = [
        'job_id',
        'media_type',
        'file_path',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    /**
     * Get the job that owns the media.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}

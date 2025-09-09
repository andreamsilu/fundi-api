<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = [
        'job_id',
        'fundi_id',
        'requirements',
        'budget_breakdown',
        'total_budget',
        'estimated_time',
        'status',
    ];

    protected $casts = [
        'budget_breakdown' => 'array',
        'total_budget' => 'decimal:2',
        'estimated_time' => 'integer',
    ];

    /**
     * Get the job that the application belongs to.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the fundi that made the application.
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Calculate total budget from breakdown.
     */
    public function calculateTotalBudget(): float
    {
        if (!$this->budget_breakdown) {
            return 0;
        }

        return array_sum($this->budget_breakdown);
    }

    /**
     * Boot method to auto-calculate total budget.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($application) {
            if ($application->budget_breakdown) {
                $application->total_budget = $application->calculateTotalBudget();
            }
        });
    }
}

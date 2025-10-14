<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Job;

class WorkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'fundi_id',
        'job_posting_id',
        'portfolio_id',
        'title',
        'description',
        'work_images',
        'work_files',
        'status',
        'rejection_reason',
        'revision_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'work_images' => 'array',
        'work_files' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the fundi who submitted the work
     */
    public function fundi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fundi_id');
    }

    /**
     * Get the job posting this work is for
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_posting_id');
    }

    /**
     * Get the portfolio item associated with this work
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * Get the user who reviewed this work
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if work is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if work is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if work needs revision
     */
    public function needsRevision(): bool
    {
        return $this->status === 'revision_requested';
    }

    /**
     * Check if work is pending review
     */
    public function isPending(): bool
    {
        return $this->status === 'submitted';
    }
}
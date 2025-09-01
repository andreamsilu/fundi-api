<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    /**
     * Explicitly map to service_jobs table.
     */
    protected $table = 'service_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'detailed_description',
        'location',
        'category_id',
        'status',
        'business_model',
        'job_type',
        'requirements',
        'skills_required',
        'certifications_required',
        'experience_required',
        'tools_required',
        'insurance_required',
        'license_required',
        'start_date',
        'end_date',
        'milestones',
        'onsite_required',
        'onsite_location',
        'payment_type',
        'budget_min',
        'budget_max',
        'fixed_amount',
        'hourly_rate',
        'daily_rate',
        'accepted_payment_methods',
        'payment_schedule',
        'requires_contract',
        'requires_invoice',
        'requires_insurance',
        'requires_license',
        'requires_background_check',
        'tags',
        'urgency',
        'is_featured',
        'view_count',
        'proposal_count',
        'deadline',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'postal_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'business_model' => 'string',
        'job_type' => 'string',
        'requirements' => 'array',
        'skills_required' => 'array',
        'certifications_required' => 'array',
        'tools_required' => 'array',
        'insurance_required' => 'boolean',
        'license_required' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'milestones' => 'array',
        'onsite_required' => 'boolean',
        'payment_type' => 'string',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'accepted_payment_methods' => 'array',
        'payment_schedule' => 'string',
        'requires_contract' => 'boolean',
        'requires_invoice' => 'boolean',
        'requires_insurance' => 'boolean',
        'requires_license' => 'boolean',
        'requires_background_check' => 'boolean',
        'tags' => 'array',
        'urgency' => 'string',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'proposal_count' => 'integer',
        'deadline' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the customer who created the job.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the service category for this job.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Get the bookings for this job.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope a query to only include jobs with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include jobs for a specific customer.
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('user_id', $customerId);
    }

    /**
     * Scope a query to only include jobs in a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Business model constants
     */
    const BUSINESS_MODEL_C2C = 'c2c';
    const BUSINESS_MODEL_B2C = 'b2c';
    const BUSINESS_MODEL_C2B = 'c2b';
    const BUSINESS_MODEL_B2B = 'b2b';

    /**
     * Job type constants
     */
    const JOB_TYPE_HOME_REPAIR = 'homeRepair';
    const JOB_TYPE_PERSONAL_SERVICE = 'personalService';
    const JOB_TYPE_EVENT_SERVICE = 'eventService';
    const JOB_TYPE_CONSULTATION = 'consultation';
    const JOB_TYPE_COMMERCIAL_REPAIR = 'commercialRepair';
    const JOB_TYPE_CONSTRUCTION = 'construction';
    const JOB_TYPE_MAINTENANCE = 'maintenance';
    const JOB_TYPE_INSTALLATION = 'installation';
    const JOB_TYPE_CONSULTING = 'consulting';
    const JOB_TYPE_TRAINING = 'training';
    const JOB_TYPE_AUDIT = 'audit';
    const JOB_TYPE_COMPLIANCE = 'compliance';
    const JOB_TYPE_DIGITAL_SERVICE = 'digitalService';
    const JOB_TYPE_MARKETING = 'marketing';
    const JOB_TYPE_LEGAL = 'legal';
    const JOB_TYPE_ACCOUNTING = 'accounting';
    const JOB_TYPE_HR = 'hr';
    const JOB_TYPE_LOGISTICS = 'logistics';
    const JOB_TYPE_SECURITY = 'security';
    const JOB_TYPE_CLEANING = 'cleaning';
    const JOB_TYPE_CATERING = 'catering';
    const JOB_TYPE_TRANSPORTATION = 'transportation';
    const JOB_TYPE_EQUIPMENT = 'equipment';
    const JOB_TYPE_EMERGENCY = 'emergency';

    /**
     * Payment type constants
     */
    const PAYMENT_TYPE_FIXED = 'fixed';
    const PAYMENT_TYPE_HOURLY = 'hourly';
    const PAYMENT_TYPE_DAILY = 'daily';
    const PAYMENT_TYPE_MILESTONE = 'milestone';
    const PAYMENT_TYPE_NEGOTIABLE = 'negotiable';

    /**
     * Urgency constants
     */
    const URGENCY_LOW = 'low';
    const URGENCY_MEDIUM = 'medium';
    const URGENCY_HIGH = 'high';
    const URGENCY_URGENT = 'urgent';

    /**
     * Check if job is compatible with a business model.
     */
    public function isCompatibleWithBusinessModel(string $businessModel): bool
    {
        $config = BusinessModelConfig::getByModel($businessModel);
        if (!$config) {
            return false;
        }

        return $config->supportsJobType($this->job_type);
    }

    /**
     * Check if payment method is accepted for this job.
     */
    public function acceptsPaymentMethod(string $paymentMethod): bool
    {
        return in_array($paymentMethod, $this->accepted_payment_methods ?? []);
    }

    /**
     * Get job budget range.
     */
    public function getBudgetRangeAttribute(): array
    {
        if ($this->payment_type === self::PAYMENT_TYPE_FIXED && $this->fixed_amount) {
            return [$this->fixed_amount, $this->fixed_amount];
        }

        return [$this->budget_min ?? 0, $this->budget_max ?? 0];
    }

    /**
     * Get job budget display string.
     */
    public function getBudgetDisplayAttribute(): string
    {
        if ($this->payment_type === self::PAYMENT_TYPE_FIXED && $this->fixed_amount) {
            return '$' . number_format($this->fixed_amount, 2);
        }

        if ($this->payment_type === self::PAYMENT_TYPE_HOURLY && $this->hourly_rate) {
            return '$' . number_format($this->hourly_rate, 2) . '/hour';
        }

        if ($this->payment_type === self::PAYMENT_TYPE_DAILY && $this->daily_rate) {
            return '$' . number_format($this->daily_rate, 2) . '/day';
        }

        if ($this->budget_min && $this->budget_max) {
            return '$' . number_format($this->budget_min, 2) . ' - $' . number_format($this->budget_max, 2);
        }

        return 'Negotiable';
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment proposal count.
     */
    public function incrementProposalCount(): void
    {
        $this->increment('proposal_count');
    }

    /**
     * Scope a query to only include jobs by business model.
     */
    public function scopeByBusinessModel($query, string $businessModel)
    {
        return $query->where('business_model', $businessModel);
    }

    /**
     * Scope a query to only include jobs by job type.
     */
    public function scopeByJobType($query, string $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    /**
     * Scope a query to only include jobs by payment type.
     */
    public function scopeByPaymentType($query, string $paymentType)
    {
        return $query->where('payment_type', $paymentType);
    }

    /**
     * Scope a query to only include jobs by urgency.
     */
    public function scopeByUrgency($query, string $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    /**
     * Scope a query to only include featured jobs.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include jobs within budget range.
     */
    public function scopeWithinBudget($query, float $minBudget, float $maxBudget)
    {
        return $query->where(function ($q) use ($minBudget, $maxBudget) {
            $q->whereBetween('budget_min', [$minBudget, $maxBudget])
              ->orWhereBetween('budget_max', [$minBudget, $maxBudget])
              ->orWhere(function ($subQ) use ($minBudget, $maxBudget) {
                  $subQ->where('budget_min', '<=', $minBudget)
                       ->where('budget_max', '>=', $maxBudget);
              });
        });
    }

    /**
     * Scope a query to only include jobs by location (within radius).
     */
    public function scopeNearLocation($query, float $latitude, float $longitude, float $radiusKm = 50)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->whereRaw("
            ($earthRadius * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) <= ?
        ", [$latitude, $longitude, $latitude, $radiusKm]);
    }
} 
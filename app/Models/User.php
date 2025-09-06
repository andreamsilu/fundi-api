<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'current_role',
        'user_type',
        'business_name',
        'business_type',
        'registration_number',
        'tax_id',
        'website',
        'business_description',
        'services_offered',
        'industries',
        'employee_count',
        'year_established',
        'license_number',
        'certifications',
        'contact_persons',
        'business_hours',
        'payment_methods',
        'average_project_value',
        'completed_projects',
        'bio',
        'skills',
        'specializations',
        'hourly_rate',
        'daily_rate',
        'project_rate',
        'individual_certifications',
        'years_experience',
        'languages',
        'availability',
        'preferred_job_types',
        'portfolio',
        'email',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'is_verified',
        'is_available',
        'email_verified_at',
        'phone_verified_at',
        'profile_completed_at',
        'last_role_switch',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The guard name for the model.
     *
     * @var string
     */
    protected $guard_name = 'web';

    /**
     * Get the guard name for the model.
     *
     * @return string
     */
    public function getGuardName(): string
    {
        return $this->guard_name ?? 'web';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'last_role_switch' => 'datetime',
        'services_offered' => 'array',
        'industries' => 'array',
        'certifications' => 'array',
        'contact_persons' => 'array',
        'business_hours' => 'array',
        'payment_methods' => 'array',
        'skills' => 'array',
        'specializations' => 'array',
        'individual_certifications' => 'array',
        'languages' => 'array',
        'availability' => 'array',
        'preferred_job_types' => 'array',
        'portfolio' => 'array',
        'is_verified' => 'boolean',
        'is_available' => 'boolean',
        'average_project_value' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'project_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the fundi profile associated with the user.
     */
    public function fundiProfile(): HasOne
    {
        return $this->hasOne(FundiProfile::class);
    }

    /**
     * Get the jobs posted by the user.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get the bookings where the user is the fundi.
     */
    public function fundiBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'fundi_id');
    }

    /**
     * Get the reviews where the user is the fundi.
     */
    public function fundiReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'fundi_id');
    }

    /**
     * Get the reviews posted by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Get the payments made by the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the fundi's subscription.
     */
    public function fundiSubscription(): HasOne
    {
        return $this->hasOne(FundiSubscription::class);
    }

    /**
     * Get the fundi's credits.
     */
    public function fundiCredits(): HasOne
    {
        return $this->hasOne(FundiCredits::class);
    }

    /**
     * Get the fundi's credit transactions.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get the fundi's job application fees.
     */
    public function jobApplicationFees(): HasMany
    {
        return $this->hasMany(JobApplicationFee::class, 'fundi_id');
    }

    /**
     * Get the customer's premium job boosters.
     */
    public function premiumJobBoosters(): HasMany
    {
        return $this->hasMany(PremiumJobBooster::class);
    }

    /**
     * Get the revenue tracking records for this user.
     */
    public function revenueTracking(): HasMany
    {
        return $this->hasMany(RevenueTracking::class);
    }

    /**
     * Get user's rating as a fundi (service provider).
     */
    public function getFundiRatingAttribute(): float
    {
        $reviews = $this->fundiReviews()->whereNotNull('rating');
        if ($reviews->count() === 0) {
            return 0.0;
        }
        return round($reviews->avg('rating'), 1);
    }

    /**
     * Get user's rating as a client (service requester).
     */
    public function getClientRatingAttribute(): float
    {
        $reviews = $this->reviews()->whereNotNull('rating');
        if ($reviews->count() === 0) {
            return 0.0;
        }
        return round($reviews->avg('rating'), 1);
    }

    /**
     * Get user's current role rating.
     */
    public function getCurrentRoleRatingAttribute(): float
    {
        if ($this->isActingAsFundi()) {
            return $this->fundi_rating;
        }
        return $this->client_rating;
    }

    /**
     * User role constants
     */
    const ROLE_CUSTOMER = 'customer';
    const ROLE_FUNDI = 'fundi';
    const ROLE_BUSINESS_CLIENT = 'businessClient';
    const ROLE_BUSINESS_PROVIDER = 'businessProvider';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_SUPPORT = 'support';

    /**
     * User type constants
     */
    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_BUSINESS = 'business';
    const TYPE_ENTERPRISE = 'enterprise';
    const TYPE_GOVERNMENT = 'government';
    const TYPE_NONPROFIT = 'nonprofit';

    /**
     * Check if user can act as a fundi (service provider).
     */
    public function canActAsFundi(): bool
    {
        return $this->hasAnyRole([self::ROLE_FUNDI, self::ROLE_BUSINESS_PROVIDER]);
    }

    /**
     * Check if user can act as a customer (service requester).
     */
    public function canActAsCustomer(): bool
    {
        return $this->hasAnyRole([self::ROLE_CUSTOMER, self::ROLE_BUSINESS_CLIENT]);
    }

    /**
     * Check if user is currently acting as a fundi.
     */
    public function isActingAsFundi(): bool
    {
        return session('current_role') === 'fundi' && $this->canActAsFundi();
    }

    /**
     * Check if user is currently acting as a customer.
     */
    public function isActingAsCustomer(): bool
    {
        return session('current_role') === 'customer' && $this->canActAsCustomer();
    }

    /**
     * Get user's available roles for switching.
     */
    public function getAvailableRoles(): array
    {
        $roles = [];
        
        if ($this->canActAsCustomer()) {
            $roles[] = 'customer';
        }
        
        if ($this->canActAsFundi()) {
            $roles[] = 'fundi';
        }
        
        return $roles;
    }

    /**
     * Switch user's current active role.
     */
    public function switchRole(string $role): bool
    {
        if (in_array($role, $this->getAvailableRoles())) {
            session(['current_role' => $role]);
            $this->update([
                'current_role' => $role,
                'last_role_switch' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Get user's current active role.
     */
    public function getCurrentRole(): ?string
    {
        return session('current_role') ?? $this->current_role ?? $this->getPrimaryRoleAttribute();
    }

    /**
     * Check if user is an individual (not a business).
     */
    public function isIndividual(): bool
    {
        return $this->user_type === self::TYPE_INDIVIDUAL;
    }

    /**
     * Check if user is a business entity.
     */
    public function isBusiness(): bool
    {
        return in_array($this->user_type, [
            self::TYPE_BUSINESS,
            self::TYPE_ENTERPRISE,
            self::TYPE_GOVERNMENT,
            self::TYPE_NONPROFIT
        ]);
    }

    /**
     * Check if user can be a customer in a specific business model.
     */
    public function canBeCustomerInBusinessModel(string $businessModel): bool
    {
        $config = BusinessModelConfig::getByModel($businessModel);
        if (!$config) {
            return false;
        }

        // Check if user can act as customer and has compatible user type
        return $this->canActAsCustomer() && 
               $config->canBeClientType($this->user_type);
    }

    /**
     * Check if user can be a provider in a specific business model.
     */
    public function canBeProviderInBusinessModel(string $businessModel): bool
    {
        $config = BusinessModelConfig::getByModel($businessModel);
        if (!$config) {
            return false;
        }

        // Check if user can act as provider and has compatible user type
        return $this->canActAsFundi() && 
               $config->canBeProviderType($this->user_type);
    }

    /**
     * Check if user can currently post jobs (acting as customer).
     */
    public function canPostJobs(): bool
    {
        return $this->isActingAsCustomer() && $this->can('create jobs');
    }

    /**
     * Check if user can currently accept jobs (acting as fundi).
     */
    public function canAcceptJobs(): bool
    {
        return $this->isActingAsFundi() && $this->can('accept jobs');
    }

    /**
     * Get user's display name (business name for businesses, personal name for individuals).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isBusiness() && $this->business_name) {
            return $this->business_name;
        }
        return $this->name;
    }

    /**
     * Get user's profile completion percentage.
     */
    public function getProfileCompletionPercentageAttribute(): int
    {
        $requiredFields = $this->getRequiredProfileFields();
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return (int) round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Get required profile fields based on user type and current role.
     */
    private function getRequiredProfileFields(): array
    {
        $baseFields = ['name', 'phone', 'email', 'address', 'city', 'state', 'country'];

        if ($this->isBusiness()) {
            $baseFields = array_merge($baseFields, [
                'business_name', 'business_type', 'business_description'
            ]);
        }

        // Add role-specific fields based on current active role
        if ($this->isActingAsFundi()) {
            $baseFields = array_merge($baseFields, [
                'bio', 'skills', 'hourly_rate', 'years_experience'
            ]);
        }
        
        if ($this->isActingAsCustomer()) {
            $baseFields = array_merge($baseFields, [
                'address', 'city', 'state', 'country'
            ]);
        }

        return $baseFields;
    }

    /**
     * Get profile completion percentage for current role.
     */
    public function getCurrentRoleProfileCompletion(): int
    {
        $requiredFields = $this->getRequiredProfileFields();
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return (int) round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Mark profile as completed.
     */
    public function markProfileAsCompleted(): void
    {
        $this->update(['profile_completed_at' => now()]);
    }

    /**
     * Get user's primary role.
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is a moderator.
     */
    public function isModerator(): bool
    {
        return $this->hasRole(self::ROLE_MODERATOR);
    }

    /**
     * Check if user is support staff.
     */
    public function isSupport(): bool
    {
        return $this->hasRole(self::ROLE_SUPPORT);
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_MODERATOR]) &&
               $this->can('manage users');
    }

    /**
     * Check if user can manage roles and permissions.
     */
    public function canManageUAC(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN) &&
               $this->can('manage roles') &&
               $this->can('manage permissions');
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include available users.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include users by type.
     */
    public function scopeByType($query, string $userType)
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope a query to only include users by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->role($role);
    }

    /**
     * Get user's statistics for both roles.
     */
    public function getRoleStatistics(): array
    {
        return [
            'fundi' => [
                'rating' => $this->fundi_rating,
                'reviews_count' => $this->fundiReviews()->count(),
                'completed_jobs' => $this->fundiBookings()->where('status', 'completed')->count(),
                'total_earnings' => $this->fundiBookings()->where('status', 'completed')->sum('amount'),
            ],
            'customer' => [
                'rating' => $this->customer_rating,
                'reviews_count' => $this->reviews()->count(),
                'posted_jobs' => $this->jobs()->count(),
                'completed_jobs' => $this->jobs()->whereHas('bookings', function($q) {
                    $q->where('status', 'completed');
                })->count(),
            ]
        ];
    }

    /**
     * Check if user has completed profile for current role.
     */
    public function hasCompletedCurrentRoleProfile(): bool
    {
        return $this->getCurrentRoleProfileCompletion() >= 80;
    }
}

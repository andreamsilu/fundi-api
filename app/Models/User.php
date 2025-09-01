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
        'role',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'profile_completed_at' => 'datetime',
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
        return $this->hasMany(ServiceJob::class);
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
     * User role constants
     */
    const ROLE_CLIENT = 'client';
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
     * Check if user is a fundi (individual service provider).
     */
    public function isFundi(): bool
    {
        return $this->role === self::ROLE_FUNDI;
    }

    /**
     * Check if user is a client (individual seeking services).
     */
    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    /**
     * Check if user is a business client.
     */
    public function isBusinessClient(): bool
    {
        return $this->role === self::ROLE_BUSINESS_CLIENT;
    }

    /**
     * Check if user is a business provider.
     */
    public function isBusinessProvider(): bool
    {
        return $this->role === self::ROLE_BUSINESS_PROVIDER;
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
     * Check if user can be a client in a specific business model.
     */
    public function canBeClientInBusinessModel(string $businessModel): bool
    {
        $config = BusinessModelConfig::getByModel($businessModel);
        if (!$config) {
            return false;
        }

        return $config->canBeClient($this->role) && 
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

        return $config->canBeProvider($this->role) && 
               $config->canBeProviderType($this->user_type);
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
     * Get required profile fields based on user type and role.
     */
    private function getRequiredProfileFields(): array
    {
        $baseFields = ['name', 'phone', 'email', 'address', 'city', 'state', 'country'];

        if ($this->isBusiness()) {
            return array_merge($baseFields, [
                'business_name', 'business_type', 'business_description'
            ]);
        }

        if ($this->isFundi()) {
            return array_merge($baseFields, [
                'bio', 'skills', 'hourly_rate', 'years_experience'
            ]);
        }

        return $baseFields;
    }

    /**
     * Mark profile as completed.
     */
    public function markProfileAsCompleted(): void
    {
        $this->update(['profile_completed_at' => now()]);
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
        return $query->where('role', $role);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
        'nida_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Automatically assign customer role to new users
        static::created(function ($user) {
            $user->assignRole('customer');
        });
    }

    // IMPORTANT: Do not declare a roles attribute accessor or append it.
    // The Spatie HasRoles trait uses a roles() relationship. Defining a
    // getRolesAttribute() or appending 'roles' will shadow the relation
    // and cause errors like calling pluck() on null.

    // Relationships
    public function fundiProfile()
    {
        return $this->hasOne(FundiProfile::class);
    }

    public function fundiApplications()
    {
        return $this->hasMany(FundiApplication::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'customer_id');
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'fundi_id');
    }

    public function portfolio()
    {
        return $this->hasMany(Portfolio::class, 'fundi_id');
    }

    public function visiblePortfolio()
    {
        return $this->hasMany(Portfolio::class, 'fundi_id')->where('is_visible', true);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function ratingsReceived()
    {
        return $this->hasMany(RatingReview::class, 'fundi_id');
    }

    public function ratingsGiven()
    {
        return $this->hasMany(RatingReview::class, 'customer_id');
    }

    // Role checking methods
    public function isCustomer()
    {
        return $this->hasRole('customer');
    }

    public function isFundi()
    {
        return $this->hasRole('fundi');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function canBecomeFundi()
    {
        return $this->isCustomer() && !$this->isFundi();
    }

    public function canBecomeAdmin()
    {
        return !$this->isAdmin();
    }

    public function hasMultipleRoles()
    {
        return $this->getRoleNames()->count() > 1;
    }

    // Portfolio methods
    public function canAddPortfolioItem(): bool
    {
        return $this->isFundi() && $this->fundiProfile && $this->fundiProfile->is_approved;
    }

    public function getPortfolioCount(): int
    {
        return $this->portfolio()->count();
    }

    public function getVisiblePortfolioCount(): int
    {
        return $this->visiblePortfolio()->count();
    }

    // Role management methods
    public function promoteToFundi()
    {
        if ($this->canBecomeFundi()) {
            $this->assignRole('fundi');
            return true;
        }
        return false;
    }

    public function promoteToAdmin()
    {
        if ($this->canBecomeAdmin()) {
            $this->assignRole('admin');
            return true;
        }
        return false;
    }

    public function demoteToCustomer()
    {
        $this->syncRoles(['customer']);
        return true;
    }

    // Attribute accessors
    public function getPrimaryRoleAttribute()
    {
        $roles = $this->getRoleNames();
        return $roles->first() ?? 'customer';
    }

    public function getRoleDisplayNameAttribute()
    {
        $roleNames = $this->getRoleNames()->toArray();
        return implode(' + ', $roleNames);
    }

    public function getRoleDescriptionAttribute()
    {
        $descriptions = [];
        if ($this->isCustomer()) $descriptions[] = 'Can post jobs and hire fundis';
        if ($this->isFundi()) $descriptions[] = 'Can apply for jobs and provide services';
        if ($this->isAdmin()) $descriptions[] = 'Can manage the platform and users';
        
        return implode(' | ', $descriptions);
    }

    /**
     * Get role IDs for mobile app compatibility
     */
    public function getRoleIds()
    {
        return $this->roles()->pluck('id')->toArray();
    }
}
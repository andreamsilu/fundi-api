<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'full_name',
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

    /**
     * The guard name for the model.
     *
     * @var string
     */
    protected $guard_name = 'api';

    /**
     * The attributes that should be appended to the model's array form.
     * 
     * @var array
     */
    protected $appends = [
        // Removed appends to prevent recursion during serialization
        // Use toApiArray() method instead for safe serialization
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
        // Only fundi users can have portfolios
        if (!$this->isFundi()) {
            return $this->hasMany(Portfolio::class, 'fundi_id')->whereRaw('1 = 0'); // Empty result
        }
        return $this->hasMany(Portfolio::class, 'fundi_id');
    }

    public function visiblePortfolio()
    {
        // Only fundi users can have visible portfolios
        if (!$this->isFundi()) {
            return $this->hasMany(Portfolio::class, 'fundi_id')->whereRaw('1 = 0'); // Empty result
        }
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
        // Only fundi users can have portfolios
        if (!$this->isFundi()) {
            return 0;
        }
        return $this->portfolio()->count();
    }

    public function getVisiblePortfolioCount(): int
    {
        // Only fundi users can have visible portfolios
        if (!$this->isFundi()) {
            return 0;
        }
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

    /**
     * Get roles data safely without recursion
     * 
     * @return array
     */
    public function getRolesData()
    {
        return $this->roles()->select('id', 'name', 'guard_name')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ];
        })->toArray();
    }

    /**
     * Get permissions data safely without recursion
     * 
     * @return array
     */
    public function getPermissionsData()
    {
        return $this->getAllPermissions()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ];
        })->toArray();
    }

    /**
     * Get user data for API responses without recursion
     * 
     * @return array
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'nida_number' => $this->nida_number,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->getRolesData(),
            'permissions' => $this->getPermissionsData(),
            'role_names' => $this->getRoleNames()->toArray(),
            'permission_names' => $this->getAllPermissions()->pluck('name')->toArray(),
            'primary_role' => $this->primary_role,
            'role_display_name' => $this->role_display_name,
            'role_description' => $this->role_description,
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'phone' => $this->phone,
            'roles' => $this->getRoleNames()->toArray(),
        ];
    }
}
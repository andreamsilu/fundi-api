<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'password',
        'roles',
        'status',
        'nida_number',
        'full_name',
        'email',
        'location',
        'bio',
        'skills',
        'languages',
        'veta_certificate',
        'portfolio_images',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'roles' => 'array',
            'skills' => 'array',
            'languages' => 'array',
            'portfolio_images' => 'array',
        ];
    }

    /**
     * Get the roles attribute with proper JSON decoding
     */
    public function getRolesAttribute($value)
    {
        if (is_string($value)) {
            // Handle both regular JSON and escaped JSON strings
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // If JSON decode failed, try to handle escaped quotes
            $cleaned = stripslashes($value);
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // Handle the specific format: "[\"customer\"]"
            if (preg_match('/^"(.+)"$/', $value, $matches)) {
                $innerValue = $matches[1];
                // Decode the escaped quotes first
                $innerValue = str_replace('\\"', '"', $innerValue);
                $decoded = json_decode($innerValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            return [];
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Get the fundi profile associated with the user.
     */
    public function fundiProfile()
    {
        return $this->hasOne(FundiProfile::class);
    }

    /**
     * Get the fundi applications made by the user.
     */
    public function fundiApplications()
    {
        return $this->hasMany(FundiApplication::class);
    }

    /**
     * Get the jobs posted by the user (as customer).
     */
    public function jobs()
    {
        return $this->hasMany(Job::class, 'customer_id');
    }

    /**
     * Get the job applications made by the user (as fundi).
     */
    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'fundi_id');
    }

    /**
     * Get the portfolio items for the user (as fundi).
     */
    public function portfolio()
    {
        return $this->hasMany(Portfolio::class, 'fundi_id');
    }

    /**
     * Get the approved and visible portfolio items for the user (as fundi).
     */
    public function visiblePortfolio()
    {
        return $this->hasMany(Portfolio::class, 'fundi_id')
            ->where('status', 'approved')
            ->where('is_visible', true);
    }

    /**
     * Check if user can add more portfolio items (max 5)
     */
    public function canAddPortfolioItem(): bool
    {
        return $this->portfolio()->count() < 5;
    }

    /**
     * Get the number of portfolio items the user has
     */
    public function getPortfolioCount(): int
    {
        return $this->portfolio()->count();
    }

    /**
     * Get the number of visible portfolio items the user has
     */
    public function getVisiblePortfolioCount(): int
    {
        return $this->visiblePortfolio()->count();
    }

    /**
     * Get the payments made by the user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user sessions.
     */
    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get the ratings given to the user (as fundi).
     */
    public function ratingsReceived()
    {
        return $this->hasMany(RatingReview::class, 'fundi_id');
    }

    /**
     * Get the ratings given by the user (as customer).
     */
    public function ratingsGiven()
    {
        return $this->hasMany(RatingReview::class, 'customer_id');
    }


    /**
     * Check if user has multiple roles (customer + fundi, etc.)
     */
    public function hasMultipleRoles()
    {
        return count($this->roles) > 1;
    }


    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        $roles = $this->getRoles();
        return in_array($role, $roles);
    }

    /**
     * Get user roles as array
     */
    public function getRoles()
    {
        if (is_string($this->roles)) {
            return json_decode($this->roles, true) ?? [];
        }
        return is_array($this->roles) ? $this->roles : [];
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission)
    {
        // Get all roles for this user
        $userRoles = \App\Models\Role::whereIn('name', $this->roles)->get();
        
        // Check if any of the user's roles have the required permission
        foreach ($userRoles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user is a customer
     */
    public function isCustomer()
    {
        return $this->hasRole('customer');
    }

    /**
     * Check if user is a fundi
     */
    public function isFundi()
    {
        return $this->hasRole('fundi');
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user can be promoted to fundi
     */
    public function canBecomeFundi()
    {
        return $this->isCustomer() && !$this->isFundi();
    }

    /**
     * Check if user can be promoted to admin
     */
    public function canBecomeAdmin()
    {
        return !$this->isAdmin();
    }

    /**
     * Add a role to the user
     */
    public function addRole($role)
    {
        if (!in_array($role, $this->roles)) {
            $roles = $this->roles;
            $roles[] = $role;
            $this->update(['roles' => $roles]);
            return true;
        }
        return false;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole($role)
    {
        if (in_array($role, $this->roles) && count($this->roles) > 1) {
            $roles = array_filter($this->roles, fn($r) => $r !== $role);
            $this->update(['roles' => array_values($roles)]);
            return true;
        }
        return false;
    }

    /**
     * Promote user to fundi role
     */
    public function promoteToFundi()
    {
        return $this->addRole('fundi');
    }

    /**
     * Promote user to admin role
     */
    public function promoteToAdmin()
    {
        return $this->addRole('admin');
    }

    /**
     * Demote user to customer role only
     */
    public function demoteToCustomer()
    {
        $this->update(['roles' => ['customer']]);
        return true;
    }

    /**
     * Get primary role (first role in the array)
     */
    public function getPrimaryRoleAttribute()
    {
        return $this->roles[0] ?? 'customer';
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayNameAttribute()
    {
        $roleNames = array_map(fn($role) => match($role) {
            'customer' => 'Customer',
            'fundi' => 'Fundi',
            'admin' => 'Admin',
            default => 'Unknown',
        }, $this->roles);

        return implode(' + ', $roleNames);
    }

    /**
     * Get role description
     */
    public function getRoleDescriptionAttribute()
    {
        $descriptions = [];
        if ($this->isCustomer()) $descriptions[] = 'Can post jobs and hire fundis';
        if ($this->isFundi()) $descriptions[] = 'Can apply for jobs and provide services';
        if ($this->isAdmin()) $descriptions[] = 'Can manage the platform and users';
        
        return implode(' | ', $descriptions);
    }

    /**
     * Get all roles for the user (backward compatibility)
     */
    public function roles()
    {
        return collect($this->roles);
    }
}

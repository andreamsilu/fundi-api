<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        'role' => 'string',
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
     * Check if the user is a fundi.
     */
    public function isFundi(): bool
    {
        return $this->role === 'fundi';
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}

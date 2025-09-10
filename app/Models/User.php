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
        'role',
        'status',
        'nida_number',
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
        ];
    }

    /**
     * Get the fundi profile associated with the user.
     */
    public function fundiProfile()
    {
        return $this->hasOne(FundiProfile::class);
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
     * Check if user is a fundi.
     */
    public function isFundi()
    {
        return $this->role === 'fundi';
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}

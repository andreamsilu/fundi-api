<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_info',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'expired_at',
        'is_active'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active' => 'boolean',
        'device_info' => 'array'
    ];

    /**
     * Get the user that owns the session
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNull('logout_at')
                    ->where('expired_at', '>', now());
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where('expired_at', '<=', now())
                    ->orWhere('is_active', false);
    }

    /**
     * Scope for sessions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if session is expired
     */
    public function isExpired()
    {
        return $this->expired_at <= now() || !$this->is_active;
    }

    /**
     * Mark session as logged out
     */
    public function logout()
    {
        $this->update([
            'logout_at' => now(),
            'is_active' => false
        ]);
    }

    /**
     * Extend session expiration
     */
    public function extend($minutes = 120)
    {
        $this->update([
            'expired_at' => now()->addMinutes($minutes)
        ]);
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationAttribute()
    {
        $endTime = $this->logout_at ?? now();
        return $this->login_at->diffInMinutes($endTime);
    }
}

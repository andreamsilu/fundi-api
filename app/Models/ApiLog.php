<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'url',
        'status_code',
        'response_time',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'error_message'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'response_time' => 'float'
    ];

    /**
     * Get the user that made the request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status_code', '>=', 200)->where('status_code', '<', 300);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * Scope for requests by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope for requests by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for requests within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}

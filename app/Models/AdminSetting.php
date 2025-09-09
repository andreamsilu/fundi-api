<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'payments_enabled',
        'payment_model',
        'subscription_fee',
        'application_fee',
        'job_post_fee',
    ];

    protected $casts = [
        'payments_enabled' => 'boolean',
        'subscription_fee' => 'decimal:2',
        'application_fee' => 'decimal:2',
        'job_post_fee' => 'decimal:2',
    ];
}

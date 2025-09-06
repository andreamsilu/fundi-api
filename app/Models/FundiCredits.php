<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundiCredits extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fundi_credits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_purchased',
        'total_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_purchased' => 'decimal:2',
        'total_used' => 'decimal:2',
    ];

    /**
     * Get the user that owns the credits.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the credit transactions for this fundi.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Check if the fundi has sufficient credits.
     */
    public function hasSufficientCredits(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Add credits to the balance.
     */
    public function addCredits(float $amount, string $description = 'Credit purchase'): CreditTransaction
    {
        $this->increment('balance', $amount);
        $this->increment('total_purchased', $amount);

        return $this->creditTransactions()->create([
            'type' => 'purchase',
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    /**
     * Use credits from the balance.
     */
    public function useCredits(float $amount, string $description = 'Credit usage', ?int $jobId = null): ?CreditTransaction
    {
        if (!$this->hasSufficientCredits($amount)) {
            return null;
        }

        $this->decrement('balance', $amount);
        $this->increment('total_used', $amount);

        return $this->creditTransactions()->create([
            'type' => 'usage',
            'amount' => $amount,
            'description' => $description,
            'job_id' => $jobId,
        ]);
    }

    /**
     * Refund credits to the balance.
     */
    public function refundCredits(float $amount, string $description = 'Credit refund', ?int $jobId = null): CreditTransaction
    {
        $this->increment('balance', $amount);
        $this->decrement('total_used', $amount);

        return $this->creditTransactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'description' => $description,
            'job_id' => $jobId,
        ]);
    }
}

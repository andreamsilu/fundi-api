<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get the fundi profiles in this category.
     */
    public function fundiProfiles(): HasMany
    {
        return $this->hasMany(FundiProfile::class, 'category_id');
    }

    /**
     * Get the jobs in this category.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(ServiceJob::class, 'category_id');
    }

    /**
     * Get the active fundis count in this category.
     */
    public function getActiveFundisCountAttribute(): int
    {
        return $this->fundiProfiles()
            ->whereHas('user', function ($query) {
                $query->where('role', 'fundi');
            })
            ->where('is_available', true)
            ->count();
    }
} 
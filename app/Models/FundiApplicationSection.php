<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundiApplicationSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'section_name',
        'section_data',
        'is_completed',
        'submitted_at',
    ];

    protected $casts = [
        'section_data' => 'array',
        'is_completed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the application section
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark section as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'is_completed' => true,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Check if all required sections are completed
     */
    public static function areAllSectionsCompleted($userId)
    {
        $requiredSections = ['personal_info', 'contact_info', 'professional_info', 'documents'];
        $completedSections = self::where('user_id', $userId)
            ->where('is_completed', true)
            ->pluck('section_name')
            ->toArray();

        return empty(array_diff($requiredSections, $completedSections));
    }

    /**
     * Get application progress for a user
     */
    public static function getApplicationProgress($userId)
    {
        $sections = self::where('user_id', $userId)->get();
        $requiredSections = ['personal_info', 'contact_info', 'professional_info', 'documents'];
        
        $progress = [];
        foreach ($requiredSections as $sectionName) {
            $section = $sections->where('section_name', $sectionName)->first();
            $progress[$sectionName] = [
                'completed' => $section ? $section->is_completed : false,
                'submitted_at' => $section ? $section->submitted_at : null,
                'data' => $section ? $section->section_data : null,
            ];
        }

        $completedCount = collect($progress)->where('completed', true)->count();
        $totalCount = count($requiredSections);
        $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        return [
            'progress' => $progress,
            'completed_sections' => $completedCount,
            'total_sections' => $totalCount,
            'completion_percentage' => $percentage,
            'can_submit_final' => self::areAllSectionsCompleted($userId),
        ];
    }
}
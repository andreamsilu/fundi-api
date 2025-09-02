<?php

namespace App\Http\Controllers\Uac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserRoleSwitchingController extends Controller
{
    /**
     * Get user's available roles for switching.
     */
    public function getAvailableRoles(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'data' => [
                'available_roles' => $user->getAvailableRoles(),
                'current_role' => $user->getCurrentRole(),
                'can_switch' => count($user->getAvailableRoles()) > 1,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_type' => $user->user_type
                ]
            ]
        ]);
    }

    /**
     * Switch user's current active role.
     */
    public function switchRole(Request $request): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|in:customer,fundi'
        ]);

        $user = Auth::user();
        
        if ($user->switchRole($request->role)) {
            return response()->json([
                'message' => "Role switched to {$request->role} successfully",
                'data' => [
                    'current_role' => $user->getCurrentRole(),
                    'available_roles' => $user->getAvailableRoles(),
                    'switched_at' => now()->toISOString()
                ]
            ]);
        }

        return response()->json([
            'message' => 'Invalid role or role not available',
            'errors' => ['role' => ['The selected role is not available for this user.']]
        ], 400);
    }

    /**
     * Get user's profile completion status for current role.
     */
    public function getProfileStatus(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'data' => [
                'current_role' => $user->getCurrentRole(),
                'profile_completion' => $user->getCurrentRoleProfileCompletion(),
                'is_completed' => $user->hasCompletedCurrentRoleProfile(),
                'required_fields' => $user->getRequiredProfileFields(),
                'missing_fields' => $this->getMissingFields($user),
                'profile_summary' => $this->getProfileSummary($user)
            ]
        ]);
    }

    /**
     * Get user's statistics for both roles.
     */
    public function getRoleStatistics(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'data' => [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'current_role' => $user->getCurrentRole()
                ],
                'role_statistics' => $user->getRoleStatistics(),
                'overall_stats' => $this->getOverallStats($user)
            ]
        ]);
    }

    /**
     * Get user's role switching history.
     */
    public function getSwitchingHistory(): JsonResponse
    {
        $user = Auth::user();
        
        // This would typically come from a role_switching_logs table
        // For now, we'll return basic info
        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'current_role' => $user->getCurrentRole(),
                'available_roles' => $user->getAvailableRoles(),
                'can_switch' => count($user->getAvailableRoles()) > 1,
                'note' => 'Role switching history would be logged in a dedicated table'
            ]
        ]);
    }

    /**
     * Get user's current role capabilities.
     */
    public function getCurrentRoleCapabilities(): JsonResponse
    {
        $user = Auth::user();
        $currentRole = $user->getCurrentRole();
        
        $capabilities = [
            'can_post_jobs' => $user->canPostJobs(),
            'can_accept_jobs' => $user->canAcceptJobs(),
            'can_manage_profile' => $user->can('edit own profile'),
            'can_view_dashboard' => true,
            'can_switch_roles' => count($user->getAvailableRoles()) > 1
        ];

        // Add role-specific capabilities
        if ($currentRole === 'fundi') {
            $capabilities['can_browse_jobs'] = true;
            $capabilities['can_accept_bookings'] = true;
            $capabilities['can_update_availability'] = true;
        } elseif ($currentRole === 'customer') {
            $capabilities['can_post_jobs'] = true;
            $capabilities['can_manage_bookings'] = true;
            $capabilities['can_review_services'] = true;
        }

        return response()->json([
            'data' => [
                'current_role' => $currentRole,
                'capabilities' => $capabilities,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]
        ]);
    }

    /**
     * Get missing profile fields for current role.
     */
    private function getMissingFields($user): array
    {
        $requiredFields = $user->getRequiredProfileFields();
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Get profile summary for current role.
     */
    private function getProfileSummary($user): array
    {
        $currentRole = $user->getCurrentRole();
        $completion = $user->getCurrentRoleProfileCompletion();
        
        $summary = [
            'completion_percentage' => $completion,
            'status' => $completion >= 80 ? 'complete' : ($completion >= 50 ? 'partial' : 'incomplete'),
            'current_role' => $currentRole
        ];

        // Add role-specific summary
        if ($currentRole === 'fundi') {
            $summary['fundi_specific'] = [
                'has_bio' => !empty($user->bio),
                'has_skills' => !empty($user->skills),
                'has_rates' => !empty($user->hourly_rate) || !empty($user->daily_rate),
                'has_experience' => !empty($user->years_experience)
            ];
        } elseif ($currentRole === 'customer') {
            $summary['customer_specific'] = [
                'has_contact_info' => !empty($user->phone) && !empty($user->email),
                'has_location' => !empty($user->address) && !empty($user->city)
            ];
        }

        return $summary;
    }

    /**
     * Get overall user statistics.
     */
    private function getOverallStats($user): array
    {
        return [
            'total_roles' => count($user->getAvailableRoles()),
            'profile_completion' => $user->getCurrentRoleProfileCompletion(),
            'is_verified' => $user->is_verified,
            'is_available' => $user->is_available,
            'member_since' => $user->created_at->diffForHumans(),
            'last_active' => $user->updated_at->diffForHumans()
        ];
    }
}

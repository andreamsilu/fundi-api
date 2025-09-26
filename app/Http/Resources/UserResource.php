<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'nida_number' => $this->nida_number,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Role and permission data (safely serialized)
            'roles' => $this->getRolesData(),
            'permissions' => $this->getPermissionsData(),
            'role_names' => $this->getRoleNames()->toArray(),
            'permission_names' => $this->getAllPermissions()->pluck('name')->toArray(),
            
            // Computed role attributes
            'primary_role' => $this->primary_role,
            'role_display_name' => $this->role_display_name,
            'role_description' => $this->role_description,
            
            // Role IDs for mobile app compatibility
            'role_ids' => $this->getRoleIds(),
            
            // Role checking flags
            'is_customer' => $this->isCustomer(),
            'is_fundi' => $this->isFundi(),
            'is_admin' => $this->isAdmin(),
            'has_multiple_roles' => $this->hasMultipleRoles(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}

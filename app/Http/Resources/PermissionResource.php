<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Only include roles if explicitly requested to avoid recursion
            'roles' => $this->when(
                $request->has('include_roles'),
                function () {
                    return $this->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'guard_name' => $role->guard_name,
                        ];
                    });
                }
            ),
            
            // Role names only (safer)
            'role_names' => $this->when(
                $request->has('include_role_names'),
                $this->roles->pluck('name')->toArray()
            ),
        ];
    }
}


<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            
            // Only include permissions if explicitly requested to avoid recursion
            'permissions' => $this->when(
                $request->has('include_permissions'),
                function () {
                    return $this->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                        ];
                    });
                }
            ),
            
            // Permission names only (safer)
            'permission_names' => $this->when(
                $request->has('include_permission_names'),
                $this->permissions->pluck('name')->toArray()
            ),
        ];
    }
}


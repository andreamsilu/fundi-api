<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user() || !$request->user()->hasPermissionTo($permission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have the required permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
} 
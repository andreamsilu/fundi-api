<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class ValidateRouteExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        
        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found',
                'error' => 'The requested endpoint does not exist',
                'path' => $request->path(),
                'method' => $request->method()
            ], 404);
        }

        $controller = $route->getController();
        $action = $route->getActionMethod();

        // Check if the controller method exists
        if (!$controller || !method_exists($controller, $action)) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint method not implemented',
                'error' => "Method {$action} not found in controller",
                'path' => $request->path(),
                'method' => $request->method()
            ], 501);
        }

        return $next($request);
    }
}

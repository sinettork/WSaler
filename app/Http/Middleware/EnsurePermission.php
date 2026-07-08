<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! method_exists($user, 'hasPermissionTo')) {
            return response()->json(['message' => 'Permission system not available.'], 500);
        }

        // Check if user has any of the required permissions
        if (! $user->hasPermissionTo($permissions)) {
            return response()->json([
                'message' => 'Forbidden. Required permission: ' . implode(' or ', $permissions),
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}

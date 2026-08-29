<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaginationDefaults
{
    /**
     * Default items per page if not specified
     */
    private const DEFAULT_PER_PAGE = 50;

    /**
     * Maximum allowed items per page
     */
    private const MAX_PER_PAGE = 100;

    /**
     * Handle an incoming request.
     *
     * Ensures that API list endpoints have sensible pagination defaults
     * to prevent returning thousands of records in a single request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to GET requests (list endpoints)
        if ($request->isMethod('GET')) {
            // If per_page is not set, use default
            if (!$request->has('per_page')) {
                $request->merge(['per_page' => self::DEFAULT_PER_PAGE]);
            } else {
                // Enforce maximum per_page limit
                $perPage = (int) $request->input('per_page');
                if ($perPage > self::MAX_PER_PAGE) {
                    $request->merge(['per_page' => self::MAX_PER_PAGE]);
                }
                // Prevent negative or zero values
                if ($perPage <= 0) {
                    $request->merge(['per_page' => self::DEFAULT_PER_PAGE]);
                }
            }
        }

        return $next($request);
    }
}

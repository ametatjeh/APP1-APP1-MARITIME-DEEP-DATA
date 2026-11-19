<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCountryAccess
{
    /**
     * Handle an incoming request.
     * Enforces country-level access restriction if user is bound to a single country.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->country_restriction_id) {
            $requestedCountryId = $request->route('country')?->id 
                ?? $request->route('countryId') 
                ?? $request->input('country_id');

            if ($requestedCountryId && $requestedCountryId !== $user->country_restriction_id) {
                return response()->json([
                    'message' => 'Forbidden. Access to datasets for this country is restricted for your account.',
                ], 403);
            }
        }

        return $next($request);
    }
}

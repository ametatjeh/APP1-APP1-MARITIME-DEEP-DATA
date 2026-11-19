<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * Roles can be passed as comma-separated values, e.g. 'super_admin,research_director'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role === UserRole::SUPER_ADMIN) {
            return $next($request);
        }

        $userRoleValue = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (!in_array($userRoleValue, $roles)) {
            return response()->json([
                'message' => 'Forbidden. Your role does not possess authorization for this action.',
                'required_roles' => $roles,
                'user_role' => $userRoleValue,
            ], 403);
        }

        return $next($request);
    }
}

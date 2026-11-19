<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'NGO-Research-Station';
        $token = $user->createToken($deviceName)->plainTextToken;

        AuditLog::log('login', $user, null, ['ip' => $request->ip()]);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'country_restriction_id' => $user->country_restriction_id,
                'can_edit' => $user->role->canEditIntelligence(),
                'can_resolve_conflicts' => $user->role->canResolveConflicts(),
                'can_view_redacted' => $user->role->canViewRedactedPII(),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'country_restriction_id' => $user->country_restriction_id,
            'can_edit' => $user->role->canEditIntelligence(),
            'can_resolve_conflicts' => $user->role->canResolveConflicts(),
            'can_view_redacted' => $user->role->canViewRedactedPII(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            AuditLog::log('logout', $user);
        }

        return response()->json(['message' => 'Successfully logged out.']);
    }
}

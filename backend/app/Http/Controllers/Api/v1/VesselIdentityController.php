<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\ResolutionStatus;
use App\Http\Controllers\Controller;
use App\Models\VesselIdentityConflict;
use App\Services\IdentityResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselIdentityController extends Controller
{
    public function __construct(
        protected IdentityResolutionService $identityService
    ) {}

    public function conflicts(Request $request): JsonResponse
    {
        $query = VesselIdentityConflict::with([
            'vesselA.currentFlagCountry',
            'vesselB.currentFlagCountry',
            'reviewedByUser',
        ]);

        if ($status = $request->input('resolution_status')) {
            $query->where('resolution_status', $status);
        }

        if ($nature = $request->input('conflict_nature')) {
            $query->where('conflict_nature', $nature);
        }

        $conflicts = $query->orderByDesc('conflict_score')->paginate($request->input('per_page', 25));

        return response()->json($conflicts);
    }

    public function conflictDetail(string $id): JsonResponse
    {
        $conflict = VesselIdentityConflict::with([
            'vesselA.identifiers',
            'vesselA.currentFlagCountry',
            'vesselB.identifiers',
            'vesselB.currentFlagCountry',
            'reviewedByUser',
        ])->findOrFail($id);

        return response()->json(['data' => $conflict]);
    }

    public function resolveConflict(Request $request, string $id): JsonResponse
    {
        $conflict = VesselIdentityConflict::findOrFail($id);

        $validated = $request->validate([
            'resolution_status' => 'required|in:unreviewed,under_review,confirmed_distinct_vessels,confirmed_same_vessel_pending_merge,dismissed_noise',
            'analyst_conclusion' => 'required|string|min:10',
        ]);

        $resolved = $this->identityService->resolveConflict(
            $conflict,
            ResolutionStatus::from($validated['resolution_status']),
            $validated['analyst_conclusion'],
            $request->user()->id
        );

        return response()->json([
            'data' => $resolved,
            'message' => 'Conflict resolution status and conclusion recorded successfully.',
        ]);
    }

    public function matchCandidates(Request $request): JsonResponse
    {
        $candidates = $this->identityService->matchCandidates($request->all());

        return response()->json([
            'candidates_count' => $candidates->count(),
            'data' => $candidates,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\EvidenceVerificationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Models\SourceRecord;
use App\Models\VerificationReview;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class VerificationController extends Controller
{
    public function __construct(
        protected VerificationService $verificationService
    ) {}

    public function queue(Request $request): JsonResponse
    {
        $unverifiedEvidence = Evidence::with(['case', 'dataSource'])
            ->whereIn('verification_status', [
                EvidenceVerificationStatus::PENDING_VERIFICATION->value,
                EvidenceVerificationStatus::UNDER_REVIEW->value,
            ])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $unverifiedSources = SourceRecord::with('dataSource')
            ->where('verification_status', 'unverified')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'pending_evidence_count' => $unverifiedEvidence->count(),
            'pending_evidence' => $unverifiedEvidence,
            'pending_sources_count' => $unverifiedSources->count(),
            'pending_sources' => $unverifiedSources,
        ]);
    }

    public function review(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->role->canResolveConflicts()) {
            return response()->json([
                'message' => 'Forbidden. Verification decisions require Senior Investigator or Director clearance.',
            ], 403);
        }

        $validated = $request->validate([
            'verifiable_type' => 'required|in:evidence,source_record,vessel,company',
            'verifiable_id' => 'required|uuid',
            'new_status' => ['required', new Enum(EvidenceVerificationStatus::class)],
            'decision' => 'required|string|max:100',
            'written_conclusion' => 'required|string|min:10',
            'supporting_evidence_id' => 'nullable|exists:evidences,id',
        ]);

        $modelClass = match ($validated['verifiable_type']) {
            'evidence' => Evidence::class,
            'source_record' => SourceRecord::class,
            'vessel' => \App\Models\Vessel::class,
            'company' => \App\Models\Company::class,
        };

        $entity = $modelClass::findOrFail($validated['verifiable_id']);

        $review = $this->verificationService->submitReview(
            $entity,
            EvidenceVerificationStatus::from($validated['new_status']),
            $validated['decision'],
            $validated['written_conclusion'],
            $validated['supporting_evidence_id'] ?? null,
            $user
        );

        return response()->json([
            'data' => $review,
            'message' => "Formal verification review registered. Status updated to '{$validated['new_status']}'.",
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verifiable_type' => 'required|in:evidence,source_record,vessel,company',
            'verifiable_id' => 'required|uuid',
        ]);

        $modelClass = match ($validated['verifiable_type']) {
            'evidence' => Evidence::class,
            'source_record' => SourceRecord::class,
            'vessel' => \App\Models\Vessel::class,
            'company' => \App\Models\Company::class,
        };

        $reviews = VerificationReview::with(['reviewer', 'supportingEvidence'])
            ->where('verifiable_type', $modelClass)
            ->where('verifiable_id', $validated['verifiable_id'])
            ->orderByDesc('reviewed_at')
            ->get();

        return response()->json(['data' => $reviews]);
    }
}

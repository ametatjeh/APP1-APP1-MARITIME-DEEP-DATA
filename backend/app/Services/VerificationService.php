<?php

namespace App\Services;

use App\Enums\CustodyAction;
use App\Enums\EvidenceVerificationStatus;
use App\Models\AuditLog;
use App\Models\ChainOfCustody;
use App\Models\Evidence;
use App\Models\User;
use App\Models\VerificationReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VerificationService
{
    /**
     * Submit an analyst verification review for any verifiable intelligence entity.
     * Enforces mandatory written conclusion and immutable audit logging.
     */
    public function submitReview(
        Model $verifiable,
        EvidenceVerificationStatus $newStatus,
        string $decision,
        string $writtenConclusion,
        ?string $supportingEvidenceId,
        User $reviewer
    ): VerificationReview {
        if (strlen(trim($writtenConclusion)) < 10) {
            throw new InvalidArgumentException('Verification requires a comprehensive written conclusion of at least 10 characters.');
        }

        return DB::transaction(function () use ($verifiable, $newStatus, $decision, $writtenConclusion, $supportingEvidenceId, $reviewer) {
            $previousStatus = $verifiable->verification_status?->value ?? 'unverified';

            $review = VerificationReview::create([
                'verifiable_type' => get_class($verifiable),
                'verifiable_id' => $verifiable->id,
                'reviewer_id' => $reviewer->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'decision' => $decision,
                'written_conclusion' => $writtenConclusion,
                'supporting_evidence_id' => $supportingEvidenceId,
                'reviewed_at' => now(),
            ]);

            // Update status on the target entity
            $verifiable->update([
                'verification_status' => $newStatus,
            ]);

            // If the entity is Evidence, record this transition in the Chain of Custody
            if ($verifiable instanceof Evidence) {
                ChainOfCustody::recordEvent(
                    $verifiable,
                    CustodyAction::VERIFIED,
                    $reviewer,
                    "Verification verdict: {$decision}. Status updated from '{$previousStatus}' to '{$newStatus->value}'. Rationale: {$writtenConclusion}",
                    ['review_id' => $review->id]
                );
            }

            AuditLog::log('verification_reviewed', $verifiable, [
                'verification_status' => $previousStatus,
            ], [
                'verification_status' => $newStatus->value,
                'decision' => $decision,
                'conclusion' => $writtenConclusion,
                'reviewer_id' => $reviewer->id,
            ]);

            return $review->load(['reviewer', 'supportingEvidence']);
        });
    }
}

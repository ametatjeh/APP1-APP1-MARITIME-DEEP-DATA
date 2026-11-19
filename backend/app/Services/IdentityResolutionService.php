<?php

namespace App\Services;

use App\Enums\ConflictNature;
use App\Enums\ResolutionStatus;
use App\Models\AuditLog;
use App\Models\Vessel;
use App\Models\VesselIdentifier;
use App\Models\VesselIdentityConflict;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IdentityResolutionService
{
    /**
     * Search and match potential vessel candidates using strict identifier matching and fuzzy trigram name matching.
     */
    public function matchCandidates(array $attributes): Collection
    {
        $query = Vessel::query()->with(['currentFlagCountry', 'identifiers']);

        $name = $attributes['name'] ?? null;
        $imo = $attributes['imo'] ?? null;
        $mmsi = $attributes['mmsi'] ?? null;
        $callsign = $attributes['callsign'] ?? null;
        $registration = $attributes['registration_number'] ?? null;

        $matches = collect();

        // 1. Strict IMO match (Highest priority, but not absolute)
        if (!empty($imo)) {
            $imoMatches = (clone $query)->where('imo_number', $imo)->get();
            $matches = $matches->merge($imoMatches);
        }

        // 2. MMSI match
        if (!empty($mmsi)) {
            $mmsiMatches = (clone $query)->where('current_mmsi', $mmsi)->get();
            $matches = $matches->merge($mmsiMatches);
        }

        // 3. Callsign match
        if (!empty($callsign)) {
            $callsignMatches = (clone $query)->where('current_callsign', $callsign)->get();
            $matches = $matches->merge($callsignMatches);
        }

        // 4. National registration match
        if (!empty($registration)) {
            $regMatches = (clone $query)->where('national_registration_number', $registration)->get();
            $matches = $matches->merge($regMatches);
        }

        // 5. Fuzzy Name match (pg_trgm similarity > 0.4)
        if (!empty($name)) {
            $nameMatches = (clone $query)
                ->whereRaw('similarity(current_name, ?) > 0.4', [$name])
                ->orderByRaw('similarity(current_name, ?) DESC', [$name])
                ->limit(10)
                ->get();
            $matches = $matches->merge($nameMatches);
        }

        return $matches->unique('id')->values();
    }

    /**
     * Detect identifier conflicts between vessels.
     * Prevents automatic merging by recording a conflict when different vessels share an identifier.
     */
    public function detectAndRecordConflicts(Vessel $vessel): array
    {
        $conflictsCreated = [];

        // Check if current MMSI is used by any other distinct vessel
        if (!empty($vessel->current_mmsi)) {
            $otherVesselsWithMmsi = Vessel::where('id', '!=', $vessel->id)
                ->where('current_mmsi', $vessel->current_mmsi)
                ->get();

            foreach ($otherVesselsWithMmsi as $other) {
                $conflict = VesselIdentityConflict::firstOrCreate(
                    [
                        'vessel_a_id' => min($vessel->id, $other->id),
                        'vessel_b_id' => max($vessel->id, $other->id),
                        'conflicting_identifier_type' => 'mmsi',
                        'conflicting_identifier_value' => $vessel->current_mmsi,
                    ],
                    [
                        'conflict_nature' => ConflictNature::SHARED_MMSI,
                        'conflict_score' => 85.00,
                        'resolution_status' => ResolutionStatus::UNREVIEWED,
                    ]
                );
                $conflictsCreated[] = $conflict;
            }
        }

        // Check if IMO is duplicated by another distinct vessel
        if (!empty($vessel->imo_number)) {
            $otherVesselsWithImo = Vessel::where('id', '!=', $vessel->id)
                ->where('imo_number', $vessel->imo_number)
                ->get();

            foreach ($otherVesselsWithImo as $other) {
                $conflict = VesselIdentityConflict::firstOrCreate(
                    [
                        'vessel_a_id' => min($vessel->id, $other->id),
                        'vessel_b_id' => max($vessel->id, $other->id),
                        'conflicting_identifier_type' => 'imo',
                        'conflicting_identifier_value' => $vessel->imo_number,
                    ],
                    [
                        'conflict_nature' => ConflictNature::CLONED_IMO,
                        'conflict_score' => 95.00,
                        'resolution_status' => ResolutionStatus::UNREVIEWED,
                    ]
                );
                $conflictsCreated[] = $conflict;
            }
        }

        return $conflictsCreated;
    }

    /**
     * Resolve a conflict with explicit analyst review.
     * CRITICAL: Never auto-merge without analyst authorization.
     */
    public function resolveConflict(
        VesselIdentityConflict $conflict,
        ResolutionStatus $newStatus,
        string $analystConclusion,
        string $userId
    ): VesselIdentityConflict {
        $oldStatus = $conflict->resolution_status->value;

        $conflict->update([
            'resolution_status' => $newStatus,
            'analyst_conclusion' => $analystConclusion,
            'reviewed_by_user_id' => $userId,
            'reviewed_at' => now(),
        ]);

        AuditLog::log(
            'resolve_conflict',
            $conflict,
            ['resolution_status' => $oldStatus],
            ['resolution_status' => $newStatus->value, 'conclusion' => $analystConclusion],
            ['conflict_id' => $conflict->id]
        );

        return $conflict->fresh();
    }
}

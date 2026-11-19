<?php

namespace Tests\Feature\Phase1;

use App\Enums\ResolutionStatus;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselIdentityConflict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VesselMasterAndIdentityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_vessel_master_allows_non_unique_identifiers_without_crashing_database(): void
    {
        // Vessel 1 with MMSI 525999001
        $res1 = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/vessels', [
            'current_name' => 'KM PERDANA 01',
            'current_mmsi' => '525999001',
            'imo_number' => '9990001',
        ]);
        $res1->assertStatus(201);

        // Vessel 2 with the same MMSI and IMO (Spoofing / Reassigned scenario)
        $res2 = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/vessels', [
            'current_name' => 'GHOST VESSEL CLONE',
            'current_mmsi' => '525999001',
            'imo_number' => '9990001',
        ]);
        $res2->assertStatus(201)
            ->assertJsonPath('conflicts_detected', 2); // 1 for MMSI conflict, 1 for IMO conflict
    }

    public function test_can_retrieve_vessel_historical_timeline(): void
    {
        $vessel = Vessel::where('current_name', 'KM BAHARI PERKASA 01')->firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/vessels/{$vessel->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'event_type',
                        'title',
                        'valid_from',
                        'is_current',
                    ],
                ],
            ]);
    }

    public function test_identity_conflict_requires_analyst_review_and_conclusion_never_auto_merging(): void
    {
        $conflict = VesselIdentityConflict::where('conflicting_identifier_value', '525019821')->firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/identity/conflicts/{$conflict->id}/resolve", [
            'resolution_status' => 'confirmed_distinct_vessels',
            'analyst_conclusion' => 'Vessels confirmed distinct after analyzing structural dimensions, gear, and registration records.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.resolution_status', 'confirmed_distinct_vessels')
            ->assertJsonPath('data.reviewed_by_user_id', $this->seniorInv->id);

        $this->assertDatabaseHas('vessel_identity_conflicts', [
            'id' => $conflict->id,
            'resolution_status' => 'confirmed_distinct_vessels',
        ]);
    }
}

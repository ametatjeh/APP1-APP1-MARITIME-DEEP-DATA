<?php

namespace Tests\Feature\Phase5;

use App\Models\Company;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCompanyAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdvancedSearchAndNetworkGraphTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected Vessel $vessel;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();

        $this->vessel = Vessel::create([
            'current_name' => 'PACIFIC VOYAGER X',
            'imo_number' => '9074729',
            'current_mmsi' => '525999888',
            'current_callsign' => 'YDA999',
            'vessel_type' => 'tuna_longliner',
        ]);

        $this->company = Company::create([
            'company_name' => 'PACIFIC OCEAN HOLDINGS LTD',
            'registration_number' => 'POH-2026-99',
            'jurisdiction_country_id' => null,
        ]);

        VesselCompanyAssignment::create([
            'vessel_id' => $this->vessel->id,
            'company_id' => $this->company->id,
            'assignment_type' => 'beneficial_owner',
            'is_current' => true,
        ]);
    }

    public function test_can_perform_unified_cross_domain_search(): void
    {
        // 1. Search by vessel name
        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/search?q=VOYAGER');

        $response->assertStatus(200)
            ->assertJsonPath('query', 'VOYAGER')
            ->assertJsonStructure(['query', 'total_hits', 'results']);

        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $this->assertEquals('vessel', $results[0]['entity_type']);
        $this->assertEquals('PACIFIC VOYAGER X', $results[0]['title']);

        // 2. Search by corporate name
        $corpResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/search?q=PACIFIC+OCEAN+HOLDINGS');
        $corpResponse->assertStatus(200);

        $corpResults = $corpResponse->json('results');
        $this->assertNotEmpty($corpResults);
        $found = collect($corpResults)->firstWhere('id', $this->company->id);
        $this->assertNotNull($found);
        $this->assertStringContainsStringIgnoringCase('PACIFIC OCEAN HOLDINGS', $found['title']);
    }

    public function test_can_generate_network_graph_with_seed(): void
    {
        // Seed from vessel
        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson(
            "/api/v1/network/graph?seed_type=vessel&seed_id={$this->vessel->id}&depth=2"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'nodes',
                'edges',
                'total_nodes',
                'total_edges',
            ]);

        $nodes = $response->json('nodes');
        $edges = $response->json('edges');

        $this->assertGreaterThanOrEqual(2, count($nodes));
        $this->assertGreaterThanOrEqual(1, count($edges));

        $vesselNode = collect($nodes)->firstWhere('type', 'vessel');
        $companyNode = collect($nodes)->firstWhere('type', 'company');

        $this->assertNotNull($vesselNode);
        $this->assertNotNull($companyNode);
        $this->assertEquals('PACIFIC VOYAGER X', $vesselNode['label']);
        $this->assertEquals('PACIFIC OCEAN HOLDINGS LTD', $companyNode['label']);
    }
}

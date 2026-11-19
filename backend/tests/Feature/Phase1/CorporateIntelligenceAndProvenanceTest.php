<?php

namespace Tests\Feature\Phase1;

use App\Models\Company;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CorporateIntelligenceAndProvenanceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $director;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->director = User::where('email', 'director@maritimedeepdata.org')->first();
        $this->viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();
    }

    public function test_can_generate_corporate_network_graph(): void
    {
        $company = Company::where('company_name', 'PT Samudera Bahari Sentosa')->firstOrFail();

        $response = $this->actingAs($this->director, 'sanctum')->getJson("/api/v1/companies/network-graph?type=company&id={$company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'nodes' => [
                    '*' => ['id', 'type', 'label'],
                ],
                'edges' => [
                    '*' => ['id', 'from', 'to', 'relationship'],
                ],
                'meta' => ['total_nodes', 'total_edges'],
            ]);
    }

    public function test_vessel_to_company_network_graph(): void
    {
        $vessel = Vessel::where('current_name', 'KM BAHARI PERKASA 01')->firstOrFail();

        $response = $this->actingAs($this->director, 'sanctum')->getJson("/api/v1/companies/network-graph?type=vessel&id={$vessel->id}");

        $response->assertStatus(200)
            ->assertJsonPath('nodes.0.type', 'vessel')
            ->assertJsonPath('nodes.0.label', 'KM BAHARI PERKASA 01');
    }

    public function test_officer_pii_redaction_for_unauthorized_users(): void
    {
        $company = Company::where('company_name', 'PT Samudera Bahari Sentosa')->firstOrFail();

        // When viewed by Viewer role (does not have canViewRedactedPII permission)
        $viewerResponse = $this->actingAs($this->viewer, 'sanctum')->getJson("/api/v1/companies/network-graph?type=company&id={$company->id}");
        $viewerNodes = collect($viewerResponse->json('nodes'));
        $redactedOfficer = $viewerNodes->firstWhere('is_redacted', true);

        $this->assertNotNull($redactedOfficer);
        $this->assertStringContainsString('REDACTED', $redactedOfficer['label']);

        // When viewed by Research Director (has canViewRedactedPII permission)
        $directorResponse = $this->actingAs($this->director, 'sanctum')->getJson("/api/v1/companies/network-graph?type=company&id={$company->id}");
        $directorNodes = collect($directorResponse->json('nodes'));
        $clearOfficer = $directorNodes->firstWhere('is_redacted', true);

        $this->assertNotNull($clearOfficer);
        $this->assertStringContainsString('Wong Kar Wai', $clearOfficer['label']);
    }
}

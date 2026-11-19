<?php

namespace Tests\Feature\Phase3;

use App\Models\CrewEmployment;
use App\Models\ManningAgent;
use App\Models\Person;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrewIntelligenceAndLabourTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_can_register_manning_agent(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/crew/agents', [
            'agent_name' => 'PT Samudera Bahari Recruitment',
            'license_number' => 'SIP3MI-2024-0099',
            'registered_address' => 'Jakarta Utara, Indonesia',
            'compliance_rating' => 'under_scrutiny',
            'past_violations_summary' => 'Multiple complaints regarding passport retention and excessive recruitment fees.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.agent_name', 'PT Samudera Bahari Recruitment')
            ->assertJsonPath('data.compliance_rating', 'under_scrutiny');
    }

    public function test_can_record_crew_employment_and_forced_labour_indicator(): void
    {
        $person = Person::create([
            'full_name' => 'Budi Santoso',
            'primary_role' => 'crew',
            'verification_status' => 'verified',
        ]);

        $vessel = Vessel::first();

        $agent = ManningAgent::create([
            'agent_name' => 'Pacific Crewing Agency Ltd',
            'compliance_rating' => 'high_risk_flagged',
        ]);

        // Record crew employment
        $empResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/crew/employments', [
            'person_id' => $person->id,
            'vessel_id' => $vessel->id,
            'manning_agent_id' => $agent->id,
            'role_on_board' => 'deckhand',
            'embarkation_date' => '2025-01-10',
            'embarkation_port' => 'Benoa, Bali',
            'contract_status' => 'active_on_board',
            'salary_currency' => 'USD',
            'reported_monthly_wage' => 350.00,
            'is_forced_labour_suspected' => false,
        ]);

        $empResponse->assertStatus(201)
            ->assertJsonPath('data.role_on_board', 'deckhand');

        $empId = $empResponse->json('data.id');

        // Record ILO 11 Labour violation (retention of documents / withholding wages)
        $violationResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/crew/labour-violations', [
            'crew_employment_id' => $empId,
            'vessel_id' => $vessel->id,
            'person_id' => $person->id,
            'indicator_type' => 'retention_of_documents',
            'severity' => 'critical',
            'incident_date' => '2025-06-15',
            'incident_description' => 'Captain confiscated original seaman book and passport upon boarding and refused return during medical emergency.',
            'reported_by' => 'Escaped Crew Member',
            'reporting_organization' => 'Destructive Fishing Watch (DFW)',
        ]);

        $violationResponse->assertStatus(201)
            ->assertJsonPath('data.indicator_type', 'retention_of_documents')
            ->assertJsonPath('data.severity', 'critical');

        // Verify employment record was automatically flagged for forced labour
        $employment = CrewEmployment::findOrFail($empId);
        $this->assertTrue($employment->is_forced_labour_suspected);
    }

    public function test_can_filter_employments_by_vessel(): void
    {
        $vessel = Vessel::first();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/crew/employments?vessel_id={$vessel->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'role_on_board',
                        'contract_status',
                        'person',
                    ],
                ],
            ]);
    }
}

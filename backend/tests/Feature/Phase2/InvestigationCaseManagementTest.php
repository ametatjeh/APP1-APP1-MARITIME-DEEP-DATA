<?php

namespace Tests\Feature\Phase2;

use App\Enums\CaseCategory;
use App\Enums\CaseConfidentiality;
use App\Enums\CasePriority;
use App\Enums\CaseRole;
use App\Enums\CaseStatus;
use App\Enums\SubjectRole;
use App\Models\InvestigationCase;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvestigationCaseManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected User $investigator;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->investigator = User::where('email', 'investigator@maritimedeepdata.org')->first();
        $this->viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();
    }

    public function test_can_create_investigation_case_with_auto_generated_reference_number(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/cases', [
            'title' => 'Operation Silent Net: High Seas Transshipment',
            'summary' => 'Investigating unauthorized bunker supply in the Western Pacific.',
            'category' => CaseCategory::TRANSSHIPMENT_ABUSE->value,
            'priority' => CasePriority::HIGH->value,
            'status' => CaseStatus::ACTIVE->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Operation Silent Net: High Seas Transshipment')
            ->assertJsonPath('data.lead_investigator_id', $this->seniorInv->id);

        $caseNumber = $response->json('data.case_number');
        $this->assertStringStartsWith('MDD-INV-', $caseNumber);
    }

    public function test_case_supports_multiple_subjects_vessel_company_location(): void
    {
        $case = InvestigationCase::where('case_number', 'MDD-INV-2026-0001')->firstOrFail();
        $vessel = Vessel::first();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/cases/{$case->id}/subjects", [
            'subject_type' => 'vessel',
            'subject_id' => $vessel?->id,
            'subject_name' => $vessel?->current_name ?? 'Test Vessel',
            'subject_role' => SubjectRole::SUSPECTED_VESSEL->value,
            'involvement_summary' => 'Observed alongside target refrigerated cargo carrier.',
            'date_identified' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject_type', 'vessel')
            ->assertJsonPath('data.subject_role', SubjectRole::SUSPECTED_VESSEL->value);

        $this->assertDatabaseHas('case_subjects', [
            'case_id' => $case->id,
            'subject_type' => 'vessel',
        ]);
    }

    public function test_can_assign_investigator_collaborator_to_case(): void
    {
        $case = InvestigationCase::where('case_number', 'MDD-INV-2026-0001')->firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/cases/{$case->id}/assign", [
            'user_id' => $this->investigator->id,
            'role_in_case' => CaseRole::CO_INVESTIGATOR->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user_id', $this->investigator->id)
            ->assertJsonPath('data.role_in_case', CaseRole::CO_INVESTIGATOR->value);
    }

    public function test_confidential_restricted_case_enforces_case_level_authorization(): void
    {
        // Case is restricted and assigned to seniorInv
        $case = InvestigationCase::where('case_number', 'MDD-INV-2026-0001')->firstOrFail();

        // Viewer (unassigned) is forbidden
        $viewerResponse = $this->actingAs($this->viewer, 'sanctum')->getJson("/api/v1/cases/{$case->id}");
        $viewerResponse->assertStatus(403);

        // Senior Investigator is authorized
        $authResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/cases/{$case->id}");
        $authResponse->assertStatus(200)
            ->assertJsonPath('data.case_number', 'MDD-INV-2026-0001');
    }
}

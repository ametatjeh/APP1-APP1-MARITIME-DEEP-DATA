<?php

namespace Tests\Feature\Phase2;

use App\Enums\CustodyAction;
use App\Enums\EvidenceVerificationStatus;
use App\Models\ChainOfCustody;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VerificationWorkflowAndCustodyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $director;
    protected User $seniorInv;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->director = User::where('email', 'director@maritimedeepdata.org')->first();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();
    }

    public function test_can_fetch_verification_queue(): void
    {
        $response = $this->actingAs($this->director, 'sanctum')->getJson('/api/v1/verification/queue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'pending_evidence_count',
                'pending_evidence',
                'pending_sources_count',
                'pending_sources',
            ]);
    }

    public function test_verification_review_requires_substantive_written_conclusion(): void
    {
        $evidence = Evidence::firstOrFail();

        // Too short conclusion should fail validation
        $failResponse = $this->actingAs($this->director, 'sanctum')->postJson('/api/v1/verification/review', [
            'verifiable_type' => 'evidence',
            'verifiable_id' => $evidence->id,
            'new_status' => EvidenceVerificationStatus::VERIFIED->value,
            'decision' => 'Approved',
            'written_conclusion' => 'OK', // Too short
        ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['written_conclusion']);
    }

    public function test_formal_verification_updates_status_and_appends_to_chain_of_custody(): void
    {
        $evidence = Evidence::firstOrFail();

        $response = $this->actingAs($this->director, 'sanctum')->postJson('/api/v1/verification/review', [
            'verifiable_type' => 'evidence',
            'verifiable_id' => $evidence->id,
            'new_status' => EvidenceVerificationStatus::VERIFIED->value,
            'decision' => 'Authenticated against port authority ledger',
            'written_conclusion' => 'Forensic examination confirms official stamp, signature, and timestamp match port clearance log.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.new_status', EvidenceVerificationStatus::VERIFIED->value);

        // Verify that chain of custody logged the VERIFIED event
        $this->assertDatabaseHas('chain_of_custodies', [
            'evidence_id' => $evidence->id,
            'action' => CustodyAction::VERIFIED->value,
            'actor_id' => $this->director->id,
        ]);
    }

    public function test_can_retrieve_chronological_chain_of_custody(): void
    {
        $evidence = Evidence::firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/evidences/{$evidence->id}/custody");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'actor_name',
                        'actor_role',
                        'hash_at_action',
                        'created_at',
                    ],
                ],
            ]);
    }
}

<?php

namespace Tests\Feature\Phase6;

use App\Models\AiConversation;
use App\Models\AiInvestigationFinding;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\IuuVesselListing;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AiMultiAgentAndRagTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected User $analyst;
    protected Vessel $vessel;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->analyst = User::where('email', 'analyst@maritimedeepdata.org')->first();

        $this->vessel = Vessel::first() ?? Vessel::create([
            'current_name' => 'FV PACIFIC VOYAGER',
            'imo_number' => '9074729',
            'flag_country_code' => 'PA', // FoC
            'vessel_type' => 'longliner',
        ]);

        $this->company = Company::first() ?? Company::create([
            'company_name' => 'Horizon Oceanic Holdings Ltd',
            'legal_form' => 'limited',
        ]);
    }

    public function test_can_list_specialized_agents_roster(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/ai/agents');

        $response->assertStatus(200)
            ->assertJsonPath('orchestrator', 'OrchestratorAgent')
            ->assertJsonCount(8, 'specialist_agents');
    }

    public function test_can_create_conversation_and_query_multi_agent_orchestrator(): void
    {
        // 1. Create AI Conversation
        $createRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/ai/conversations', [
            'title' => 'Deep Inquiry: Pacific Voyager Ownership & Compliance',
            'context_type' => 'vessel',
            'context_id' => $this->vessel->id,
            'agent_mode' => 'orchestrator',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.title', 'Deep Inquiry: Pacific Voyager Ownership & Compliance')
            ->assertJsonPath('data.context_type', 'vessel');

        $convId = $createRes->json('data.id');

        // 2. Query Multi-Agent Orchestrator
        $queryRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/ai/conversations/{$convId}/query", [
            'prompt' => 'Analyze vessel flag-hopping history, corporate ownership ties, and any active IUU infractions in the Natuna basin.',
        ]);

        $queryRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user_message' => ['id', 'role', 'content'],
                    'assistant_message' => ['id', 'role', 'content', 'citations', 'reasoning_trace'],
                    'findings',
                ],
                'message',
            ]);

        $content = $queryRes->json('data.assistant_message.content');
        $this->assertStringContainsString('Multi-Agent Intelligence Research Assessment', $content);

        // Verify that reasoning trace recorded multi-agent execution
        $trace = $queryRes->json('data.assistant_message.reasoning_trace');
        $this->assertNotEmpty($trace['agents_invoked']);
        $this->assertContains('OrchestratorAgent', $trace['agents_invoked']);
    }

    public function test_ai_findings_require_analyst_review_and_cannot_auto_merge(): void
    {
        $conversation = AiConversation::create([
            'title' => 'Test Review Workflow',
            'user_id' => $this->seniorInv->id,
            'context_type' => 'general',
            'agent_mode' => 'orchestrator',
        ]);

        $finding = AiInvestigationFinding::create([
            'conversation_id' => $conversation->id,
            'finding_type' => 'potential_identity_fraud',
            'title' => 'Flag Hopping & MMSI Collision on Pacific Voyager',
            'summary' => 'MMSI 525001234 recorded transmitting across two distinct hulls within 48-hour window.',
            'confidence_level' => 'high',
            'is_verified_by_analyst' => false,
        ]);

        $this->assertFalse($finding->is_verified_by_analyst);

        // Analyst performs verification review
        $reviewRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/ai/findings/{$finding->id}/review", [
            'is_verified' => true,
            'notes' => 'Corroborated with Port State Control inspection report 2026-JKT-081. Formally escalated to investigation case #2026-004.',
        ]);

        $reviewRes->assertStatus(200)
            ->assertJsonPath('data.is_verified_by_analyst', true)
            ->assertJsonPath('data.reviewed_by', $this->seniorInv->id);

        $this->assertDatabaseHas('ai_investigation_findings', [
            'id' => $finding->id,
            'is_verified_by_analyst' => true,
            'reviewed_by' => $this->seniorInv->id,
        ]);
    }

    public function test_can_sync_entities_to_vector_embeddings_and_search(): void
    {
        // 1. Trigger Vector Embedding Synchronization
        $syncRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/ai/vector-sync');
        $syncRes->assertStatus(200)
            ->assertJsonStructure(['data' => ['synced_records']]);

        // 2. Execute Semantic Vector Search
        $searchRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/ai/vector-search', [
            'query' => 'Panama flag tuna longliner vessel oceanic',
            'top_k' => 3,
        ]);

        $searchRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'entity_type', 'entity_id', 'title', 'content', 'similarity'],
                ],
                'total',
            ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/ai/conversations');
        $response->assertStatus(401);
    }
}

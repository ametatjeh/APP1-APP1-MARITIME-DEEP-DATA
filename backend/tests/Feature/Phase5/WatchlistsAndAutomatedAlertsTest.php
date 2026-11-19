<?php

namespace Tests\Feature\Phase5;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\WatchlistCategory;
use App\Enums\WatchlistPriority;
use App\Models\AnalystAlert;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Watchlist;
use App\Services\WatchlistAlertService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WatchlistsAndAutomatedAlertsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected Vessel $vessel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->vessel = Vessel::first() ?? Vessel::create([
            'current_name' => 'PACIFIC RUNNER 01',
            'imo_number' => '9074729',
            'vessel_type' => 'tuna_longliner',
        ]);
    }

    public function test_can_create_watchlist_and_add_vessel(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/watchlists', [
            'name' => 'High Risk IUU Transshipment Fleet',
            'description' => 'Target reefer and longliner vessels suspected of illegal high-seas transshipment.',
            'category' => WatchlistCategory::IUU_HIGH_RISK->value,
            'priority' => WatchlistPriority::CRITICAL->value,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'High Risk IUU Transshipment Fleet')
            ->assertJsonPath('data.category', WatchlistCategory::IUU_HIGH_RISK->value);

        $watchlistId = $response->json('data.id');

        // Add vessel item to watchlist
        $itemRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/watchlists/{$watchlistId}/items", [
            'entity_type' => 'vessel',
            'entity_id' => $this->vessel->id,
            'reason' => 'Identified in satellite AIS dark activity cluster outside exclusive economic zone.',
        ]);

        $itemRes->assertStatus(201)
            ->assertJsonPath('data.entity_type', 'vessel')
            ->assertJsonPath('data.entity_id', $this->vessel->id);

        $this->assertDatabaseHas('watchlist_items', [
            'watchlist_id' => $watchlistId,
            'entity_id' => $this->vessel->id,
            'is_active' => true,
        ]);
    }

    public function test_can_trigger_automated_analyst_alert_when_watchlist_matched(): void
    {
        $service = app(WatchlistAlertService::class);

        $watchlist = Watchlist::create([
            'name' => 'Critical Forced Labour Watch',
            'category' => WatchlistCategory::FORCED_LABOUR_RISK,
            'priority' => WatchlistPriority::CRITICAL,
            'is_active' => true,
            'created_by' => $this->seniorInv->id,
        ]);

        $service->addItem(
            watchlist: $watchlist,
            entityType: 'vessel',
            entityId: $this->vessel->id,
            reason: 'Multiple crew death reports documented.',
            user: $this->seniorInv
        );

        // Simulate incoming event trigger
        $alert = $service->evaluateEntityWatchlists(
            entityType: 'vessel',
            entityId: $this->vessel->id,
            eventTrigger: 'forced_labour_flag',
            summary: "Vessel {$this->vessel->current_name} docked at regional port",
            context: ['port' => 'Benoa', 'date' => now()->toDateString()]
        );

        $this->assertNotNull($alert);
        $this->assertEquals(AlertSeverity::CRITICAL, $alert->severity);
        $this->assertEquals(AlertStatus::ACTIVE, $alert->status);
        $this->assertStringStartsWith('ALT-', $alert->alert_reference);

        // Check alerts endpoint returns it
        $alertRes = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/watchlists/alerts');
        $alertRes->assertStatus(200)
            ->assertJsonPath('data.0.id', $alert->id);
    }

    public function test_can_acknowledge_and_resolve_alert(): void
    {
        $alert = AnalystAlert::create([
            'alert_reference' => 'ALT-2026-TEST',
            'entity_type' => 'vessel',
            'entity_id' => $this->vessel->id,
            'alert_type' => 'identity_conflict_detected',
            'severity' => AlertSeverity::HIGH,
            'title' => 'Suspicious Call Sign Collision',
            'details' => 'Two active vessels are broadcasting the identical call sign YDA8921.',
            'status' => AlertStatus::ACTIVE,
        ]);

        // 1. Acknowledge Alert
        $ackRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/watchlists/alerts/{$alert->id}/acknowledge");
        $ackRes->assertStatus(200)
            ->assertJsonPath('data.status', AlertStatus::ACKNOWLEDGED->value);

        $alert->refresh();
        $this->assertEquals(AlertStatus::ACKNOWLEDGED, $alert->status);
        $this->assertEquals($this->seniorInv->id, $alert->acknowledged_by);

        // 2. Resolve Alert
        $resolveRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/watchlists/alerts/{$alert->id}/resolve", [
            'notes' => 'Investigator confirmed target vessel was decommissioned; identity spoofing report filed with flag administration.',
        ]);

        $resolveRes->assertStatus(200)
            ->assertJsonPath('data.status', AlertStatus::RESOLVED->value);

        $alert->refresh();
        $this->assertEquals(AlertStatus::RESOLVED, $alert->status);
        $this->assertNotNull($alert->resolution_notes);
    }
}

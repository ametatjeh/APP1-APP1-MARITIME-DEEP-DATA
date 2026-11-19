<?php

namespace Tests\Feature\Phase3;

use App\Models\DataSource;
use App\Models\IuuInfractionEvent;
use App\Models\IuuVesselListing;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IuuIntelligenceAndListingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_can_record_official_rfmo_iuu_listing(): void
    {
        $vessel = Vessel::first();
        $source = DataSource::first();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/iuu/listings', [
            'vessel_id' => $vessel->id,
            'list_name' => 'WCPFC IUU Vessel List',
            'listing_organization' => 'WCPFC',
            'listing_date' => '2024-12-10',
            'listing_reason' => 'Engaged in unauthorized fishing inside high seas pocket without valid WCPFC record authorization.',
            'listing_status' => 'listed',
            'reported_flag_state' => 'Panama',
            'official_reference_number' => 'WCPFC-IUU-2024-004',
            'source_url' => 'https://www.wcpfc.int/iuu-vessel-list',
            'data_source_id' => $source?->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.list_name', 'WCPFC IUU Vessel List')
            ->assertJsonPath('data.listing_organization', 'WCPFC')
            ->assertJsonPath('data.listing_status', 'listed');

        $this->assertDatabaseHas('iuu_vessel_listings', [
            'vessel_id' => $vessel->id,
            'listing_organization' => 'WCPFC',
        ]);
    }

    public function test_can_record_iuu_infraction_with_postgis_coordinates(): void
    {
        $vessel = Vessel::first();

        // 108.5°E, 4.2°N is in the North Natuna Sea within Indonesian EEZ
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/iuu/infractions', [
            'vessel_id' => $vessel->id,
            'infraction_type' => 'unauthorized_transshipment',
            'event_category' => 'official_adjudication',
            'severity' => 'critical',
            'event_date' => '2025-05-20',
            'location_text' => '04°12\'N 108°30\'E (North Natuna Sea)',
            'longitude' => 108.5000,
            'latitude' => 4.2000,
            'description' => 'Reefer vessel apprehended while receiving 120 tons of yellowfin tuna from unflagged catcher vessel at sea.',
            'reporting_agency' => 'PSDKP - Kementerian Kelautan dan Perikanan RI',
            'action_taken' => 'Vessel escorted to Ranai naval base, crew detained, catch seized.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.infraction_type', 'unauthorized_transshipment')
            ->assertJsonPath('data.severity', 'critical');

        $infractionId = $response->json('data.id');

        // Fetch list with coordinates
        $listResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/iuu/infractions');
        $listResponse->assertStatus(200);

        $event = collect($listResponse->json('data'))->firstWhere('id', $infractionId);
        $this->assertNotNull($event);
        $this->assertEquals(108.5, (float) $event['longitude']);
        $this->assertEquals(4.2, (float) $event['latitude']);
        $this->assertNotNull($event['geojson']);
    }
}

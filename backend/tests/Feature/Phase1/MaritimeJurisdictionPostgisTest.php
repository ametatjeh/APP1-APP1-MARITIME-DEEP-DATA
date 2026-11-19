<?php

namespace Tests\Feature\Phase1;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MaritimeJurisdictionPostgisTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('email' ,'investigator@maritimedeepdata.org')->first();
    }

    public function test_postgis_point_lookup_identifies_jurisdiction_and_overlapping_disputed_zone(): void
    {
        // Coordinates in North Natuna Sea overlap sector (approx 108.0 E, 4.5 N)
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/jurisdictions/point-lookup?longitude=108.0&latitude=4.5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'point' => ['longitude', 'latitude'],
                'containing_jurisdictions',
                'in_disputed_or_overlapping_zone',
                'overlapping_claims',
            ])
            ->assertJsonPath('in_disputed_or_overlapping_zone', true);
    }

    public function test_export_geojson_returns_valid_rfc7946_feature_collection(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/jurisdictions/export-geojson');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'type',
                'features' => [
                    '*' => [
                        'type',
                        'id',
                        'geometry' => ['type', 'coordinates'],
                        'properties' => [
                            'country_name',
                            'jurisdiction_type',
                            'legal_status',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('type', 'FeatureCollection');
    }

    public function test_can_retrieve_overlapping_claims_and_boundary_claims(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/jurisdictions/overlapping-claims');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'claim_type',
                        'resolution_status',
                        'jurisdiction_a',
                        'jurisdiction_b',
                    ],
                ],
            ]);
    }
}

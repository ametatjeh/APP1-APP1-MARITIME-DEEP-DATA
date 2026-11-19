<?php

namespace Tests\Feature\Phase3;

use App\Models\ResearchPolygon;
use App\Models\SpatialEvent;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GisResearchMapAndSpatialQueryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_can_retrieve_multi_layer_geojson_features(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/gis/layers');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonStructure([
                'type',
                'layers' => [
                    'jurisdictions' => ['type', 'features'],
                    'research_polygons' => ['type', 'features'],
                    'spatial_events' => ['type', 'features'],
                    'iuu_infractions' => ['type', 'features'],
                ],
            ]);
    }

    public function test_can_create_custom_research_polygon(): void
    {
        // Simple bounding box polygon around high risk Natuna transshipment zone
        $geojson = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [107.5, 3.5],
                    [109.5, 3.5],
                    [109.5, 5.0],
                    [107.5, 5.0],
                    [107.5, 3.5],
                ],
            ],
        ];

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/gis/polygons', [
            'name' => 'Natuna High Risk Transshipment Corridor Alpha',
            'category' => 'transshipment_corridor',
            'description' => 'Documented area of clandestine refrigerated carrier transshipments during monsoon season.',
            'geojson' => $geojson,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Natuna High Risk Transshipment Corridor Alpha')
            ->assertJsonPath('data.category', 'transshipment_corridor');

        $this->assertDatabaseHas('research_polygons', [
            'name' => 'Natuna High Risk Transshipment Corridor Alpha',
        ]);
    }

    public function test_spatial_point_containment_and_bbox_query(): void
    {
        $vessel = Vessel::first();

        // Create a spatial event at 108.2°E, 4.0°N
        $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/gis/events', [
            'title' => 'Joint Maritime Enforcement Patrol Inspection',
            'event_type' => 'inspection_point',
            'event_date' => '2025-07-04T10:00:00Z',
            'longitude' => 108.2000,
            'latitude' => 4.0000,
            'vessel_id' => $vessel->id,
            'notes' => 'Vessel boarded and logbook inspected. Catch composition recorded.',
        ])->assertStatus(201);

        // Run spatial point query at Natuna point
        $pointResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/gis/query', [
            'longitude' => 108.2000,
            'latitude' => 4.0000,
        ]);

        $pointResponse->assertStatus(200)
            ->assertJsonPath('query_type', 'point_containment')
            ->assertJsonStructure([
                'containing_jurisdictions',
                'nearby_events_within_100km',
            ]);

        // Run spatial Bounding Box query covering Natuna
        $bboxResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/gis/query', [
            'bbox' => [107.0, 3.0, 110.0, 6.0],
        ]);

        $bboxResponse->assertStatus(200)
            ->assertJsonPath('query_type', 'bbox_intersection')
            ->assertJsonStructure([
                'spatial_events',
                'intersecting_jurisdictions',
            ]);
    }
}

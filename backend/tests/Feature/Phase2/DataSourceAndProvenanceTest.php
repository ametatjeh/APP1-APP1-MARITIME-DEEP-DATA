<?php

namespace Tests\Feature\Phase2;

use App\Enums\SourceCategory;
use App\Models\DataSource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DataSourceAndProvenanceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_can_list_data_sources_with_reliability_scores(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/sources');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'category',
                        'reliability_score',
                        'source_records_count',
                        'evidences_count',
                    ],
                ],
            ]);
    }

    public function test_can_register_new_source_with_provenance_metadata(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/sources', [
            'name' => 'Center for Advanced Defense Studies (C4ADS)',
            'code' => 'C4ADS_RESEARCH',
            'category' => SourceCategory::NGO_REPORT->value,
            'publisher' => 'C4ADS Environmental Program',
            'publication_date' => '2025-11-01',
            'reliability_score' => 0.94,
            'source_url' => 'https://c4ads.org/reports',
            'description' => 'Global investigations into transnational IUU fishing networks and corporate beneficial ownership.',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'C4ADS_RESEARCH')
            ->assertJsonPath('data.category', SourceCategory::NGO_REPORT->value);
    }
}

<?php

namespace Tests\Feature\Phase4;

use App\Enums\DatasetStatus;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\DatasetVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DatasetVersioningAndProvenanceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected DataSource $source;
    protected Country $indonesia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->source = DataSource::first() ?? DataSource::create([
            'name' => 'KKP Registry',
            'code' => 'KKP_REG',
            'category' => 'government_registry',
            'publisher' => 'Kementerian Kelautan dan Perikanan',
            'organization_type' => 'government',
            'reliability_score' => 0.9,
        ]);
        $this->indonesia = Country::where('iso_code_3', 'IDN')->first();
    }

    public function test_can_register_new_dataset_version_with_sha256(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/datasets/versions', [
            'data_source_id' => $this->source->id,
            'country_id' => $this->indonesia->id,
            'dataset_name' => 'Indonesian Licensed Fishing Fleet',
            'version_tag' => 'v2026.1',
            'publication_date' => '2026-01-15',
            'effective_date' => '2026-02-01',
            'is_current' => true,
            'schema_definition' => [
                'columns' => ['name', 'imo', 'mmsi', 'callsign', 'tonnage', 'gear_type'],
                'primary_key' => 'imo',
            ],
            'changelog' => 'Updated longliner registrations and added newly issued licenses for Natuna Sea.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.version_tag', 'v2026.1')
            ->assertJsonPath('data.status', DatasetStatus::ACTIVE->value);

        $this->assertNotEmpty($response->json('data.snapshot_sha256'));

        $this->assertDatabaseHas('dataset_versions', [
            'dataset_name' => 'Indonesian Licensed Fishing Fleet',
            'version_tag' => 'v2026.1',
            'is_current' => true,
        ]);
    }

    public function test_can_deprecate_or_supersede_dataset_version(): void
    {
        $version = DatasetVersion::create([
            'data_source_id' => $this->source->id,
            'country_id' => $this->indonesia->id,
            'dataset_name' => 'Legacy KKP Fleet Registry',
            'version_tag' => 'v2024.0',
            'publication_date' => '2024-01-01',
            'effective_date' => '2024-01-01',
            'is_current' => true,
            'snapshot_sha256' => hash('sha256', 'v2024'),
            'records_count' => 500,
            'status' => DatasetStatus::ACTIVE,
            'created_by' => $this->seniorInv->id,
        ]);

        $response = $this->actingAs($this->seniorInv, 'sanctum')->putJson("/api/v1/datasets/versions/{$version->id}", [
            'status' => DatasetStatus::DEPRECATED->value,
            'deprecation_date' => '2026-11-19',
            'is_current' => false,
            'changelog' => 'Deprecated due to issuance of updated consolidated database.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', DatasetStatus::DEPRECATED->value)
            ->assertJsonPath('data.is_current', false);

        $version->refresh();
        $this->assertEquals(DatasetStatus::DEPRECATED, $version->status);
        $this->assertFalse($version->is_current);
    }

    public function test_import_increments_dataset_version_records_count(): void
    {
        $version = DatasetVersion::create([
            'data_source_id' => $this->source->id,
            'country_id' => $this->indonesia->id,
            'dataset_name' => 'Test Fleet Incremental Ingestion',
            'version_tag' => 'v1.0-INC',
            'publication_date' => '2026-06-01',
            'effective_date' => '2026-06-01',
            'is_current' => true,
            'snapshot_sha256' => hash('sha256', 'inc-test'),
            'records_count' => 10,
            'status' => DatasetStatus::ACTIVE,
            'created_by' => $this->seniorInv->id,
        ]);

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/imports', [
            'data_source_id' => $this->source->id,
            'dataset_version_id' => $version->id,
            'country_id' => $this->indonesia->id,
            'target_entity_type' => 'vessel',
            'rows' => [
                [
                    'name' => 'KM LAUT CERIA 01',
                    'imo' => '9074729',
                    'flag' => 'Indonesia',
                ],
            ],
        ]);

        $response->assertStatus(201);

        $version->refresh();
        $this->assertEquals(11, $version->records_count);
    }
}

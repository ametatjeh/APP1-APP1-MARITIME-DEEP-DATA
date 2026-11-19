<?php

namespace Tests\Feature\Phase4;

use App\Enums\ImportStatus;
use App\Enums\QuarantineReason;
use App\Models\DataImport;
use App\Models\DataSource;
use App\Models\ImportQuarantinedRecord;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DataImportPipelinesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected DataSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->source = DataSource::first() ?? DataSource::create([
            'name' => 'WCPFC Record of Fishing Vessels',
            'code' => 'WCPFC_TEST_REG',
            'category' => 'regional_fisheries_body',
            'publisher' => 'Western and Central Pacific Fisheries Commission',
            'organization_type' => 'regional_fisheries_body',
            'reliability_score' => 0.95,
        ]);
    }

    public function test_can_run_import_pipeline_and_quarantine_invalid_imo(): void
    {
        // IMO 9074729 has valid checksum: (9*7 + 0*6 + 7*5 + 4*4 + 7*3 + 2*2) = 139 => 139 % 10 = 9
        // IMO 9074721 has invalid checksum: 9 != 1
        $payload = [
            'data_source_id' => $this->source->id,
            'target_entity_type' => 'vessel',
            'filename' => 'wcpfc_authorized_vessels_2026.json',
            'rows' => [
                [
                    'record_id' => 'WCPFC-TEST-001',
                    'name' => 'PACIFIC OCEAN EXPLORER',
                    'imo' => '9074729',
                    'mmsi' => '525123456',
                    'callsign' => 'YDA8921',
                    'flag' => 'Indonesia',
                    'vessel_type' => 'tuna_longliner',
                    'length' => 54.2,
                    'tonnage' => 780,
                ],
                [
                    'record_id' => 'WCPFC-TEST-002',
                    'name' => 'SUSPECT TUNA VESSEL 02',
                    'imo' => '9074721', // Invalid checksum!
                    'mmsi' => '351987654',
                    'callsign' => 'HP9821',
                    'flag' => 'Panama',
                    'vessel_type' => 'purse_seiner',
                ],
            ],
        ];

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/imports', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows_count', 1)
            ->assertJsonPath('data.quarantined_rows_count', 1)
            ->assertJsonPath('data.status', ImportStatus::PARTIALLY_IMPORTED->value);

        $importId = $response->json('data.id');

        // Check master DB created valid entity
        $this->assertDatabaseHas('vessels', [
            'current_name' => 'PACIFIC OCEAN EXPLORER',
            'imo_number' => '9074729',
        ]);

        // Check quarantine DB isolated invalid IMO row without crashing
        $this->assertDatabaseHas('import_quarantined_records', [
            'data_import_id' => $importId,
            'row_index' => 2,
            'quarantine_reason' => QuarantineReason::INVALID_IMO_CHECKSUM->value,
            'resolution_status' => 'pending_review',
        ]);
    }

    public function test_can_inspect_quarantined_records_and_resolve_by_correction(): void
    {
        $import = DataImport::create([
            'batch_reference' => 'IMP-2026-TEST',
            'data_source_id' => $this->source->id,
            'source_filename' => 'corrupted_batch.csv',
            'file_format' => 'csv',
            'file_size_bytes' => 1024,
            'file_sha256' => hash('sha256', 'dummy'),
            'target_entity_type' => 'vessel',
            'status' => ImportStatus::PARTIALLY_IMPORTED,
            'total_rows' => 1,
            'processed_rows' => 1,
            'valid_rows_count' => 0,
            'quarantined_rows_count' => 1,
            'created_by' => $this->seniorInv->id,
        ]);

        $quarantined = ImportQuarantinedRecord::create([
            'data_import_id' => $import->id,
            'row_index' => 1,
            'raw_payload' => [
                'record_id' => 'BAD-ROW-1',
                'name' => 'CORRECTABLE MARU',
                'imo' => '9074721', // bad checksum
                'flag' => 'Japan',
            ],
            'quarantine_reason' => QuarantineReason::INVALID_IMO_CHECKSUM,
            'error_details' => 'Invalid IMO checksum.',
            'resolution_status' => 'pending_review',
        ]);

        // 1. List quarantined records
        $listResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/imports/{$import->id}/quarantine");
        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.id', $quarantined->id);

        // 2. Resolve by correcting payload with valid IMO (9074729)
        $resolveResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson(
            "/api/v1/imports/{$import->id}/quarantine/{$quarantined->id}/resolve",
            [
                'decision' => 'correct_and_import',
                'corrected_payload' => [
                    'record_id' => 'BAD-ROW-1',
                    'name' => 'CORRECTABLE MARU',
                    'imo' => '9074729',
                    'flag' => 'Japan',
                ],
                'notes' => 'Typo in last digit verified by registry documentation.',
            ]
        );

        $resolveResponse->assertStatus(200)
            ->assertJsonPath('data.resolution_status', 'corrected_and_reprocessed');

        // Verify vessel created in master DB
        $this->assertDatabaseHas('vessels', [
            'current_name' => 'CORRECTABLE MARU',
            'imo_number' => '9074729',
        ]);

        // Verify import counts updated
        $import->refresh();
        $this->assertEquals(1, $import->valid_rows_count);
        $this->assertEquals(0, $import->quarantined_rows_count);
    }

    public function test_can_discard_quarantined_record(): void
    {
        $import = DataImport::create([
            'batch_reference' => 'IMP-2026-DISCARD',
            'data_source_id' => $this->source->id,
            'source_filename' => 'corrupted_batch2.csv',
            'file_format' => 'csv',
            'file_size_bytes' => 512,
            'file_sha256' => hash('sha256', 'dummy2'),
            'target_entity_type' => 'vessel',
            'status' => ImportStatus::PARTIALLY_IMPORTED,
            'total_rows' => 1,
            'processed_rows' => 1,
            'valid_rows_count' => 0,
            'quarantined_rows_count' => 1,
            'created_by' => $this->seniorInv->id,
        ]);

        $quarantined = ImportQuarantinedRecord::create([
            'data_import_id' => $import->id,
            'row_index' => 1,
            'raw_payload' => ['junk' => 'gibberish'],
            'quarantine_reason' => QuarantineReason::SCHEMA_ERROR,
            'error_details' => 'Missing vessel name.',
            'resolution_status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson(
            "/api/v1/imports/{$import->id}/quarantine/{$quarantined->id}/resolve",
            [
                'decision' => 'discard',
                'notes' => 'Completely corrupted header record.',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.resolution_status', 'discarded');

        $quarantined->refresh();
        $this->assertEquals('discarded', $quarantined->resolution_status);
    }
}

<?php

namespace Tests\Feature\Phase5;

use App\Enums\ReportType;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\ResearchReport;
use App\Models\SourceRecord;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResearchReportsAndDossierTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected Vessel $vessel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();

        $source = DataSource::first() ?? DataSource::create([
            'name' => 'Trial Registry',
            'code' => 'TRIAL_REG',
            'category' => 'government',
            'organization_type' => 'government',
        ]);

        $sourceRec = SourceRecord::create([
            'data_source_id' => $source->id,
            'record_identifier' => 'REC-DOSSIER-001',
            'record_date' => '2026-01-01',
            'verbatim_payload' => ['name' => 'SEA PHANTOM'],
            'sha256_hash' => hash('sha256', 'dummy'),
            'verification_status' => 'verified',
        ]);

        $this->vessel = Vessel::create([
            'current_name' => 'SEA PHANTOM VII',
            'imo_number' => '9074729',
            'current_mmsi' => '525123456',
            'vessel_type' => 'reefer',
            'gross_tonnage' => 1250.5,
        ]);

        VesselName::create([
            'vessel_id' => $this->vessel->id,
            'name' => 'SEA PHANTOM VII',
            'is_current' => true,
            'source_record_id' => $sourceRec->id,
            'valid_from' => '2025-01-01',
        ]);
    }

    public function test_can_compile_evidence_linked_vessel_dossier(): void
    {
        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson(
            "/api/v1/reports/vessels/{$this->vessel->id}/dossier",
            ['classification' => 'restricted']
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.report_type', ReportType::VESSEL_DOSSIER->value)
            ->assertJsonPath('data.classification', 'restricted');

        $reportId = $response->json('data.id');
        $this->assertNotEmpty($response->json('data.report_reference'));
        $this->assertNotEmpty($response->json('data.sha256_checksum'));
        $this->assertNotEmpty($response->json('data.body_markdown'));

        // Verify citations created linking source records
        $this->assertDatabaseHas('report_citations', [
            'research_report_id' => $reportId,
            'citation_key' => '[CIT-1]',
        ]);
    }

    public function test_can_export_report_dossier(): void
    {
        $report = ResearchReport::create([
            'report_reference' => 'REP-2026-TEST',
            'title' => 'Exportable Dossier',
            'report_type' => ReportType::VESSEL_DOSSIER,
            'classification' => 'confidential',
            'executive_summary' => 'Dossier summary',
            'body_markdown' => '# Executive Dossier Body',
            'structured_payload' => ['vessel' => 'SEA PHANTOM'],
            'sha256_checksum' => hash('sha256', 'report_export'),
            'created_by' => $this->seniorInv->id,
        ]);

        $response = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/reports/{$report->id}/export");

        $response->assertStatus(200)
            ->assertJsonPath('report_reference', 'REP-2026-TEST')
            ->assertJsonPath('sha256_checksum', hash('sha256', 'report_export'))
            ->assertJsonStructure([
                'report_reference',
                'title',
                'report_type',
                'classification',
                'sha256_checksum',
                'executive_summary',
                'markdown',
                'snapshot',
                'citations',
                'exported_at',
            ]);
    }
}

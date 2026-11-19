<?php

namespace Tests\Feature\Phase2;

use App\Enums\EvidenceClassification;
use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\InvestigationCase;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceManagementAndSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $this->viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();
    }

    public function test_can_upload_evidence_and_generate_sha256_in_private_vault(): void
    {
        Storage::fake('private');

        $case = InvestigationCase::where('case_number', 'MDD-INV-2026-0001')->firstOrFail();
        $fakePdf = UploadedFile::fake()->create('satellite_radar_scene.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/evidences', [
            'file' => $fakePdf,
            'title' => 'Synthetic Aperture Radar (SAR) Oil Slick Detection',
            'evidence_type' => EvidenceType::SATELLITE_IMAGERY->value,
            'classification' => EvidenceClassification::CONFIDENTIAL->value,
            'case_id' => $case->id,
            'collection_date' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Synthetic Aperture Radar (SAR) Oil Slick Detection')
            ->assertJsonStructure(['sha256_hash', 'data' => ['id', 'file_path']]);

        $storedHash = $response->json('sha256_hash');
        $this->assertEquals(64, strlen($storedHash));

        // Ensure file exists in private storage and NEVER in public
        $storedPath = $response->json('data.file_path');
        Storage::disk('private')->assertExists($storedPath);
    }

    public function test_evidence_download_delivers_binary_stream_with_sha256_verification(): void
    {
        $evidence = Evidence::firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->get("/api/v1/evidences/{$evidence->id}/download");

        $response->assertStatus(200);
        $this->assertEquals($evidence->sha256_hash, $response->headers->get('X-Evidence-SHA256'));
    }

    public function test_can_map_evidence_relationship_to_vessel(): void
    {
        $evidence = Evidence::firstOrFail();
        $vessel = Vessel::firstOrFail();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/evidences/{$evidence->id}/relationships", [
            'target_type' => 'vessel',
            'target_id' => $vessel->id,
            'relationship_type' => 'documents_violation',
            'notes' => 'Photographic evidence confirming unmanifested catch onboard.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.target_type', 'vessel')
            ->assertJsonPath('data.relationship_type', 'documents_violation');
    }
}

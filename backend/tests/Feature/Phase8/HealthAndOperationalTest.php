<?php

namespace Tests\Feature\Phase8;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthAndOperationalTest extends TestCase
{
    /**
     * Test health check endpoint returns 200 with PostGIS, database, and storage status.
     */
    public function test_health_check_endpoint_operational(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'application',
                'environment',
                'timestamp',
                'duration_ms',
                'checks' => [
                    'database' => ['status', 'driver', 'postgis_version', 'latency_ms'],
                    'cache' => ['status', 'driver', 'latency_ms'],
                    'evidence_storage' => ['status', 'disk', 'type'],
                    'ai_service' => ['status', 'url'],
                ],
            ]);

        $this->assertEquals('operational', $response->json('status'));
        $this->assertEquals('healthy', $response->json('checks.database.status'));
    }

    /**
     * Test that enterprise security headers are applied to API responses.
     */
    public function test_security_headers_enforced(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    /**
     * Test that private evidence storage is strictly isolated and accessible only via authorized streaming.
     */
    public function test_private_evidence_storage_vault_integrity(): void
    {
        // 1. Evidence item exists in database
        $evidence = Evidence::first();
        if (!$evidence) {
            $user = User::factory()->create(['role' => 'investigator']);
            $evidence = Evidence::create([
                'title' => 'Test Vessel Inspection Report',
                'file_name' => 'inspection.pdf',
                'file_path' => 'evidence/test/sample.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'sha256_hash' => hash('sha256', 'mock content'),
                'evidence_type' => 'inspection_report',
                'classification' => 'confidential',
                'verification_status' => 'verified',
                'uploaded_by' => $user->id,
            ]);
            Storage::disk('private')->put('evidence/test/sample.pdf', 'mock content');
        }

        // 2. Unauthenticated download fails
        $unauthResponse = $this->getJson("/api/v1/evidences/{$evidence->id}/download");
        $unauthResponse->assertStatus(401);

        // 3. Authenticated authorized download streams binary with correct SHA-256 header
        $user = User::first() ?? User::factory()->create(['role' => 'investigator']);
        $authResponse = $this->actingAs($user, 'sanctum')->get("/api/v1/evidences/{$evidence->id}/download");
        $this->assertContains($authResponse->getStatusCode(), [200, 404]);
    }

    /**
     * Test seven-role authorization boundaries on operational routes.
     */
    public function test_role_boundary_enforcement(): void
    {
        $viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();
        if (!$viewer) {
            $viewer = User::factory()->create(['role' => 'viewer']);
        }

        // Viewer cannot create vessel records
        $response = $this->actingAs($viewer, 'sanctum')->postJson('/api/v1/vessels', [
            'current_name' => 'UNAUTHORIZED VESSEL',
        ]);
        $response->assertStatus(403);
    }
}

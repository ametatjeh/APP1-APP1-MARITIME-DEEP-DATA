<?php

namespace Tests\Feature\Phase1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthenticationAndRbacTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_login_and_receive_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@maritimedeepdata.org',
            'password' => 'SecretAdmin2026!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'role_label',
                    'can_edit',
                ],
            ]);
    }

    public function test_viewer_role_is_forbidden_from_creating_vessels(): void
    {
        $viewer = User::where('email', 'viewer@maritimedeepdata.org')->first();

        $response = $this->actingAs($viewer, 'sanctum')->postJson('/api/v1/vessels', [
            'current_name' => 'UNAUTHORIZED VESSEL',
        ]);

        $response->assertStatus(403);
    }

    public function test_investigator_role_can_create_vessel_master_record(): void
    {
        $investigator = User::where('email', 'investigator@maritimedeepdata.org')->first();

        $response = $this->actingAs($investigator, 'sanctum')->postJson('/api/v1/vessels', [
            'current_name' => 'KM RESEARCHER ONE',
            'vessel_type' => 'research',
            'gross_tonnage' => 250.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.current_name', 'KM RESEARCHER ONE');
    }
}

<?php

namespace Tests\Feature\Phase1;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiCountryManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@maritimedeepdata.org')->first();
    }

    public function test_can_list_countries_with_dynamic_counts(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/countries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'iso_code_2',
                        'iso_code_3',
                        'name',
                        'is_active',
                        'regulations_count',
                        'datasets_count',
                    ],
                ],
            ]);
    }

    public function test_can_retrieve_indonesia_profile_and_regulations_without_hardcoded_logic(): void
    {
        $indonesia = Country::where('iso_code_2', 'ID')->firstOrFail();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/countries/{$indonesia->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.iso_code_2', 'ID')
            ->assertJsonPath('data.name', 'Indonesia');

        $regResponse = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/countries/{$indonesia->id}/regulations");
        $regResponse->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'title', 'regulation_number']]]);
    }

    public function test_can_create_new_foreign_country_demonstrating_multi_country_architecture(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/countries', [
            'iso_code_2' => 'TH',
            'iso_code_3' => 'THA',
            'numeric_code' => '764',
            'name' => 'Thailand',
            'official_name' => 'Kingdom of Thailand',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.iso_code_2', 'TH')
            ->assertJsonPath('data.name', 'Thailand');
    }
}

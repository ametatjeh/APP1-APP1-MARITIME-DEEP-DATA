<?php

namespace Tests\Feature\Phase4;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DataValidationEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected User $seniorInv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
    }

    public function test_validates_imo_checksum_correctly(): void
    {
        // Valid IMO 9074729: sum = 9*7 + 0*6 + 7*5 + 4*4 + 7*3 + 2*2 = 139 => 139 % 10 = 9
        $validRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/validate-identifier', [
            'type' => 'imo',
            'value' => 'IMO 9074729',
        ]);

        $validRes->assertStatus(200)
            ->assertJsonPath('result.is_valid', true)
            ->assertJsonPath('result.cleaned_imo', '9074729');

        // Invalid checksum: 9074721 has check digit 1 instead of 9
        $invalidRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/validate-identifier', [
            'type' => 'imo',
            'value' => '9074721',
        ]);

        $invalidRes->assertStatus(200)
            ->assertJsonPath('result.is_valid', false)
            ->assertJsonPath('result.expected_check_digit', 9)
            ->assertJsonPath('result.actual_check_digit', 1);
    }

    public function test_validates_mmsi_and_itu_mid_mapping(): void
    {
        // Indonesian MMSI starting with 525
        $inaRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/validate-identifier', [
            'type' => 'mmsi',
            'value' => '525123456',
        ]);

        $inaRes->assertStatus(200)
            ->assertJsonPath('result.is_valid', true)
            ->assertJsonPath('result.mid', '525')
            ->assertJsonPath('result.country_name', 'Indonesia');

        // Panama MMSI starting with 351
        $panRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/validate-identifier', [
            'type' => 'mmsi',
            'value' => '351987654',
        ]);

        $panRes->assertStatus(200)
            ->assertJsonPath('result.is_valid', true)
            ->assertJsonPath('result.country_name', 'Panama');

        // Bad length MMSI (8 digits)
        $badRes = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/validate-identifier', [
            'type' => 'mmsi',
            'value' => '52512345',
        ]);

        $badRes->assertStatus(200)
            ->assertJsonPath('result.is_valid', false);
    }

    public function test_dry_run_preview_without_saving(): void
    {
        $previewPayload = [
            'target_entity_type' => 'vessel',
            'rows' => [
                [
                    'name' => 'LEGAL OCEAN RUNNER',
                    'imo' => '9074729',
                    'mmsi' => '525000001',
                ],
                [
                    'name' => 'FORGED IDENTIFIER VESSEL',
                    'imo' => '9074728', // Bad checksum
                    'mmsi' => '351000002',
                ],
                [
                    // Missing name
                    'imo' => '9074729',
                ],
            ],
        ];

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/validation/preview', $previewPayload);

        $response->assertStatus(200)
            ->assertJsonPath('summary.total_analyzed', 3)
            ->assertJsonPath('summary.valid_count', 1)
            ->assertJsonPath('summary.invalid_count', 2)
            ->assertJsonCount(1, 'sample_valid')
            ->assertJsonCount(2, 'sample_quarantined');
    }
}

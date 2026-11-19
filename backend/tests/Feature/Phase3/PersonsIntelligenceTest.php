<?php

namespace Tests\Feature\Phase3;

use App\Models\Company;
use App\Models\Country;
use App\Models\Person;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PersonsIntelligenceTest extends TestCase
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

    public function test_can_create_person_and_encrypt_pii(): void
    {
        $country = Country::where('iso_code_3', 'IDN')->first();

        $response = $this->actingAs($this->seniorInv, 'sanctum')->postJson('/api/v1/persons', [
            'full_name' => 'Alexander Chen',
            'primary_role' => 'beneficial_owner',
            'date_of_birth' => '1975-08-14',
            'place_of_birth' => 'Singapore',
            'nationality_country_id' => $country?->id,
            'passport_number' => 'E12345678',
            'national_id_number' => 'S1234567A',
            'is_pep' => true,
            'notes' => 'Ultimate beneficial owner of several offshore holding companies operating reefer carriers.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'Alexander Chen')
            ->assertJsonPath('data.primary_role', 'beneficial_owner')
            ->assertJsonPath('data.is_pep', true);

        $personId = $response->json('data.id');
        $person = Person::findOrFail($personId);

        // Assert encrypted PII is stored securely and decrypted via model
        $this->assertEquals('E12345678', $person->encrypted_passport_number);
        $this->assertEquals('S1234567A', $person->encrypted_national_id);
        $this->assertStringContainsString('PPT-', $person->masked_identifier);
    }

    public function test_viewer_role_receives_redacted_pii(): void
    {
        $person = Person::create([
            'full_name' => 'Captain Viktor Petrov',
            'primary_role' => 'skipper',
            'encrypted_passport_number' => 'RUS987654321',
            'masked_identifier' => 'PPT-RU****21',
            'verification_status' => 'verified',
        ]);

        // Senior investigator can view full PII
        $invResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson("/api/v1/persons/{$person->id}");
        $invResponse->assertStatus(200)
            ->assertJsonPath('data.encrypted_passport_number', 'RUS987654321')
            ->assertJsonPath('data.is_redacted', false);

        // Viewer role receives redacted PII
        $viewerResponse = $this->actingAs($this->viewer, 'sanctum')->getJson("/api/v1/persons/{$person->id}");
        $viewerResponse->assertStatus(200)
            ->assertJsonPath('data.encrypted_passport_number', null)
            ->assertJsonPath('data.is_redacted', true);
    }

    public function test_can_link_person_corporate_and_vessel_ties(): void
    {
        $person = Person::create([
            'full_name' => 'Hendri Wijaya',
            'primary_role' => 'director',
            'verification_status' => 'verified',
        ]);

        $company = Company::first();
        $vessel = Vessel::first();

        // Add corporate tie
        $corpResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/persons/{$person->id}/corporate-ties", [
            'company_id' => $company->id,
            'role' => 'Managing Director',
            'shares_percentage' => 35.50,
            'effective_from' => '2020-01-15',
            'is_current' => true,
        ]);

        $corpResponse->assertStatus(201)
            ->assertJsonPath('data.role', 'Managing Director');

        // Add vessel tie
        $vesselResponse = $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/persons/{$person->id}/vessel-ties", [
            'vessel_id' => $vessel->id,
            'role' => 'Designated Person Ashore (DPA)',
            'effective_from' => '2021-06-01',
            'is_current' => true,
        ]);

        $vesselResponse->assertStatus(201)
            ->assertJsonPath('data.role', 'Designated Person Ashore (DPA)');

        // Verify loaded relationships
        $this->assertCount(1, $person->corporateTies);
        $this->assertCount(1, $person->vesselTies);
    }

    public function test_fuzzy_search_by_person_alias(): void
    {
        $person = Person::create([
            'full_name' => 'Zhang Wei Long',
            'primary_role' => 'beneficial_owner',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($this->seniorInv, 'sanctum')->postJson("/api/v1/persons/{$person->id}/aliases", [
            'alias_name' => 'Ah Long',
            'alias_type' => 'nickname',
            'notes' => 'Street name used during fishing quota brokerage',
        ])->assertStatus(201);

        // Search by alias name
        $searchResponse = $this->actingAs($this->seniorInv, 'sanctum')->getJson('/api/v1/persons?search=Ah Long');
        $searchResponse->assertStatus(200);
        $this->assertTrue(collect($searchResponse->json('data'))->contains('id', $person->id));
    }
}

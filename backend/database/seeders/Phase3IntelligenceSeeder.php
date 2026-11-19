<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CrewEmployment;
use App\Models\DataSource;
use App\Models\InvestigationCase;
use App\Models\IuuInfractionEvent;
use App\Models\IuuVesselListing;
use App\Models\LabourViolationReport;
use App\Models\ManningAgent;
use App\Models\Person;
use App\Models\ResearchPolygon;
use App\Models\SpatialEvent;
use App\Models\Vessel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase3IntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $indonesia = Country::where('iso_code_3', 'IDN')->first();
        $vessel = Vessel::first();
        $source = DataSource::first();
        $case = InvestigationCase::first();

        // 1. Persons
        $alexander = Person::firstOrCreate(
            ['full_name' => 'Alexander Chen'],
            [
                'primary_role' => 'beneficial_owner',
                'date_of_birth' => '1974-06-12',
                'place_of_birth' => 'Singapore',
                'nationality_country_id' => $indonesia?->id,
                'encrypted_passport_number' => 'E78492019',
                'encrypted_national_id' => 'S7482910A',
                'masked_identifier' => 'PPT-E7****19',
                'is_pep' => true,
                'is_sanctioned' => true,
                'sanction_details' => ['listed_by' => 'OFAC/Treasury', 'reason' => 'IUU Network Financing'],
                'verification_status' => 'verified',
                'notes' => 'Suspected ultimate beneficial owner behind complex offshore nominee networks operating tuna reefers.',
            ]
        );

        $captainViktor = Person::firstOrCreate(
            ['full_name' => 'Capt. Viktor Petrov'],
            [
                'primary_role' => 'skipper',
                'date_of_birth' => '1968-11-04',
                'place_of_birth' => 'Vladivostok',
                'encrypted_passport_number' => 'RUS482910284',
                'masked_identifier' => 'PPT-RU****84',
                'verification_status' => 'verified',
                'notes' => 'Vessel Master with 25 years experience in deep sea carrier transshipment.',
            ]
        );

        $crewBudi = Person::firstOrCreate(
            ['full_name' => 'Budi Santoso'],
            [
                'primary_role' => 'crew',
                'date_of_birth' => '1998-03-21',
                'place_of_birth' => 'Tegal, Jawa Tengah',
                'nationality_country_id' => $indonesia?->id,
                'encrypted_seaman_book_number' => 'IDN-SB-994821',
                'masked_identifier' => 'SB-****4821',
                'verification_status' => 'verified',
                'notes' => 'Fisheries crew survivor of 18-month forced at-sea transshipment.',
            ]
        );

        // Aliases
        $alexander->aliases()->firstOrCreate(
            ['alias_name' => 'Ah Chen'],
            ['alias_type' => 'nickname', 'notes' => 'Trading name in Pacific fishing ports']
        );

        // Vessel ties
        if ($vessel) {
            $captainViktor->vesselTies()->firstOrCreate(
                ['vessel_id' => $vessel->id, 'role' => 'skipper_master'],
                ['effective_from' => '2023-01-01', 'is_current' => true, 'verification_status' => 'verified']
            );
        }

        // 2. Manning Agent
        $manningAgent = ManningAgent::firstOrCreate(
            ['agent_name' => 'PT Samudera Bahari Sentosa'],
            [
                'license_number' => 'SIP3MI-2023-0142',
                'country_id' => $indonesia?->id,
                'registered_address' => 'Jl. Pelabuhan Samudera No. 44, Tanjung Priok, Jakarta Utara',
                'compliance_rating' => 'under_scrutiny',
                'past_violations_summary' => 'Investigation pending regarding confiscation of passports and unpaid wage deductions.',
                'verification_status' => 'verified',
            ]
        );

        // 3. Crew Employment & Labour Violations
        if ($vessel) {
            $employment = CrewEmployment::firstOrCreate(
                ['person_id' => $crewBudi->id, 'vessel_id' => $vessel->id],
                [
                    'manning_agent_id' => $manningAgent->id,
                    'role_on_board' => 'deckhand',
                    'embarkation_date' => '2024-03-10',
                    'embarkation_port' => 'Benoa, Bali',
                    'disembarkation_date' => '2025-09-15',
                    'disembarkation_port' => 'Bitung, Sulawesi Utara',
                    'contract_status' => 'escaped_reported',
                    'salary_currency' => 'USD',
                    'reported_monthly_wage' => 300.00,
                    'is_forced_labour_suspected' => true,
                    'notes' => 'Crew escaped during bunker replenishment at port.',
                ]
            );

            LabourViolationReport::firstOrCreate(
                ['crew_employment_id' => $employment->id, 'indicator_type' => 'retention_of_documents'],
                [
                    'vessel_id' => $vessel->id,
                    'person_id' => $crewBudi->id,
                    'severity' => 'critical',
                    'incident_date' => '2024-03-11',
                    'incident_description' => 'Captain confiscated original seaman book and national identity card immediately upon departure outside Indonesian archipelagic waters.',
                    'reported_by' => 'DFW Indonesia & Witness Statement',
                    'reporting_organization' => 'Destructive Fishing Watch',
                    'is_verified' => true,
                    'verification_status' => 'verified',
                    'investigation_case_id' => $case?->id,
                ]
            );

            LabourViolationReport::firstOrCreate(
                ['crew_employment_id' => $employment->id, 'indicator_type' => 'isolation_transshipment'],
                [
                    'vessel_id' => $vessel->id,
                    'person_id' => $crewBudi->id,
                    'severity' => 'high',
                    'incident_date' => '2024-11-20',
                    'incident_description' => 'Vessel remained at sea continuously for 18 months without docking, transferring catch and resupplying exclusively via refrigerated cargo reefers.',
                    'reported_by' => 'Survivor Debrief',
                    'is_verified' => true,
                    'verification_status' => 'verified',
                    'investigation_case_id' => $case?->id,
                ]
            );
        }

        // 4. IUU Vessel Listings & Infractions
        if ($vessel) {
            IuuVesselListing::firstOrCreate(
                ['vessel_id' => $vessel->id, 'listing_organization' => 'WCPFC'],
                [
                    'list_name' => 'WCPFC IUU Vessel List',
                    'listing_date' => '2024-12-15',
                    'listing_reason' => 'Engaged in unauthorized fishing and high seas transshipment in WCPFC convention area without valid authorization.',
                    'listing_status' => 'listed',
                    'reported_flag_state' => 'Panama',
                    'reported_owner' => 'Pacific Ocean Marine Ltd',
                    'official_reference_number' => 'WCPFC-IUU-2024-009',
                    'source_url' => 'https://www.wcpfc.int/iuu-vessel-list',
                    'data_source_id' => $source?->id,
                    'verification_status' => 'verified',
                ]
            );

            // Create PostGIS IUU Infraction Event at Natuna (108.5°E, 4.2°N)
            $existingInfraction = IuuInfractionEvent::where('vessel_id', $vessel->id)
                ->where('infraction_type', 'unauthorized_transshipment')
                ->first();

            if (!$existingInfraction) {
                $infraction = new IuuInfractionEvent([
                    'vessel_id' => $vessel->id,
                    'infraction_type' => 'unauthorized_transshipment',
                    'event_category' => 'official_adjudication',
                    'severity' => 'critical',
                    'event_date' => '2025-05-18',
                    'location_text' => '04°12\'N 108°30\'E (North Natuna Sea)',
                    'description' => 'Clandestine rendezvous with carrier reefer transferring 150MT of yellowfin tuna without mandatory AIS transmission.',
                    'reporting_agency' => 'PSDKP - KKP RI',
                    'action_taken' => 'Target apprehended by Maritime Patrol, legal proceedings initiated.',
                    'investigation_case_id' => $case?->id,
                    'data_source_id' => $source?->id,
                    'verification_status' => 'verified',
                ]);
                $infraction->geom = DB::raw("ST_SetSRID(ST_MakePoint(108.5000, 4.2000), 4326)");
                $infraction->save();
            }
        }

        // 5. Research Polygons & Spatial Events
        $existingPolygon = ResearchPolygon::where('name', 'Natuna IUU & Transshipment Hotspot AOI')->first();
        if (!$existingPolygon) {
            $geojson = [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [107.0, 3.0],
                        [110.0, 3.0],
                        [110.0, 5.5],
                        [107.0, 5.5],
                        [107.0, 3.0],
                    ],
                ],
            ];
            $geojsonStr = json_encode($geojson);

            $poly = new ResearchPolygon([
                'name' => 'Natuna IUU & Transshipment Hotspot AOI',
                'category' => 'high_risk_iuu_zone',
                'description' => 'Designated NGO analytical area covering overlapping maritime claims and persistent dark fleet encounters in North Natuna Sea.',
                'is_active' => true,
            ]);
            $poly->geom = DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON('{$geojsonStr}'), 4326))");
            $poly->save();
        }

        $existingEvent = SpatialEvent::where('title', 'Joint Boarding & Inspection KM Jaya 88')->first();
        if (!$existingEvent && $vessel) {
            $event = new SpatialEvent([
                'title' => 'Joint Boarding & Inspection KM Jaya 88',
                'event_type' => 'inspection_point',
                'event_date' => '2025-05-18 14:30:00',
                'vessel_id' => $vessel->id,
                'investigation_case_id' => $case?->id,
                'notes' => 'Physical logbooks, crew manifests, and electronic navigational cards seized during maritime interdiction.',
            ]);
            $event->geom = DB::raw("ST_SetSRID(ST_MakePoint(108.3500, 4.1200), 4326)");
            $event->save();
        }
    }
}

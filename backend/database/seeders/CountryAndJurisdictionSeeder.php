<?php

namespace Database\Seeders;

use App\Enums\JurisdictionType;
use App\Enums\LegalStatus;
use App\Enums\VerificationStatus;
use App\Models\Country;
use App\Models\CountryDataset;
use App\Models\CountryRegulation;
use App\Models\DataSource;
use App\Models\MaritimeBoundaryClaim;
use App\Models\MaritimeJurisdiction;
use App\Models\OverlappingClaim;
use App\Models\SourceRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryAndJurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Sources
        $kkpSource = DataSource::firstOrCreate(
            ['code' => 'ID_KKP'],
            [
                'name' => 'Kementerian Kelautan dan Perikanan Republik Indonesia',
                'organization_type' => 'government',
                'reliability_score' => 0.95,
                'source_url' => 'https://kkp.go.id',
                'description' => 'Official registry for Indonesian fishing licenses, vessel permits, and maritime regulations.',
                'is_active' => true,
            ]
        );

        $unDoalosSource = DataSource::firstOrCreate(
            ['code' => 'UN_DOALOS'],
            [
                'name' => 'UN Division for Ocean Affairs and the Law of the Sea',
                'organization_type' => 'public',
                'reliability_score' => 0.98,
                'source_url' => 'https://www.un.org/Depts/los',
                'description' => 'UNCLOS baseline declarations, continental shelf submissions, and maritime treaty registry.',
                'is_active' => true,
            ]
        );

        $wcpfcSource = DataSource::firstOrCreate(
            ['code' => 'RFMO_WCPFC'],
            [
                'name' => 'Western and Central Pacific Fisheries Commission',
                'organization_type' => 'rfmo',
                'reliability_score' => 0.92,
                'source_url' => 'https://www.wcpfc.int',
                'description' => 'Regional Fisheries Management Organization record of authorized fishing vessels.',
                'is_active' => true,
            ]
        );

        // 2. Source Records (Provenance)
        $unclosRecord = SourceRecord::firstOrCreate(
            ['record_identifier' => 'UNCLOS-IDN-DECLARATION-1985'],
            [
                'data_source_id' => $unDoalosSource->id,
                'record_date' => '1985-12-31',
                'retrieval_date' => now(),
                'verbatim_payload' => [
                    'title' => 'Indonesia Ratification of UNCLOS 1982',
                    'law_number' => 'Undang-Undang Nomor 17 Tahun 1985',
                    'signatory' => 'Republic of Indonesia',
                ],
                'sha256_hash' => hash('sha256', 'UNCLOS-IDN-DECLARATION-1985'),
                'verification_status' => VerificationStatus::VERIFIED,
                'analyst_notes' => 'Foundational legal instrument establishing Indonesian Archipelagic State status.',
            ]
        );

        $uu6Record = SourceRecord::firstOrCreate(
            ['record_identifier' => 'ID-UU-6-1996'],
            [
                'data_source_id' => $kkpSource->id,
                'record_date' => '1996-08-08',
                'verbatim_payload' => [
                    'title' => 'Undang-Undang Nomor 6 Tahun 1996 tentang Perairan Indonesia',
                    'scope' => 'Indonesian territorial sea, internal waters, and archipelagic baselines',
                ],
                'sha256_hash' => hash('sha256', 'ID-UU-6-1996'),
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // 3. Countries (Multi-Country Ready, Indonesia Initial)
        $indonesia = Country::firstOrCreate(
            ['iso_code_2' => 'ID'],
            [
                'iso_code_3' => 'IDN',
                'numeric_code' => '360',
                'name' => 'Indonesia',
                'official_name' => 'Republik Indonesia',
                'is_active' => true,
                'metadata' => [
                    'capital' => 'Jakarta',
                    'archipelagic_state' => true,
                    'unclos_party' => true,
                    'estimated_eez_sqkm' => 6159032,
                    'wppnri_zones' => [
                        'WPPNRI 711', 'WPPNRI 712', 'WPPNRI 713', 'WPPNRI 714',
                        'WPPNRI 715', 'WPPNRI 716', 'WPPNRI 717', 'WPPNRI 718',
                        'WPPNRI 571', 'WPPNRI 572', 'WPPNRI 573'
                    ],
                ],
            ]
        );

        $vietnam = Country::firstOrCreate(
            ['iso_code_2' => 'VN'],
            [
                'iso_code_3' => 'VNM',
                'numeric_code' => '704',
                'name' => 'Vietnam',
                'official_name' => 'Socialist Republic of Vietnam',
                'is_active' => true,
                'metadata' => ['capital' => 'Hanoi', 'unclos_party' => true],
            ]
        );

        $malaysia = Country::firstOrCreate(
            ['iso_code_2' => 'MY'],
            [
                'iso_code_3' => 'MYS',
                'numeric_code' => '458',
                'name' => 'Malaysia',
                'official_name' => 'Malaysia',
                'is_active' => true,
                'metadata' => ['capital' => 'Kuala Lumpur', 'unclos_party' => true],
            ]
        );

        $philippines = Country::firstOrCreate(
            ['iso_code_2' => 'PH'],
            [
                'iso_code_3' => 'PHL',
                'numeric_code' => '608',
                'name' => 'Philippines',
                'official_name' => 'Republic of the Philippines',
                'is_active' => true,
                'metadata' => ['capital' => 'Manila', 'archipelagic_state' => true],
            ]
        );

        // 4. Country Regulations
        CountryRegulation::firstOrCreate(
            ['country_id' => $indonesia->id, 'regulation_number' => 'UU 6/1996'],
            [
                'title' => 'Undang-Undang Nomor 6 Tahun 1996 tentang Perairan Indonesia',
                'issuing_authority' => 'Pemerintah Republik Indonesia',
                'effective_date' => '1996-08-08',
                'status' => 'active',
                'summary' => 'Menetapkan kedaulatan atas perairan kepulauan, laut teritorial, dan hak berdaulat di ZEE.',
                'source_record_id' => $uu6Record->id,
            ]
        );

        CountryRegulation::firstOrCreate(
            ['country_id' => $indonesia->id, 'regulation_number' => 'Permen KP 58/2020'],
            [
                'title' => 'Peraturan Menteri KP Nomor 58/PERMEN-KP/2020 tentang Usaha Perikanan Tangkap',
                'issuing_authority' => 'Kementerian Kelautan dan Perikanan',
                'effective_date' => '2020-11-20',
                'status' => 'active',
                'summary' => 'Ketentuan SIUP, SIPI, alokasi WPPNRI, dan pengawasan kapal perikanan nasional maupun asing.',
                'source_record_id' => $uu6Record->id,
            ]
        );

        // 5. Country Datasets
        CountryDataset::firstOrCreate(
            ['country_id' => $indonesia->id, 'dataset_name' => 'Indonesian National Fishing Vessel Registry'],
            [
                'dataset_type' => 'vessel_registry',
                'data_source_id' => $kkpSource->id,
                'version' => '2026.Q1',
                'last_updated_at' => now(),
                'records_count' => 5420,
                'status' => 'active',
            ]
        );

        // 6. Maritime Jurisdictions (PostGIS)
        // Indonesia Archipelagic Waters
        $archipelagicJurisdiction = MaritimeJurisdiction::firstOrCreate(
            ['country_id' => $indonesia->id, 'jurisdiction_type' => JurisdictionType::ARCHIPELAGIC_WATERS],
            [
                'area_name' => 'Indonesian Archipelagic Waters (Perairan Kepulauan)',
                'legal_basis' => 'UNCLOS 1982 Part IV & UU No. 6/1996',
                'legal_status' => LegalStatus::AGREED,
                'ratification_status' => 'Ratified by UNCLOS deposit',
                'effective_date' => '1996-08-08',
                'source_record_id' => $unclosRecord->id,
                'verification_status' => VerificationStatus::VERIFIED,
                'metadata' => ['sovereignty_level' => 'full_internal_sovereignty'],
            ]
        );
        // Set PostGIS MultiPolygon geometry covering Indonesian archipelago core
        DB::statement("
            UPDATE maritime_jurisdictions 
            SET geom = ST_Multi(ST_GeomFromText('POLYGON((
                95.0 -5.0,
                110.0 -7.0,
                118.0 -8.5,
                135.0 -8.0,
                141.0 -2.5,
                141.0 1.0,
                130.0 2.5,
                120.0 3.0,
                108.0 4.0,
                98.0 5.5,
                95.0 -5.0
            ))', 4326))
            WHERE id = ?
        ", [$archipelagicJurisdiction->id]);

        // Indonesia EEZ - North Natuna Sector
        $eezNatuna = MaritimeJurisdiction::firstOrCreate(
            ['country_id' => $indonesia->id, 'jurisdiction_type' => JurisdictionType::EEZ, 'area_name' => 'Indonesian EEZ - North Natuna Sea Sector'],
            [
                'legal_basis' => 'UU No. 5/1983 tentang ZEE Indonesia & Peta NKRI 2017',
                'legal_status' => LegalStatus::CLAIMED,
                'effective_date' => '2017-07-14',
                'source_record_id' => $uu6Record->id,
                'verification_status' => VerificationStatus::VERIFIED,
                'metadata' => ['wpp_zone' => 'WPPNRI 711', 'strategic_importance' => 'high'],
            ]
        );
        DB::statement("
            UPDATE maritime_jurisdictions 
            SET geom = ST_Multi(ST_GeomFromText('POLYGON((
                106.0 3.5,
                109.0 3.5,
                110.5 5.5,
                108.0 6.5,
                106.0 5.0,
                106.0 3.5
            ))', 4326))
            WHERE id = ?
        ", [$eezNatuna->id]);

        // Vietnam Unilateral EEZ Claim (Overlapping polygon with North Natuna Sector)
        $vnClaimJurisdiction = MaritimeJurisdiction::firstOrCreate(
            ['country_id' => $vietnam->id, 'jurisdiction_type' => JurisdictionType::DISPUTED_AREA, 'area_name' => 'Vietnam Claimed EEZ - Southern Shelf (Overlap Sector)'],
            [
                'legal_basis' => 'Vietnam 2012 Law of the Sea Unilateral Declaration',
                'legal_status' => LegalStatus::DISPUTED,
                'effective_date' => '2013-01-01',
                'verification_status' => VerificationStatus::DISPUTED,
                'metadata' => ['dispute_counterpart' => 'Indonesia'],
            ]
        );
        DB::statement("
            UPDATE maritime_jurisdictions 
            SET geom = ST_Multi(ST_GeomFromText('POLYGON((
                107.0 4.0,
                110.0 4.0,
                110.5 6.0,
                107.5 6.5,
                107.0 4.0
            ))', 4326))
            WHERE id = ?
        ", [$vnClaimJurisdiction->id]);

        // 7. Overlapping Claim Record
        $overlap = OverlappingClaim::firstOrCreate(
            [
                'jurisdiction_a_id' => $eezNatuna->id,
                'jurisdiction_b_id' => $vnClaimJurisdiction->id,
            ],
            [
                'claim_type' => 'overlapping_eez',
                'resolution_status' => 'bilateral_talks',
                'analyst_notes' => 'Indonesia and Vietnam completed technical EEZ delimitation talks in Dec 2022. Ratification process ongoing.',
            ]
        );
        // Set PostGIS intersection polygon
        DB::statement("
            UPDATE overlapping_claims
            SET overlap_geom = ST_Multi(ST_Intersection(
                (SELECT geom FROM maritime_jurisdictions WHERE id = ?),
                (SELECT geom FROM maritime_jurisdictions WHERE id = ?)
            ))
            WHERE id = ?
        ", [$eezNatuna->id, $vnClaimJurisdiction->id, $overlap->id]);

        // 8. Maritime Boundary Claim
        MaritimeBoundaryClaim::firstOrCreate(
            [
                'claimant_country_id' => $indonesia->id,
                'opposing_country_id' => $vietnam->id,
                'dispute_name' => 'North Natuna Sea / Southern Con Son Basin Delimitation',
            ],
            [
                'boundary_type' => 'EEZ Delimitation',
                'claim_description' => 'Overlapping maritime claims over sovereign rights to explore and exploit living marine resources in WPPNRI 711.',
                'legal_basis_claimant' => 'UNCLOS 1982 Art. 74 - Equidistance and continental shelf agreement 2003.',
                'legal_basis_opposing' => 'Vietnam historical fishing claims and baseline calculations.',
                'status' => 'negotiation',
            ]
        );
    }
}

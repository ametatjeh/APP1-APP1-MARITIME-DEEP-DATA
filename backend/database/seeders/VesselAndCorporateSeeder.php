<?php

namespace Database\Seeders;

use App\Enums\CompanyAssignmentType;
use App\Enums\ConflictNature;
use App\Enums\ResolutionStatus;
use App\Enums\VerificationStatus;
use App\Models\Company;
use App\Models\CompanyIdentifier;
use App\Models\CompanyOfficer;
use App\Models\CompanyShareholder;
use App\Models\CorporateRelationship;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\SourceRecord;
use App\Models\Vessel;
use App\Models\VesselAlias;
use App\Models\VesselCompanyAssignment;
use App\Models\VesselFlag;
use App\Models\VesselIdentifier;
use App\Models\VesselIdentityConflict;
use App\Models\VesselLicense;
use App\Models\VesselName;
use App\Models\VesselRegistration;
use Illuminate\Database\Seeder;

class VesselAndCorporateSeeder extends Seeder
{
    public function run(): void
    {
        $indonesia = Country::where('iso_code_2', 'ID')->first();
        $vietnam = Country::where('iso_code_2', 'VN')->first();
        $kkpSource = DataSource::where('code', 'ID_KKP')->first();

        // 1. Source Records for Vessels and Companies
        $vesselSourceRecord = SourceRecord::firstOrCreate(
            ['record_identifier' => 'KKP-VESSEL-REG-2025-9921'],
            [
                'data_source_id' => $kkpSource->id,
                'record_date' => '2025-06-15',
                'retrieval_date' => now(),
                'verbatim_payload' => [
                    'nama_kapal' => 'KM BAHARI PERKASA 01',
                    'tanda_selar' => 'GT.450 No. 8812/Bc',
                    'pemilik' => 'PT SAMUDERA BAHARI SENTOSA',
                    'pelabuhan_pangkalan' => 'Muara Baru, Jakarta',
                    'alat_tangkap' => 'Purse Seine Pelagis Besar',
                ],
                'sha256_hash' => hash('sha256', 'KKP-VESSEL-REG-2025-9921'),
                'verification_status' => VerificationStatus::VERIFIED,
                'analyst_notes' => 'Official ministry registration dossier with authenticated inspection report.',
            ]
        );

        $corpSourceRecord = SourceRecord::firstOrCreate(
            ['record_identifier' => 'AHU-KEMENKUMHAM-PT-9812-2022'],
            [
                'data_source_id' => $kkpSource->id,
                'record_date' => '2022-03-10',
                'retrieval_date' => now(),
                'verbatim_payload' => [
                    'company' => 'PT SAMUDERA BAHARI SENTOSA',
                    'nomor_ahu' => 'AHU-0019283.AH.01.01.TAHUN 2022',
                    'direktur_utama' => 'Bambang Kusuma',
                    'pemegang_saham_mayoritas' => 'Apex Ocean Pacific Holdings Ltd (85%)',
                ],
                'sha256_hash' => hash('sha256', 'AHU-KEMENKUMHAM-PT-9812-2022'),
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // 2. Corporate Entities
        // Operating Company in Indonesia
        $ptSamudera = Company::firstOrCreate(
            ['company_name' => 'PT Samudera Bahari Sentosa'],
            [
                'registration_number' => '02.481.938.1-012.000',
                'country_of_incorporation_id' => $indonesia->id,
                'incorporation_date' => '2015-04-12',
                'legal_form' => 'Perseroan Terbatas (PT)',
                'corporate_status' => 'active',
                'registered_address' => 'Jl. Muara Baru Raya No. 45, Penjaringan, Jakarta Utara',
                'is_sanctioned' => false,
                'verification_status' => VerificationStatus::VERIFIED,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        // Offshore Parent Company (Holding / Shell)
        $apexHoldings = Company::firstOrCreate(
            ['company_name' => 'Apex Ocean Pacific Holdings Ltd'],
            [
                'registration_number' => 'MH-CORP-91823',
                'incorporation_date' => '2014-08-20',
                'legal_form' => 'International Business Company (IBC)',
                'corporate_status' => 'active',
                'registered_address' => 'Trust Company Complex, Ajeltake Road, Majuro, Marshall Islands',
                'is_sanctioned' => false,
                'verification_status' => VerificationStatus::VERIFIED,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        // Company Identifiers
        CompanyIdentifier::firstOrCreate(
            ['company_id' => $ptSamudera->id, 'identifier_type' => 'national_tax_id'],
            [
                'identifier_value' => '024819381012000',
                'issuing_country_id' => $indonesia->id,
                'is_current' => true,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        // Company Officers (with sensitive/redacted PII demonstrated)
        CompanyOfficer::firstOrCreate(
            ['company_id' => $ptSamudera->id, 'officer_name' => 'Bambang Kusuma'],
            [
                'position' => 'President Director (Direktur Utama)',
                'appointment_date' => '2020-01-15',
                'is_current' => true,
                'is_redacted' => false,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        CompanyOfficer::firstOrCreate(
            ['company_id' => $ptSamudera->id, 'officer_name' => 'Wong Kar Wai [Beneficial Controller]'],
            [
                'position' => 'Shadow Director / Authorized Signatory',
                'appointment_date' => '2021-06-01',
                'is_current' => true,
                'is_redacted' => true, // Redacted for non-privileged viewers
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        // Company Shareholders
        CompanyShareholder::firstOrCreate(
            ['company_id' => $ptSamudera->id, 'shareholder_name' => 'Apex Ocean Pacific Holdings Ltd'],
            [
                'shareholder_type' => 'corporate',
                'share_percentage' => 85.00,
                'shares_count' => 85000,
                'valid_from' => '2015-04-12',
                'is_current' => true,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        CompanyShareholder::firstOrCreate(
            ['company_id' => $ptSamudera->id, 'shareholder_name' => 'Bambang Kusuma'],
            [
                'shareholder_type' => 'individual',
                'share_percentage' => 15.00,
                'shares_count' => 15000,
                'valid_from' => '2015-04-12',
                'is_current' => true,
                'source_record_id' => $corpSourceRecord->id,
            ]
        );

        // Corporate Relationship (Parent-Subsidiary with beneficial ownership)
        CorporateRelationship::firstOrCreate(
            [
                'parent_company_id' => $apexHoldings->id,
                'child_company_id' => $ptSamudera->id,
            ],
            [
                'relationship_type' => 'beneficial_owner',
                'ownership_percentage' => 85.00,
                'valid_from' => '2015-04-12',
                'is_current' => true,
                'source_record_id' => $corpSourceRecord->id,
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // 3. Vessel Master
        $vesselA = Vessel::firstOrCreate(
            ['current_name' => 'KM BAHARI PERKASA 01'],
            [
                'current_flag_country_id' => $indonesia->id,
                'imo_number' => '8910245',
                'current_mmsi' => '525019821',
                'current_callsign' => 'YDA8821',
                'national_registration_number' => 'GT.450 No. 8812/Bc',
                'vessel_type' => 'purse_seiner',
                'fishing_gear_type' => 'Purse Seine Pelagis Besar',
                'gross_tonnage' => 452.00,
                'length_overall' => 48.50,
                'breadth' => 9.20,
                'draught' => 4.10,
                'engine_power_kw' => 1200.00,
                'built_year' => 2012,
                'shipyard' => 'Niigata Shipyard Co.',
                'port_of_registry' => 'Jakarta (Muara Baru)',
                'verification_status' => VerificationStatus::VERIFIED,
                'structural_specs' => [
                    'freezer_capacity_tons' => 300,
                    'refrigerant_type' => 'Ammonia (NH3)',
                    'hull_material' => 'Steel',
                ],
                'notes' => 'High seas purse seiner authorized to operate in WPPNRI 716 and WCPFC convention area.',
            ]
        );

        // Identifiers
        VesselIdentifier::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'identifier_type' => 'imo', 'identifier_value' => '8910245'],
            ['valid_from' => '2012-01-01', 'is_current' => true, 'verification_status' => VerificationStatus::VERIFIED]
        );
        VesselIdentifier::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'identifier_type' => 'mmsi', 'identifier_value' => '525019821'],
            ['valid_from' => '2018-05-10', 'is_current' => true, 'verification_status' => VerificationStatus::VERIFIED]
        );

        // Historical Name
        VesselName::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'name' => 'OCEAN HARVESTER NO. 3'],
            [
                'valid_from' => '2012-01-01',
                'valid_to' => '2018-05-09',
                'is_current' => false,
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );
        VesselName::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'name' => 'KM BAHARI PERKASA 01'],
            [
                'valid_from' => '2018-05-10',
                'is_current' => true,
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // Historical Flags
        VesselFlag::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'country_id' => $indonesia->id],
            [
                'valid_from' => '2018-05-10',
                'is_current' => true,
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // Vessel Licenses
        VesselLicense::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'license_number' => 'SIPI.02.26.001928.9812'],
            [
                'issuing_country_id' => $indonesia->id,
                'license_type' => 'Surat Izin Penangkapan Ikan (SIPI)',
                'authorized_gear' => 'Purse Seine Pelagis Besar',
                'target_species' => 'Tuna, Cakalang, Tongkol (Skipjack, Yellowfin)',
                'authorized_zones' => ['WPPNRI 716', 'WPPNRI 717'],
                'valid_from' => '2025-01-01',
                'valid_to' => '2026-12-31',
                'status' => 'valid',
                'verification_status' => VerificationStatus::VERIFIED,
            ]
        );

        // Vessel Company Assignment (Ownership Link)
        VesselCompanyAssignment::firstOrCreate(
            [
                'vessel_id' => $vesselA->id,
                'company_id' => $ptSamudera->id,
                'assignment_type' => CompanyAssignmentType::REGISTERED_OWNER,
            ],
            [
                'valid_from' => '2018-05-10',
                'is_current' => true,
                'source_record_id' => $vesselSourceRecord->id,
                'verification_status' => VerificationStatus::VERIFIED,
                'notes' => 'Purchased and re-flagged to Indonesia in May 2018.',
            ]
        );

        VesselCompanyAssignment::firstOrCreate(
            [
                'vessel_id' => $vesselA->id,
                'company_id' => $apexHoldings->id,
                'assignment_type' => CompanyAssignmentType::BENEFICIAL_OWNER,
            ],
            [
                'valid_from' => '2018-05-10',
                'is_current' => true,
                'source_record_id' => $corpSourceRecord->id,
                'verification_status' => VerificationStatus::VERIFIED,
                'notes' => 'Ultimate beneficial ownership identified through 85% majority holding.',
            ]
        );

        // 4. Identity Conflict Demonstration Scenario
        // Vessel B: Foreign vessel sharing the same MMSI due to flag hopping / uncancelled MMSI
        $vesselB = Vessel::firstOrCreate(
            ['current_name' => 'THANH DAT 68'],
            [
                'current_flag_country_id' => $vietnam->id,
                'imo_number' => null, // Small coastal pair trawler without IMO
                'current_mmsi' => '525019821', // CONFLICT: Same MMSI as KM BAHARI PERKASA 01
                'current_callsign' => 'XV8192',
                'national_registration_number' => 'KG-94812-TS',
                'vessel_type' => 'trawler',
                'fishing_gear_type' => 'Pair Trawl',
                'gross_tonnage' => 88.00,
                'length_overall' => 24.00,
                'verification_status' => VerificationStatus::DISPUTED,
                'notes' => 'Detected using Indonesian MMSI 525019821 while broadcasting Vietnamese VHF callsign.',
            ]
        );

        // Identity Conflict Record (Analyst Review Workflow)
        VesselIdentityConflict::firstOrCreate(
            [
                'vessel_a_id' => $vesselA->id,
                'vessel_b_id' => $vesselB->id,
                'conflicting_identifier_type' => 'mmsi',
                'conflicting_identifier_value' => '525019821',
            ],
            [
                'conflict_nature' => ConflictNature::SHARED_MMSI,
                'conflict_score' => 88.50,
                'resolution_status' => ResolutionStatus::UNDER_REVIEW,
                'analyst_conclusion' => 'Vessel A is a 452 GT Indonesian industrial purse seiner. Vessel B is a 24m wooden pair trawler. Clear case of MMSI spoofing or unauthorized reprogramming. Must remain separate entities.',
            ]
        );

        // Vessel Alias
        VesselAlias::firstOrCreate(
            ['vessel_id' => $vesselA->id, 'alias_name' => 'BAHARI PERKASA 1'],
            ['alias_type' => 'spelling_variant', 'confidence_score' => 0.95]
        );
    }
}

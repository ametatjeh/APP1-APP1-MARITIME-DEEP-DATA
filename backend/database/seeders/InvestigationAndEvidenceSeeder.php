<?php

namespace Database\Seeders;

use App\Enums\CaseCategory;
use App\Enums\CaseConfidentiality;
use App\Enums\CasePriority;
use App\Enums\CaseRole;
use App\Enums\CaseStatus;
use App\Enums\CustodyAction;
use App\Enums\EvidenceClassification;
use App\Enums\EvidenceType;
use App\Enums\EvidenceVerificationStatus;
use App\Enums\SourceCategory;
use App\Enums\SubjectRole;
use App\Models\CaseAssignment;
use App\Models\CaseNote;
use App\Models\CaseSubject;
use App\Models\CaseTimeline;
use App\Models\ChainOfCustody;
use App\Models\Company;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\Evidence;
use App\Models\EvidenceRelationship;
use App\Models\InvestigationCase;
use App\Models\SourceRecord;
use App\Models\User;
use App\Models\VerificationReview;
use App\Models\Vessel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvestigationAndEvidenceSeeder extends Seeder
{
    public function run(): void
    {
        $seniorInv = User::where('email', 'senior.inv@maritimedeepdata.org')->first();
        $analyst = User::where('email', 'analyst@maritimedeepdata.org')->first();
        $director = User::where('email', 'director@maritimedeepdata.org')->first();
        $indonesia = Country::where('iso_code_2', 'ID')->first();

        // 1. Enhanced Data Sources for Phase 2
        $interpolSource = DataSource::firstOrCreate(
            ['code' => 'INTERPOL_ENV'],
            [
                'name' => 'INTERPOL Environmental Security Sub-Directorate',
                'category' => SourceCategory::INTERNATIONAL_ORG,
                'organization_type' => 'international_law_enforcement',
                'publisher' => 'INTERPOL General Secretariat',
                'reliability_score' => 0.98,
                'source_url' => 'https://www.interpol.int/Crimes/Environmental-crime',
                'description' => 'Purple Notices and investigative case files on transnational fisheries crime syndicates.',
                'country_id' => null,
                'is_active' => true,
                'verification_status' => 'verified',
            ]
        );

        $sourceRecord = SourceRecord::firstOrCreate(
            ['record_identifier' => 'INTERPOL-PURPLE-NOTICE-2025-0812'],
            [
                'data_source_id' => $interpolSource->id,
                'record_date' => '2025-09-14',
                'retrieval_date' => now(),
                'verbatim_payload' => [
                    'notice_type' => 'Purple Notice (Modus Operandi)',
                    'subject' => 'Dual identity broadcast and MMSI hopping in WPPNRI 711',
                    'flags_identified' => ['Indonesia', 'Vietnam', 'Panama'],
                ],
                'sha256_hash' => hash('sha256', 'INTERPOL-PURPLE-NOTICE-2025-0812'),
                'verification_status' => 'verified',
            ]
        );

        // 2. Investigation Case 1: Operation Natuna Ghost Fleet
        $case = InvestigationCase::firstOrCreate(
            ['case_number' => 'MDD-INV-2026-0001'],
            [
                'title' => 'Operation Natuna Ghost Fleet: Industrial Purse Seine Syndicate',
                'summary' => 'Investigation into suspected coordinated identity hopping, unauthoried transshipment, and beneficial ownership concealment involving vessels operating in the North Natuna Sea disputed boundary zone (WPPNRI 711).',
                'category' => CaseCategory::FLAG_HOPPING_IDENTITY_FRAUD,
                'priority' => CasePriority::CRITICAL,
                'status' => CaseStatus::ACTIVE,
                'confidentiality_level' => CaseConfidentiality::RESTRICTED,
                'lead_investigator_id' => $seniorInv->id,
                'incident_date_from' => '2025-05-01',
                'incident_date_to' => '2026-02-15',
                'findings_summary' => 'Preliminary evidence indicates KM BAHARI PERKASA 01 shares radio transmission signatures with foreign wooden trawlers, while ultimate profits trace to an offshore holding company in the Marshall Islands.',
                'tags' => ['IUU', 'North Natuna', 'Shared MMSI', 'Shell Company', 'UBO'],
                'metadata' => [
                    'operation_codename' => 'GHOST_FLEET_NATUNA',
                    'reporting_target' => 'Task Force 115 & KKP Surveillance',
                ],
            ]
        );

        // Assignments
        CaseAssignment::firstOrCreate(
            ['case_id' => $case->id, 'user_id' => $seniorInv->id, 'role_in_case' => CaseRole::LEAD],
            ['assigned_at' => now(), 'is_active' => true]
        );
        CaseAssignment::firstOrCreate(
            ['case_id' => $case->id, 'user_id' => $analyst->id, 'role_in_case' => CaseRole::ANALYST],
            ['assigned_at' => now(), 'is_active' => true, 'assigned_by_user_id' => $seniorInv->id]
        );

        // Multi-Subject Linkages
        $vesselA = Vessel::where('current_name', 'KM BAHARI PERKASA 01')->first();
        $vesselB = Vessel::where('current_name', 'THANH DAT 68')->first();
        $ptSamudera = Company::where('company_name', 'PT Samudera Bahari Sentosa')->first();
        $apexHoldings = Company::where('company_name', 'Apex Ocean Pacific Holdings Ltd')->first();

        if ($vesselA) {
            CaseSubject::firstOrCreate(
                ['case_id' => $case->id, 'subject_type' => 'vessel', 'subject_id' => $vesselA->id],
                [
                    'subject_name' => $vesselA->current_name,
                    'subject_role' => SubjectRole::PRIMARY_TARGET,
                    'involvement_summary' => '452 GT industrial purse seiner registered in Muara Baru, broadcasting conflicting identifiers.',
                    'date_identified' => '2025-05-10',
                    'is_active' => true,
                ]
            );
        }

        if ($vesselB) {
            CaseSubject::firstOrCreate(
                ['case_id' => $case->id, 'subject_type' => 'vessel', 'subject_id' => $vesselB->id],
                [
                    'subject_name' => $vesselB->current_name,
                    'subject_role' => SubjectRole::ASSOCIATED_VESSEL,
                    'involvement_summary' => 'Foreign wooden pair trawler broadcasting duplicate MMSI 525019821.',
                    'date_identified' => '2025-06-02',
                    'is_active' => true,
                ]
            );
        }

        if ($ptSamudera) {
            CaseSubject::firstOrCreate(
                ['case_id' => $case->id, 'subject_type' => 'company', 'subject_id' => $ptSamudera->id],
                [
                    'subject_name' => $ptSamudera->company_name,
                    'subject_role' => SubjectRole::OPERATOR,
                    'involvement_summary' => 'Registered owner and onshore landing management entity in Jakarta.',
                    'date_identified' => '2025-05-12',
                    'is_active' => true,
                ]
            );
        }

        if ($apexHoldings) {
            CaseSubject::firstOrCreate(
                ['case_id' => $case->id, 'subject_type' => 'company', 'subject_id' => $apexHoldings->id],
                [
                    'subject_name' => $apexHoldings->company_name,
                    'subject_role' => SubjectRole::BENEFICIAL_OWNER,
                    'involvement_summary' => 'Offshore holding company owning 85% majority shares, concealing ultimate beneficial control.',
                    'date_identified' => '2025-07-20',
                    'is_active' => true,
                ]
            );
        }

        // Location subject
        CaseSubject::firstOrCreate(
            ['case_id' => $case->id, 'subject_type' => 'location', 'subject_name' => 'WPPNRI 711 / North Natuna Continental Shelf Overlap'],
            [
                'subject_role' => SubjectRole::LOCATION,
                'involvement_summary' => 'Primary high seas fishing ground where illegal transshipment encounters occurred.',
                'date_identified' => '2025-05-01',
                'is_active' => true,
                'metadata' => ['coordinates' => ['lat' => 4.5, 'lon' => 108.0]],
            ]
        );

        // Case Notes
        CaseNote::firstOrCreate(
            ['case_id' => $case->id, 'title' => 'Initial Hypothesis: Systematic MMSI Cloning Ring'],
            [
                'user_id' => $seniorInv->id,
                'note_text' => 'We suspect KM BAHARI PERKASA 01 is part of a corporate fleet scheme where an Indonesian permit is used to mask operations of foreign-crewed vessels in the overlapping Natuna boundary.',
                'note_type' => 'hypothesis',
                'is_confidential' => true,
            ]
        );

        // Case Timeline
        CaseTimeline::firstOrCreate(
            ['case_id' => $case->id, 'event_title' => 'First Conflict Flagged by Surveillance Station'],
            [
                'event_date' => '2025-05-08 04:30:00+00',
                'event_description' => 'Station at Ranai, Natuna flagged simultaneous transmission of MMSI 525019821 by two distinct craft 140 NM apart.',
                'event_type' => 'detection',
                'source_record_id' => $sourceRecord->id,
            ]
        );

        // 3. Evidence File Creation in Private Storage
        $evidenceContent = "=== MARITIME DEEP DATA INVESTIGATION EVIDENCE ===\n" .
            "Case Reference: MDD-INV-2026-0001\n" .
            "Document: Official Inspection & Ownership Extract\n" .
            "Subject: KM BAHARI PERKASA 01 (IMO 8910245)\n" .
            "Authenticity: Sealed and verified by Senior Investigator Budi Santoso\n" .
            "Timestamp: 2026-02-19T10:00:00Z\n";

        $evidenceSha256 = hash('sha256', $evidenceContent);
        $fileName = "INSPECTION_DOSSIER_MDD_2026_0001.pdf";
        $storagePath = "evidence/2026/02/" . Str::uuid() . ".pdf";

        Storage::disk('private')->put($storagePath, $evidenceContent);

        $evidence = Evidence::firstOrCreate(
            ['sha256_hash' => $evidenceSha256],
            [
                'case_id' => $case->id,
                'title' => 'Muara Baru Port Inspection Report & Shareholding Dossier',
                'evidence_type' => EvidenceType::REGISTRY_EXTRACT,
                'classification' => EvidenceClassification::CONFIDENTIAL,
                'file_path' => $storagePath,
                'file_name' => $fileName,
                'file_extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => strlen($evidenceContent),
                'data_source_id' => $interpolSource->id,
                'source_record_id' => $sourceRecord->id,
                'source_url' => 'https://interpol.int/restricted/dossier/2025-0812',
                'collection_date' => '2025-10-15',
                'verification_status' => EvidenceVerificationStatus::VERIFIED,
                'collected_by_user_id' => $seniorInv->id,
                'metadata' => [
                    'sealing_officer' => 'Budi Santoso',
                    'original_classification' => 'Law Enforcement Sensitive',
                ],
            ]
        );

        // Chain of Custody
        ChainOfCustody::firstOrCreate(
            ['evidence_id' => $evidence->id, 'action' => CustodyAction::UPLOADED],
            [
                'actor_id' => $seniorInv->id,
                'actor_name' => $seniorInv->name,
                'actor_role' => $seniorInv->role->value,
                'ip_address' => '127.0.0.1',
                'hash_at_action' => $evidenceSha256,
                'notes' => 'Evidence document ingested into private evidence vault.',
                'created_at' => now()->subDays(10),
            ]
        );

        ChainOfCustody::firstOrCreate(
            ['evidence_id' => $evidence->id, 'action' => CustodyAction::HASH_GENERATED],
            [
                'actor_id' => $seniorInv->id,
                'actor_name' => $seniorInv->name,
                'actor_role' => $seniorInv->role->value,
                'ip_address' => '127.0.0.1',
                'hash_at_action' => $evidenceSha256,
                'notes' => "SHA-256 integrity hash generated: {$evidenceSha256}",
                'created_at' => now()->subDays(10),
            ]
        );

        ChainOfCustody::firstOrCreate(
            ['evidence_id' => $evidence->id, 'action' => CustodyAction::VERIFIED],
            [
                'actor_id' => $director->id,
                'actor_name' => $director->name,
                'actor_role' => $director->role->value,
                'ip_address' => '127.0.0.1',
                'hash_at_action' => $evidenceSha256,
                'notes' => 'Verified against corporate registry extract and verified valid.',
                'created_at' => now()->subDays(5),
            ]
        );

        // Evidence Relationships
        if ($vesselA) {
            EvidenceRelationship::firstOrCreate(
                ['evidence_id' => $evidence->id, 'target_type' => 'vessel', 'target_id' => $vesselA->id],
                ['relationship_type' => 'proves_ownership', 'notes' => 'Documents physical ownership and structural alterations.']
            );
        }

        // Formal Verification Review
        VerificationReview::firstOrCreate(
            ['verifiable_type' => Evidence::class, 'verifiable_id' => $evidence->id],
            [
                'reviewer_id' => $director->id,
                'previous_status' => 'pending_verification',
                'new_status' => EvidenceVerificationStatus::VERIFIED,
                'decision' => 'Authenticated & Accepted into Case Evidence',
                'written_conclusion' => 'Document cross-referenced with Ministry of Law and Human Rights AHU database and certified authentic.',
                'reviewed_at' => now()->subDays(5),
            ]
        );
    }
}

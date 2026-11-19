<?php

namespace App\Services;

use App\Enums\ReportType;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\InvestigationCase;
use App\Models\ReportCitation;
use App\Models\ResearchReport;
use App\Models\User;
use App\Models\Vessel;

class DossierReportService
{
    /**
     * Compiles an evidence-linked investigative vessel dossier
     */
    public function generateVesselDossier(Vessel $vessel, ?User $user = null, array $options = []): ResearchReport
    {
        $vessel->load([
            'currentFlagCountry',
            'names.sourceRecord',
            'flags.country',
            'identifiers.sourceRecord',
            'registrations.country',
            'licenses.issuingCountry',
            'companyAssignments.company.countryOfIncorporation',
            'personTies.person',
            'crewEmployments.person',
            'labourViolations',
            'iuuListings',
            'iuuInfractions',
            'conflictsAsA.vesselB',
            'conflictsAsB.vesselA',
        ]);

        $year = date('Y');
        $count = ResearchReport::whereYear('created_at', $year)->count() + 1;
        $ref = sprintf('REP-%s-%04d', $year, $count);

        $title = "Investigative Intelligence Dossier: {$vessel->current_name} (IMO: " . ($vessel->imo_number ?? 'UNRECORDED') . ")";
        $classification = $options['classification'] ?? 'restricted';

        // Structured JSON Snapshot
        $structuredPayload = [
            'vessel_id' => $vessel->id,
            'current_name' => $vessel->current_name,
            'imo_number' => $vessel->imo_number,
            'current_mmsi' => $vessel->current_mmsi,
            'current_callsign' => $vessel->current_callsign,
            'flag' => $vessel->currentFlagCountry?->name,
            'vessel_type' => $vessel->vessel_type,
            'tonnage' => $vessel->gross_tonnage,
            'length' => $vessel->length_overall,
            'verification_status' => $vessel->verification_status?->value ?? $vessel->verification_status,
            'historical_names' => $vessel->names->map(fn($n) => [
                'name' => $n->name,
                'valid_from' => $n->valid_from?->toDateString(),
                'valid_to' => $n->valid_to?->toDateString(),
                'is_current' => $n->is_current,
            ])->toArray(),
            'historical_flags' => $vessel->flags->map(fn($f) => [
                'country' => $f->country?->name,
                'valid_from' => $f->valid_from?->toDateString(),
                'is_current' => $f->is_current,
            ])->toArray(),
            'corporate_network' => $vessel->companyAssignments->map(fn($c) => [
                'company' => $c->company?->company_name,
                'role' => $c->assignment_type,
                'country' => $c->company?->countryOfIncorporation?->name,
            ])->toArray(),
            'personnel' => $vessel->personTies->map(fn($p) => [
                'person' => $p->person?->masked_name ?? $p->person?->full_name,
                'role' => $p->role,
            ])->toArray(),
            'iuu_sanctions' => $vessel->iuuListings->map(fn($i) => [
                'organization' => $i->listing_organization ?? $i->list_name,
                'listing_date' => $i->listing_date?->toDateString(),
                'reason' => $i->listing_reason,
            ])->toArray(),
            'forced_labour_indicators' => $vessel->labourViolations->map(fn($l) => [
                'indicator' => $l->ilo_indicator,
                'incident_date' => $l->incident_date?->toDateString(),
                'severity' => $l->severity,
            ])->toArray(),
            'dossier_compiled_at' => now()->toIso8601String(),
        ];

        $sha256 = hash('sha256', json_encode($structuredPayload));

        $executiveSummary = "Comprehensive intelligence dossier for fishing vessel {$vessel->current_name}. " .
            "Flagged under {$vessel->currentFlagCountry?->name}. " .
            "Recorded {$vessel->names->count()} historical names, {$vessel->companyAssignments->count()} corporate ties, " .
            "{$vessel->iuuListings->count()} multilateral IUU sanctions, and {$vessel->labourViolations->count()} forced labour indicators.";

        $bodyMarkdown = $this->buildVesselMarkdown($vessel, $structuredPayload);

        $report = ResearchReport::create([
            'report_reference' => $ref,
            'title' => $title,
            'report_type' => ReportType::VESSEL_DOSSIER,
            'classification' => $classification,
            'target_entity_type' => 'vessel',
            'target_entity_id' => $vessel->id,
            'executive_summary' => $executiveSummary,
            'body_markdown' => $bodyMarkdown,
            'structured_payload' => $structuredPayload,
            'sha256_checksum' => $sha256,
            'created_by' => $user?->id,
            'published_at' => now(),
        ]);

        // Create Citations linking evidence & source records
        $citIndex = 1;
        foreach ($vessel->names as $nameRec) {
            if ($nameRec->source_record_id) {
                ReportCitation::create([
                    'research_report_id' => $report->id,
                    'source_record_id' => $nameRec->source_record_id,
                    'citation_key' => "[CIT-{$citIndex}]",
                    'statement_supported' => "Historical name claim '{$nameRec->name}' documented in source record.",
                ]);
                $citIndex++;
            }
        }

        AuditLog::log('generate_vessel_dossier', $report, null, [
            'report_reference' => $ref,
            'vessel_name' => $vessel->current_name,
            'sha256' => $sha256,
        ]);

        return $report;
    }

    /**
     * Compiles an evidence-linked corporate investigation report
     */
    public function generateCorporateDossier(Company $company, ?User $user = null, array $options = []): ResearchReport
    {
        $company->load([
            'countryOfIncorporation',
            'vesselAssignments.vessel',
            'officers.person',
            'shareholders.person',
            'parentRelationships.parentCompany',
            'subsidiaryRelationships.subsidiaryCompany',
        ]);

        $year = date('Y');
        $count = ResearchReport::whereYear('created_at', $year)->count() + 1;
        $ref = sprintf('REP-%s-%04d', $year, $count);

        $title = "Corporate Intelligence Dossier: {$company->company_name}";
        $classification = $options['classification'] ?? 'restricted';

        $structuredPayload = [
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'jurisdiction' => $company->countryOfIncorporation?->name,
            'registration_number' => $company->registration_number,
            'incorporation_date' => $company->incorporation_date?->toDateString(),
            'directors_officers' => $company->officers->map(fn($o) => [
                'name' => $o->person?->masked_name ?? $o->person?->full_name ?? $o->title,
                'title' => $o->title,
            ])->toArray(),
            'beneficial_owners' => $company->shareholders->map(fn($s) => [
                'shareholder' => $s->person?->masked_name ?? $s->person?->full_name,
                'percentage' => $s->percentage,
            ])->toArray(),
            'fleet_managed' => $company->vesselAssignments->map(fn($v) => [
                'vessel' => $v->vessel?->current_name,
                'imo' => $v->vessel?->imo_number,
                'role' => $v->assignment_type,
            ])->toArray(),
            'dossier_compiled_at' => now()->toIso8601String(),
        ];

        $sha256 = hash('sha256', json_encode($structuredPayload));

        $executiveSummary = "Corporate beneficial ownership analysis for {$company->company_name}. Incorporated in {$company->countryOfIncorporation?->name}. Controls a recorded fleet of {$company->vesselAssignments->count()} fishing vessels.";

        $report = ResearchReport::create([
            'report_reference' => $ref,
            'title' => $title,
            'report_type' => ReportType::CORPORATE_NETWORK_INVESTIGATION,
            'classification' => $classification,
            'target_entity_type' => 'company',
            'target_entity_id' => $company->id,
            'executive_summary' => $executiveSummary,
            'body_markdown' => "# {$title}\n\n## Executive Summary\n{$executiveSummary}\n\n## Fleet Control\n" . json_encode($structuredPayload['fleet_managed'], JSON_PRETTY_PRINT),
            'structured_payload' => $structuredPayload,
            'sha256_checksum' => $sha256,
            'created_by' => $user?->id,
            'published_at' => now(),
        ]);

        return $report;
    }

    /**
     * Builds Markdown dossier body
     */
    protected function buildVesselMarkdown(Vessel $vessel, array $data): string
    {
        $md = "# {$data['current_name']}\n\n";
        $md .= "**Report Reference**: Compiled via Maritime Deep Data Intelligence Deck\n";
        $md .= "**Integrity Hash (SHA-256)**: `{$data['vessel_id']}`\n\n";

        $md .= "## 1. Primary Vessel Identifiers\n";
        $md .= "- **Current Name**: {$data['current_name']}\n";
        $md .= "- **IMO Number**: " . ($data['imo_number'] ?? 'N/A') . "\n";
        $md .= "- **MMSI**: " . ($data['current_mmsi'] ?? 'N/A') . "\n";
        $md .= "- **Callsign**: " . ($data['current_callsign'] ?? 'N/A') . "\n";
        $md .= "- **Flag State**: " . ($data['flag'] ?? 'Unflagged / Unknown') . "\n";
        $md .= "- **Gross Tonnage**: " . ($data['tonnage'] ?? 'N/A') . " GT\n";
        $md .= "- **Length Overall (LOA)**: " . ($data['length'] ?? 'N/A') . " m\n\n";

        $md .= "## 2. Multilateral Sanctions & RFMO IUU Listings\n";
        if (count($data['iuu_sanctions']) === 0) {
            $md .= "_No official multilateral IUU listing found on record._\n\n";
        } else {
            foreach ($data['iuu_sanctions'] as $i) {
                $md .= "- **{$i['organization']}** (Listed: {$i['listing_date']}): {$i['reason']}\n";
            }
            $md .= "\n";
        }

        $md .= "## 3. Human Rights & Forced Labour Violations (ILO 11)\n";
        if (count($data['forced_labour_indicators']) === 0) {
            $md .= "_No forced labour indicators logged for this vessel._\n\n";
        } else {
            foreach ($data['forced_labour_indicators'] as $l) {
                $md .= "- **ILO Indicator**: `{$l['indicator']}` (Severity: {$l['severity']}, Date: {$l['incident_date']})\n";
            }
            $md .= "\n";
        }

        $md .= "## 4. Corporate Ownership & Beneficial Control\n";
        if (count($data['corporate_network']) === 0) {
            $md .= "_No registered corporate owner or managing entity recorded._\n\n";
        } else {
            foreach ($data['corporate_network'] as $c) {
                $md .= "- **{$c['company']}** ({$c['country']}) - Role: `{$c['role']}`\n";
            }
            $md .= "\n";
        }

        return $md;
    }
}

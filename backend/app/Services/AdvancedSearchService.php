<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Evidence;
use App\Models\InvestigationCase;
use App\Models\IuuVesselListing;
use App\Models\Person;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;

class AdvancedSearchService
{
    /**
     * Executes unified cross-domain intelligence search
     */
    public function search(array $params): array
    {
        $q = trim($params['q'] ?? '');
        $types = $params['types'] ?? ['vessel', 'company', 'person', 'case', 'evidence', 'iuu'];
        $limit = min((int)($params['limit'] ?? 20), 100);

        $results = [];

        if (empty($q)) {
            return [
                'query' => '',
                'total_hits' => 0,
                'results' => [],
            ];
        }

        // 1. Search Vessels
        if (in_array('vessel', $types)) {
            $vesselQuery = Vessel::with('currentFlagCountry')
                ->where('current_name', 'ilike', "%{$q}%")
                ->orWhere('imo_number', 'ilike', "%{$q}%")
                ->orWhere('current_mmsi', 'ilike', "%{$q}%")
                ->orWhere('current_callsign', 'ilike', "%{$q}%")
                ->orWhere('national_registration_number', 'ilike', "%{$q}%");

            if (!empty($params['country_id'])) {
                $vesselQuery->where('current_flag_country_id', $params['country_id']);
            }

            $vessels = $vesselQuery->limit($limit)->get();
            foreach ($vessels as $v) {
                $results[] = [
                    'entity_type' => 'vessel',
                    'id' => $v->id,
                    'title' => $v->current_name,
                    'subtitle' => "IMO: " . ($v->imo_number ?? 'N/A') . " | MMSI: " . ($v->current_mmsi ?? 'N/A'),
                    'flag' => $v->currentFlagCountry?->name,
                    'status' => $v->verification_status?->value ?? $v->verification_status,
                    'url' => "/vessels?highlight={$v->id}",
                    'meta' => [
                        'imo' => $v->imo_number,
                        'mmsi' => $v->current_mmsi,
                        'callsign' => $v->current_callsign,
                        'tonnage' => $v->gross_tonnage,
                    ],
                ];
            }
        }

        // 2. Search Companies
        if (in_array('company', $types)) {
            $companyQuery = Company::with('countryOfIncorporation')
                ->where('company_name', 'ilike', "%{$q}%")
                ->orWhere('registration_number', 'ilike', "%{$q}%")
                ->orWhereHas('identifiers', fn($i) => $i->where('identifier_value', 'ilike', "%{$q}%"));

            $companies = $companyQuery->limit($limit)->get();
            foreach ($companies as $c) {
                $results[] = [
                    'entity_type' => 'company',
                    'id' => $c->id,
                    'title' => $c->company_name,
                    'subtitle' => "Reg: " . ($c->registration_number ?? 'N/A') . " | Juris: " . ($c->countryOfIncorporation?->name ?? 'Global'),
                    'flag' => $c->countryOfIncorporation?->name,
                    'status' => $c->verification_status?->value ?? $c->verification_status,
                    'url' => "/corporate?highlight={$c->id}",
                    'meta' => [
                        'registration' => $c->registration_number,
                        'incorporation' => $c->incorporation_date?->toDateString(),
                    ],
                ];
            }
        }

        // 3. Search Persons
        if (in_array('person', $types)) {
            $personQuery = Person::with('countryOfCitizenship')
                ->where('full_name', 'ilike', "%{$q}%")
                ->orWhereHas('aliases', fn($a) => $a->where('alias_name', 'ilike', "%{$q}%"));

            $persons = $personQuery->limit($limit)->get();
            foreach ($persons as $p) {
                $results[] = [
                    'entity_type' => 'person',
                    'id' => $p->id,
                    'title' => $p->masked_name ?? $p->full_name,
                    'subtitle' => "Citizenship: " . ($p->countryOfCitizenship?->name ?? 'Unknown') . " | PEP: " . ($p->is_pep ? 'YES' : 'NO'),
                    'flag' => $p->countryOfCitizenship?->name,
                    'status' => $p->verification_status?->value ?? $p->verification_status,
                    'url' => "/persons?highlight={$p->id}",
                    'meta' => [
                        'pep' => $p->is_pep,
                        'sanctions' => $p->is_sanctioned,
                    ],
                ];
            }
        }

        // 4. Search Investigation Cases
        if (in_array('case', $types)) {
            $caseQuery = InvestigationCase::where('case_number', 'ilike', "%{$q}%")
                ->orWhere('title', 'ilike', "%{$q}%")
                ->orWhere('summary', 'ilike', "%{$q}%");

            $cases = $caseQuery->limit($limit)->get();
            foreach ($cases as $case) {
                $results[] = [
                    'entity_type' => 'case',
                    'id' => $case->id,
                    'title' => "[{$case->case_number}] {$case->title}",
                    'subtitle' => "Category: {$case->category?->value} | Priority: {$case->priority?->value}",
                    'flag' => null,
                    'status' => $case->status?->value ?? $case->status,
                    'url' => "/investigations/{$case->id}",
                    'meta' => [
                        'priority' => $case->priority?->value,
                        'created_at' => $case->created_at?->toDateString(),
                    ],
                ];
            }
        }

        // 5. Search Evidences
        if (in_array('evidence', $types)) {
            $evidenceQuery = Evidence::where('title', 'ilike', "%{$q}%")
                ->orWhere('file_name', 'ilike', "%{$q}%")
                ->orWhere('sha256_hash', 'ilike', "%{$q}%");

            $evidences = $evidenceQuery->limit($limit)->get();
            foreach ($evidences as $e) {
                $results[] = [
                    'entity_type' => 'evidence',
                    'id' => $e->id,
                    'title' => $e->title,
                    'subtitle' => "Type: {$e->evidence_type} | Classification: {$e->classification}",
                    'flag' => null,
                    'status' => $e->verification_status?->value ?? $e->verification_status,
                    'url' => "/evidence/{$e->id}",
                    'meta' => [
                        'hash' => substr($e->sha256_hash, 0, 16) . '...',
                    ],
                ];
            }
        }

        // 6. Search IUU Listings
        if (in_array('iuu', $types)) {
            $iuuQuery = IuuVesselListing::with('vessel')
                ->where('listing_organization', 'ilike', "%{$q}%")
                ->orWhere('listing_reason', 'ilike', "%{$q}%")
                ->orWhere('list_name', 'ilike', "%{$q}%");

            $iuuList = $iuuQuery->limit($limit)->get();
            foreach ($iuuList as $i) {
                $results[] = [
                    'entity_type' => 'iuu_listing',
                    'id' => $i->id,
                    'title' => "IUU: {$i->listing_organization} - " . ($i->vessel?->current_name ?? 'Unknown Vessel'),
                    'subtitle' => "Listed: " . ($i->listing_date?->toDateString() ?? 'N/A') . " | Reason: " . substr($i->listing_reason ?? '', 0, 60),
                    'flag' => null,
                    'status' => $i->listing_status ?? 'listed',
                    'url' => "/iuu?highlight={$i->id}",
                    'meta' => [
                        'organization' => $i->listing_organization,
                        'reason' => $i->listing_reason,
                    ],
                ];
            }
        }

        return [
            'query' => $q,
            'total_hits' => count($results),
            'results' => array_slice($results, 0, $limit),
        ];
    }
}

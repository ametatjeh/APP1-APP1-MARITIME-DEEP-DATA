<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CorporateRelationship;
use App\Models\Person;
use App\Models\PersonCorporateTie;
use App\Models\PersonVesselTie;
use App\Models\Vessel;
use App\Models\VesselCompanyAssignment;

class NetworkGraphService
{
    /**
     * Builds multi-hop network graph around a seed entity or consolidated global network
     */
    public function getGraphData(?string $seedType = null, ?string $seedId = null, int $depth = 2): array
    {
        $nodes = [];
        $edges = [];
        $visitedNodes = [];

        if ($seedType && $seedId) {
            $this->traverseEntity($seedType, $seedId, $depth, $nodes, $edges, $visitedNodes);
        } else {
            // General overview graph: fetch sample vessels, companies, and their links
            $vessels = Vessel::with(['companyAssignments.company', 'personTies.person', 'iuuListings'])
                ->limit(15)
                ->get();

            foreach ($vessels as $v) {
                $vNodeId = "vessel_{$v->id}";
                $isSanctioned = $v->iuuListings->count() > 0;
                $nodes[$vNodeId] = [
                    'id' => $vNodeId,
                    'entity_id' => $v->id,
                    'label' => $v->current_name,
                    'type' => 'vessel',
                    'sub_label' => "IMO: " . ($v->imo_number ?? 'N/A'),
                    'flag' => $v->currentFlagCountry?->name,
                    'is_sanctioned' => $isSanctioned,
                    'risk_level' => $isSanctioned ? 'critical' : 'normal',
                ];

                foreach ($v->companyAssignments as $ca) {
                    if (!$ca->company) continue;
                    $cNodeId = "company_{$ca->company->id}";
                    if (!isset($nodes[$cNodeId])) {
                        $nodes[$cNodeId] = [
                            'id' => $cNodeId,
                            'entity_id' => $ca->company->id,
                            'label' => $ca->company->company_name,
                            'type' => 'company',
                            'sub_label' => $ca->company->countryOfIncorporation?->name ?? 'Corporate',
                            'risk_level' => 'normal',
                        ];
                    }

                    $assignmentType = $this->stringify($ca->assignment_type, 'operates');
                    $edgeId = "edge_{$ca->id}";
                    $edges[$edgeId] = [
                        'id' => $edgeId,
                        'from' => $cNodeId,
                        'to' => $vNodeId,
                        'label' => strtoupper($assignmentType),
                        'type' => $assignmentType,
                    ];
                }

                foreach ($v->personTies as $pt) {
                    if (!$pt->person) continue;
                    $pNodeId = "person_{$pt->person->id}";
                    if (!isset($nodes[$pNodeId])) {
                        $nodes[$pNodeId] = [
                            'id' => $pNodeId,
                            'entity_id' => $pt->person->id,
                            'label' => $pt->person->masked_name ?? $pt->person->full_name,
                            'type' => 'person',
                            'sub_label' => $this->stringify($pt->role, 'Crew/Skipper'),
                            'risk_level' => $pt->person->is_sanctioned ? 'critical' : ($pt->person->is_pep ? 'high' : 'normal'),
                        ];
                    }

                    $role = $this->stringify($pt->role, 'crew');
                    $edgeId = "edge_pt_{$pt->id}";
                    $edges[$edgeId] = [
                        'id' => $edgeId,
                        'from' => $pNodeId,
                        'to' => $vNodeId,
                        'label' => strtoupper($role),
                        'type' => $role,
                    ];
                }
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => array_values($edges),
            'total_nodes' => count($nodes),
            'total_edges' => count($edges),
        ];
    }

    /**
     * Recursive multi-hop traversal
     */
    protected function traverseEntity(string $type, string $id, int $depth, array &$nodes, array &$edges, array &$visited): void
    {
        $nodeKey = "{$type}_{$id}";
        if ($depth < 0 || isset($visited[$nodeKey])) {
            return;
        }
        $visited[$nodeKey] = true;

        if ($type === 'vessel') {
            $vessel = Vessel::with(['companyAssignments.company', 'personTies.person', 'iuuListings'])->find($id);
            if (!$vessel) return;

            $isSanctioned = $vessel->iuuListings->count() > 0;
            $nodes[$nodeKey] = [
                'id' => $nodeKey,
                'entity_id' => $vessel->id,
                'label' => $vessel->current_name,
                'type' => 'vessel',
                'sub_label' => "IMO: " . ($vessel->imo_number ?? 'N/A'),
                'is_sanctioned' => $isSanctioned,
                'risk_level' => $isSanctioned ? 'critical' : 'normal',
            ];

            foreach ($vessel->companyAssignments as $ca) {
                if (!$ca->company) continue;
                $cKey = "company_{$ca->company->id}";
                $edgeId = "edge_ca_{$ca->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $cKey,
                    'to' => $nodeKey,
                    'label' => strtoupper($this->stringify($ca->assignment_type, 'OWNS')),
                ];
                $this->traverseEntity('company', $ca->company->id, $depth - 1, $nodes, $edges, $visited);
            }

            foreach ($vessel->personTies as $pt) {
                if (!$pt->person) continue;
                $pKey = "person_{$pt->person->id}";
                $edgeId = "edge_pt_{$pt->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $pKey,
                    'to' => $nodeKey,
                    'label' => strtoupper($this->stringify($pt->role, 'CREW')),
                ];
                $this->traverseEntity('person', $pt->person->id, $depth - 1, $nodes, $edges, $visited);
            }
        } elseif ($type === 'company') {
            $company = Company::with(['vesselAssignments.vessel', 'officers.person', 'shareholders.person', 'parentRelationships.parentCompany'])->find($id);
            if (!$company) return;

            $nodes[$nodeKey] = [
                'id' => $nodeKey,
                'entity_id' => $company->id,
                'label' => $company->company_name,
                'type' => 'company',
                'sub_label' => $company->registration_number ?? 'Corporate',
                'risk_level' => 'normal',
            ];

            foreach ($company->vesselAssignments as $va) {
                if (!$va->vessel) continue;
                $vKey = "vessel_{$va->vessel->id}";
                $edgeId = "edge_va_{$va->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $nodeKey,
                    'to' => $vKey,
                    'label' => strtoupper($this->stringify($va->assignment_type, 'MANAGES')),
                ];
                $this->traverseEntity('vessel', $va->vessel->id, $depth - 1, $nodes, $edges, $visited);
            }

            foreach ($company->officers as $off) {
                if (!$off->person) continue;
                $pKey = "person_{$off->person->id}";
                $edgeId = "edge_off_{$off->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $pKey,
                    'to' => $nodeKey,
                    'label' => strtoupper($this->stringify($off->title ?? $off->position, 'DIRECTOR')),
                ];
                $this->traverseEntity('person', $off->person->id, $depth - 1, $nodes, $edges, $visited);
            }

            foreach ($company->shareholders as $sh) {
                if (!$sh->person) continue;
                $pKey = "person_{$sh->person->id}";
                $edgeId = "edge_sh_{$sh->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $pKey,
                    'to' => $nodeKey,
                    'label' => "SHAREHOLDER ({$sh->percentage}%)",
                ];
                $this->traverseEntity('person', $sh->person->id, $depth - 1, $nodes, $edges, $visited);
            }
        } elseif ($type === 'person') {
            $person = Person::with(['vesselTies.vessel', 'corporateTies.company'])->find($id);
            if (!$person) return;

            $nodes[$nodeKey] = [
                'id' => $nodeKey,
                'entity_id' => $person->id,
                'label' => $person->masked_name ?? $person->full_name,
                'type' => 'person',
                'sub_label' => $person->is_pep ? 'PEP / Political Figure' : 'Individual',
                'risk_level' => $person->is_sanctioned ? 'critical' : ($person->is_pep ? 'high' : 'normal'),
            ];

            foreach ($person->vesselTies as $vt) {
                if (!$vt->vessel) continue;
                $vKey = "vessel_{$vt->vessel->id}";
                $edgeId = "edge_vt_{$vt->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $nodeKey,
                    'to' => $vKey,
                    'label' => strtoupper($this->stringify($vt->role, 'AFFILIATED')),
                ];
                $this->traverseEntity('vessel', $vt->vessel->id, $depth - 1, $nodes, $edges, $visited);
            }

            foreach ($person->corporateTies as $ct) {
                if (!$ct->company) continue;
                $cKey = "company_{$ct->company->id}";
                $edgeId = "edge_ct_{$ct->id}";
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $nodeKey,
                    'to' => $cKey,
                    'label' => strtoupper($this->stringify($ct->relationship_type, 'UBO')),
                ];
                $this->traverseEntity('company', $ct->company->id, $depth - 1, $nodes, $edges, $visited);
            }
        }
    }

    protected function stringify(mixed $val, string $default = ''): string
    {
        if ($val instanceof \BackedEnum) {
            return (string) $val->value;
        }
        return (string) ($val ?? $default);
    }
}

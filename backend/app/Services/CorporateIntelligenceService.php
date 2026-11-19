<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CorporateRelationship;
use App\Models\Vessel;
use App\Models\VesselCompanyAssignment;

class CorporateIntelligenceService
{
    /**
     * Build the corporate network graph centered on a given company or vessel.
     */
    public function getNetworkGraph(string $entityType, string $entityId): array
    {
        $nodes = [];
        $edges = [];
        $visited = [];

        if ($entityType === 'company') {
            $this->traverseCompanyNetwork($entityId, $nodes, $edges, $visited, 2);
        } elseif ($entityType === 'vessel') {
            $this->traverseVesselNetwork($entityId, $nodes, $edges, $visited);
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => array_values($edges),
            'meta' => [
                'total_nodes' => count($nodes),
                'total_edges' => count($edges),
            ],
        ];
    }

    private function traverseCompanyNetwork(string $companyId, array &$nodes, array &$edges, array &$visited, int $depth): void
    {
        if (isset($visited[$companyId]) || $depth < 0) {
            return;
        }
        $visited[$companyId] = true;

        $company = Company::with([
            'countryOfIncorporation',
            'officers',
            'shareholders',
            'subsidiaryRelationships.childCompany',
            'parentRelationships.parentCompany',
            'vesselAssignments.vessel',
        ])->find($companyId);

        if (!$company) {
            return;
        }

        $nodes[$company->id] = [
            'id' => $company->id,
            'type' => 'company',
            'label' => $company->company_name,
            'country' => $company->countryOfIncorporation?->name,
            'legal_form' => $company->legal_form,
            'is_sanctioned' => $company->is_sanctioned,
            'verification_status' => $company->verification_status?->value ?? 'unverified',
        ];

        // Officers as nodes
        foreach ($company->officers as $officer) {
            $officerNodeId = 'officer_' . $officer->id;
            $nodes[$officerNodeId] = [
                'id' => $officerNodeId,
                'type' => 'officer',
                'label' => $officer->getSafeOfficerName(),
                'position' => $officer->position,
                'is_redacted' => $officer->is_redacted,
            ];

            $edges[] = [
                'id' => 'edge_' . $company->id . '_' . $officerNodeId,
                'from' => $officerNodeId,
                'to' => $company->id,
                'relationship' => 'officer_of',
                'label' => $officer->position,
                'is_current' => $officer->is_current,
            ];
        }

        // Parent company relationships
        foreach ($company->parentRelationships as $parentRel) {
            if ($parentRel->parentCompany) {
                $edgeId = 'rel_' . $parentRel->id;
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $parentRel->parent_company_id,
                    'to' => $parentRel->child_company_id,
                    'relationship' => $parentRel->relationship_type,
                    'ownership_percentage' => $parentRel->ownership_percentage,
                    'verification_status' => $parentRel->verification_status?->value ?? 'unverified',
                ];

                $this->traverseCompanyNetwork($parentRel->parent_company_id, $nodes, $edges, $visited, $depth - 1);
            }
        }

        // Subsidiary relationships
        foreach ($company->subsidiaryRelationships as $subRel) {
            if ($subRel->childCompany) {
                $edgeId = 'rel_' . $subRel->id;
                $edges[$edgeId] = [
                    'id' => $edgeId,
                    'from' => $subRel->parent_company_id,
                    'to' => $subRel->child_company_id,
                    'relationship' => $subRel->relationship_type,
                    'ownership_percentage' => $subRel->ownership_percentage,
                    'verification_status' => $subRel->verification_status?->value ?? 'unverified',
                ];

                $this->traverseCompanyNetwork($subRel->child_company_id, $nodes, $edges, $visited, $depth - 1);
            }
        }

        // Assigned vessels
        foreach ($company->vesselAssignments as $assignment) {
            if ($assignment->vessel) {
                $vessel = $assignment->vessel;
                $nodes[$vessel->id] = [
                    'id' => $vessel->id,
                    'type' => 'vessel',
                    'label' => $vessel->current_name,
                    'imo' => $vessel->imo_number,
                    'mmsi' => $vessel->current_mmsi,
                    'vessel_type' => $vessel->vessel_type,
                    'verification_status' => $vessel->verification_status?->value ?? 'unverified',
                ];

                $edges[] = [
                    'id' => 'assign_' . $assignment->id,
                    'from' => $company->id,
                    'to' => $vessel->id,
                    'relationship' => $assignment->assignment_type?->value ?? 'owner',
                    'label' => $assignment->assignment_type?->label() ?? 'Owner',
                    'is_current' => $assignment->is_current,
                    'verification_status' => $assignment->verification_status?->value ?? 'unverified',
                ];
            }
        }
    }

    private function traverseVesselNetwork(string $vesselId, array &$nodes, array &$edges, array &$visited): void
    {
        $vessel = Vessel::with(['companyAssignments.company', 'currentFlagCountry'])->find($vesselId);
        if (!$vessel) {
            return;
        }

        $nodes[$vessel->id] = [
            'id' => $vessel->id,
            'type' => 'vessel',
            'label' => $vessel->current_name,
            'imo' => $vessel->imo_number,
            'mmsi' => $vessel->current_mmsi,
            'flag' => $vessel->currentFlagCountry?->name,
            'vessel_type' => $vessel->vessel_type,
            'verification_status' => $vessel->verification_status?->value ?? 'unverified',
        ];

        foreach ($vessel->companyAssignments as $assignment) {
            if ($assignment->company) {
                $edges[] = [
                    'id' => 'assign_' . $assignment->id,
                    'from' => $assignment->company_id,
                    'to' => $vessel->id,
                    'relationship' => $assignment->assignment_type?->value ?? 'owner',
                    'label' => $assignment->assignment_type?->label() ?? 'Owner',
                    'is_current' => $assignment->is_current,
                    'verification_status' => $assignment->verification_status?->value ?? 'unverified',
                ];

                $this->traverseCompanyNetwork($assignment->company_id, $nodes, $edges, $visited, 1);
            }
        }
    }
}

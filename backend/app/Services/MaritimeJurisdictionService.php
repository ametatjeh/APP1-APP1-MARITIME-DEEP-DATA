<?php

namespace App\Services;

use App\Models\MaritimeBoundaryClaim;
use App\Models\MaritimeJurisdiction;
use App\Models\OverlappingClaim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaritimeJurisdictionService
{
    /**
     * Identify which maritime jurisdictions and disputed claims contain a specific WGS84 coordinate.
     */
    public function identifyJurisdictionsForPoint(float $longitude, float $latitude): array
    {
        $jurisdictions = MaritimeJurisdiction::with('country')
            ->select('id', 'country_id', 'jurisdiction_type', 'area_name', 'legal_status', 'legal_basis', 'effective_date')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->containingPoint($longitude, $latitude)
            ->get();

        $overlappingClaims = OverlappingClaim::with(['jurisdictionA.country', 'jurisdictionB.country'])
            ->select('id', 'jurisdiction_a_id', 'jurisdiction_b_id', 'claim_type', 'resolution_status', 'analyst_notes')
            ->selectRaw('ST_AsGeoJSON(overlap_geom) as geojson')
            ->whereRaw('ST_Contains(overlap_geom, ST_SetSRID(ST_Point(?, ?), 4326))', [$longitude, $latitude])
            ->get();

        return [
            'point' => [
                'longitude' => $longitude,
                'latitude' => $latitude,
            ],
            'containing_jurisdictions' => $jurisdictions->map(fn($j) => [
                'id' => $j->id,
                'country' => $j->country->name,
                'iso_code' => $j->country->iso_code_2,
                'jurisdiction_type' => $j->jurisdiction_type->value,
                'jurisdiction_label' => $j->jurisdiction_type->label(),
                'area_name' => $j->area_name,
                'legal_status' => $j->legal_status->value,
                'legal_basis' => $j->legal_basis,
                'effective_date' => $j->effective_date?->format('Y-m-d'),
            ]),
            'in_disputed_or_overlapping_zone' => $overlappingClaims->isNotEmpty(),
            'overlapping_claims' => $overlappingClaims->map(fn($o) => [
                'id' => $o->id,
                'claim_type' => $o->claim_type,
                'resolution_status' => $o->resolution_status,
                'countries_involved' => [
                    $o->jurisdictionA?->country?->name,
                    $o->jurisdictionB?->country?->name,
                ],
                'notes' => $o->analyst_notes,
            ]),
        ];
    }

    /**
     * Export all maritime boundaries as standard RFC 7946 GeoJSON FeatureCollection.
     */
    public function exportGeoJsonFeatureCollection(?string $countryId = null, ?string $type = null): array
    {
        $query = MaritimeJurisdiction::with('country')
            ->select('id', 'country_id', 'jurisdiction_type', 'area_name', 'legal_status', 'legal_basis', 'verification_status')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson_geom')
            ->whereNotNull('geom');

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($type) {
            $query->where('jurisdiction_type', $type);
        }

        $jurisdictions = $query->get();

        $features = [];
        foreach ($jurisdictions as $item) {
            $geometry = json_decode($item->geojson_geom, true);
            if ($geometry) {
                $features[] = [
                    'type' => 'Feature',
                    'id' => $item->id,
                    'geometry' => $geometry,
                    'properties' => [
                        'id' => $item->id,
                        'country_name' => $item->country->name,
                        'iso_code' => $item->country->iso_code_2,
                        'jurisdiction_type' => $item->jurisdiction_type->value,
                        'area_name' => $item->area_name,
                        'legal_status' => $item->legal_status->value,
                        'legal_basis' => $item->legal_basis,
                        'verification_status' => $item->verification_status->value,
                    ],
                ];
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}

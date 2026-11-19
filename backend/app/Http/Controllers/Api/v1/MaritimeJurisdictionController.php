<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaritimeBoundaryClaim;
use App\Models\MaritimeJurisdiction;
use App\Models\OverlappingClaim;
use App\Services\MaritimeJurisdictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaritimeJurisdictionController extends Controller
{
    public function __construct(
        protected MaritimeJurisdictionService $jurisdictionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = MaritimeJurisdiction::with('country')
            ->select('id', 'country_id', 'jurisdiction_type', 'area_name', 'legal_status', 'legal_basis', 'effective_date', 'verification_status')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson');

        if ($countryId = $request->input('country_id')) {
            $query->where('country_id', $countryId);
        }

        if ($type = $request->input('jurisdiction_type')) {
            $query->where('jurisdiction_type', $type);
        }

        if ($legalStatus = $request->input('legal_status')) {
            $query->where('legal_status', $legalStatus);
        }

        $jurisdictions = $query->orderBy('area_name')->paginate($request->input('per_page', 25));

        return response()->json($jurisdictions);
    }

    public function show(string $id): JsonResponse
    {
        $jurisdiction = MaritimeJurisdiction::with([
            'country',
            'sourceRecord.dataSource',
            'overlappingClaimsAsA.jurisdictionB.country',
            'overlappingClaimsAsB.jurisdictionA.country',
        ])
        ->select('*')
        ->selectRaw('ST_AsGeoJSON(geom) as geojson')
        ->findOrFail($id);

        return response()->json(['data' => $jurisdiction]);
    }

    public function pointLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
        ]);

        $result = $this->jurisdictionService->identifyJurisdictionsForPoint(
            (float) $validated['longitude'],
            (float) $validated['latitude']
        );

        return response()->json($result);
    }

    public function exportGeoJson(Request $request): JsonResponse
    {
        $countryId = $request->input('country_id');
        $type = $request->input('jurisdiction_type');

        $geojson = $this->jurisdictionService->exportGeoJsonFeatureCollection($countryId, $type);

        return response()->json($geojson);
    }

    public function overlappingClaims(): JsonResponse
    {
        $claims = OverlappingClaim::with([
            'jurisdictionA.country',
            'jurisdictionB.country',
        ])
        ->select('*')
        ->selectRaw('ST_AsGeoJSON(overlap_geom) as geojson')
        ->get();

        return response()->json(['data' => $claims]);
    }

    public function boundaryClaims(): JsonResponse
    {
        $boundaryClaims = MaritimeBoundaryClaim::with([
            'claimantCountry',
            'opposingCountry',
            'sourceRecord',
        ])
        ->select('*')
        ->selectRaw('ST_AsGeoJSON(geom) as geojson')
        ->get();

        return response()->json(['data' => $boundaryClaims]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IuuInfractionEvent;
use App\Models\MaritimeJurisdiction;
use App\Models\ResearchPolygon;
use App\Models\SpatialEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GisResearchMapController extends Controller
{
    /**
     * Aggregated GeoJSON layers for interactive Leaflet research map
     */
    public function getMapLayers(Request $request): JsonResponse
    {
        // 1. Jurisdictions (EEZ, Archipelagic, Disputed)
        $jurisdictions = MaritimeJurisdiction::select(['id', 'area_name', 'jurisdiction_type', 'legal_status', 'country_id', DB::raw('ST_AsGeoJSON(geom) as geojson')])
            ->with('country:id,name,iso_code_3')
            ->get();

        $jurisdictionFeatures = $jurisdictions->map(function ($j) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $j->id,
                    'type' => 'jurisdiction',
                    'name' => $j->area_name,
                    'jurisdiction_type' => $j->jurisdiction_type,
                    'legal_status' => $j->legal_status,
                    'country' => $j->country?->name,
                ],
                'geometry' => json_decode($j->geojson),
            ];
        });

        // 2. Custom Research Polygons
        $polygons = ResearchPolygon::withGeoJson()->where('is_active', true)->get();
        $polygonFeatures = $polygons->map(function ($p) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $p->id,
                    'type' => 'research_polygon',
                    'name' => $p->name,
                    'category' => $p->category,
                    'description' => $p->description,
                ],
                'geometry' => json_decode($p->geojson),
            ];
        });

        // 3. Static Research Spatial Events (Boardings, Seizures, Sighting Events)
        $spatialEvents = SpatialEvent::withCoordinates()->with('vessel:id,name,imo,flag_state')->get();
        $spatialEventFeatures = $spatialEvents->filter(fn($e) => !empty($e->geojson))->map(function ($e) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $e->id,
                    'type' => 'spatial_event',
                    'title' => $e->title,
                    'event_type' => $e->event_type,
                    'event_date' => $e->event_date?->toISOString(),
                    'vessel_name' => $e->vessel?->name,
                    'vessel_imo' => $e->vessel?->imo,
                    'vessel_flag' => $e->vessel?->flag_state,
                    'notes' => $e->notes,
                ],
                'geometry' => json_decode($e->geojson),
            ];
        });

        // 4. IUU Infractions with coordinates
        $iuuEvents = IuuInfractionEvent::withCoordinates()->with('vessel:id,name,imo,flag_state')->get();
        $iuuFeatures = $iuuEvents->filter(fn($i) => !empty($i->geojson))->map(function ($i) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $i->id,
                    'type' => 'iuu_infraction',
                    'title' => $i->infraction_type,
                    'category' => $i->event_category,
                    'severity' => $i->severity,
                    'event_date' => $i->event_date?->toDateString(),
                    'vessel_name' => $i->vessel?->name,
                    'vessel_flag' => $i->vessel?->flag_state,
                    'description' => $i->description,
                    'action_taken' => $i->action_taken,
                ],
                'geometry' => json_decode($i->geojson),
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'layers' => [
                'jurisdictions' => [
                    'type' => 'FeatureCollection',
                    'features' => $jurisdictionFeatures->values(),
                ],
                'research_polygons' => [
                    'type' => 'FeatureCollection',
                    'features' => $polygonFeatures->values(),
                ],
                'spatial_events' => [
                    'type' => 'FeatureCollection',
                    'features' => $spatialEventFeatures->values(),
                ],
                'iuu_infractions' => [
                    'type' => 'FeatureCollection',
                    'features' => $iuuFeatures->values(),
                ],
            ],
        ]);
    }

    // Research Polygons CRUD
    public function polygons(Request $request): JsonResponse
    {
        $polygons = ResearchPolygon::withGeoJson()
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json($polygons);
    }

    public function storePolygon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'geojson' => 'required|array',
            'metadata' => 'nullable|array',
        ]);

        $geojsonStr = json_encode($validated['geojson']);

        $polygon = new ResearchPolygon([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()?->id,
            'is_active' => true,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $polygon->geom = DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON('{$geojsonStr}'), 4326))");
        $polygon->save();

        AuditLog::log('create_research_polygon', $polygon, null, ['name' => $polygon->name]);

        return response()->json([
            'data' => $polygon,
            'message' => 'Research polygon layer created successfully.',
        ], 201);
    }

    // Spatial Events CRUD
    public function spatialEvents(Request $request): JsonResponse
    {
        $query = SpatialEvent::withCoordinates()
            ->with(['vessel', 'investigationCase', 'maritimeJurisdiction']);

        if ($type = $request->input('event_type')) {
            $query->where('event_type', $type);
        }

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }

        if ($caseId = $request->input('investigation_case_id')) {
            $query->where('investigation_case_id', $caseId);
        }

        $events = $query->orderByDesc('event_date')->paginate($request->input('per_page', 25));

        return response()->json($events);
    }

    public function storeSpatialEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|string|max:50',
            'event_date' => 'required|date',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'vessel_id' => 'nullable|exists:vessels,id',
            'investigation_case_id' => 'nullable|exists:investigation_cases,id',
            'notes' => 'nullable|string',
        ]);

        // Auto-detect containing jurisdiction
        $jurisdiction = MaritimeJurisdiction::whereRaw(
            'ST_Contains(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$validated['longitude'], $validated['latitude']]
        )->first();

        $event = new SpatialEvent([
            'title' => $validated['title'],
            'event_type' => $validated['event_type'],
            'event_date' => $validated['event_date'],
            'vessel_id' => $validated['vessel_id'] ?? null,
            'investigation_case_id' => $validated['investigation_case_id'] ?? null,
            'maritime_jurisdiction_id' => $jurisdiction?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $event->geom = DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)");
        $event->save();

        AuditLog::log('create_spatial_event', $event, null, $validated);

        return response()->json([
            'data' => $event,
            'message' => 'Spatial research event recorded successfully.',
        ], 201);
    }

    // Spatial Query (Point containment and bounding box)
    public function spatialQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'longitude' => 'required_without:bbox|numeric|between:-180,180',
            'latitude' => 'required_without:bbox|numeric|between:-90,90',
            'bbox' => 'nullable|array', // [minLon, minLat, maxLon, maxLat]
        ]);

        if (!empty($validated['bbox'])) {
            [$minLon, $minLat, $maxLon, $maxLat] = $validated['bbox'];
            $envelopeSql = "ST_MakeEnvelope({$minLon}, {$minLat}, {$maxLon}, {$maxLat}, 4326)";

            $events = SpatialEvent::withCoordinates()
                ->whereRaw("ST_Intersects(geom, {$envelopeSql})")
                ->with('vessel')
                ->get();

            $iuu = IuuInfractionEvent::withCoordinates()
                ->whereRaw("ST_Intersects(geom, {$envelopeSql})")
                ->with('vessel')
                ->get();

            $jurisdictions = MaritimeJurisdiction::whereRaw("ST_Intersects(geom, {$envelopeSql})")
                ->get(['id', 'area_name', 'jurisdiction_type', 'legal_status']);

            return response()->json([
                'query_type' => 'bbox_intersection',
                'spatial_events_count' => $events->count(),
                'spatial_events' => $events,
                'iuu_infractions_count' => $iuu->count(),
                'iuu_infractions' => $iuu,
                'intersecting_jurisdictions' => $jurisdictions,
            ]);
        }

        $lon = $validated['longitude'];
        $lat = $validated['latitude'];

        // Point containment
        $containingJurisdictions = MaritimeJurisdiction::whereRaw(
            'ST_Contains(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$lon, $lat]
        )->get(['id', 'area_name', 'jurisdiction_type', 'legal_status', 'country_id']);

        $containingPolygons = ResearchPolygon::withGeoJson()->whereRaw(
            'ST_Contains(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$lon, $lat]
        )->get();

        $nearbyEvents = SpatialEvent::withCoordinates()
            ->whereRaw(
                'ST_DWithin(geom::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, 100000)',
                [$lon, $lat]
            )
            ->with('vessel')
            ->limit(10)
            ->get();

        return response()->json([
            'query_type' => 'point_containment',
            'coordinates' => ['longitude' => $lon, 'latitude' => $lat],
            'containing_jurisdictions' => $containingJurisdictions,
            'containing_research_polygons' => $containingPolygons,
            'nearby_events_within_100km' => $nearbyEvents,
        ]);
    }
}

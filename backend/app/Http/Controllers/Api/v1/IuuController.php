<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IuuInfractionEvent;
use App\Models\IuuVesselListing;
use App\Models\MaritimeJurisdiction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IuuController extends Controller
{
    // Official IUU Vessel Listings
    public function listings(Request $request): JsonResponse
    {
        $query = IuuVesselListing::with(['vessel.flagCountry', 'dataSource']);

        if ($org = $request->input('listing_organization')) {
            $query->where('listing_organization', $org);
        }

        if ($status = $request->input('listing_status')) {
            $query->where('listing_status', $status);
        }

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('list_name', 'ILIKE', "%{$search}%")
                  ->orWhere('official_reference_number', 'ILIKE', "%{$search}%")
                  ->orWhereHas('vessel', function ($vq) use ($search) {
                      $vq->where('name', 'ILIKE', "%{$search}%")
                         ->orWhere('imo', 'ILIKE', "%{$search}%")
                         ->orWhere('mmsi', 'ILIKE', "%{$search}%");
                  });
            });
        }

        $listings = $query->orderByDesc('listing_date')->paginate($request->input('per_page', 25));

        return response()->json($listings);
    }

    public function storeListing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_id' => 'required|exists:vessels,id',
            'list_name' => 'required|string|max:255',
            'listing_organization' => 'required|string|max:100',
            'listing_date' => 'required|date',
            'delisting_date' => 'nullable|date',
            'listing_reason' => 'required|string',
            'listing_status' => 'required|string|max:50',
            'reported_flag_state' => 'nullable|string|max:100',
            'reported_owner' => 'nullable|string|max:255',
            'reported_master' => 'nullable|string|max:255',
            'official_reference_number' => 'nullable|string|max:100',
            'source_url' => 'nullable|url',
            'data_source_id' => 'nullable|exists:data_sources,id',
        ]);

        $listing = IuuVesselListing::create($validated);
        AuditLog::log('create_iuu_vessel_listing', $listing, null, $validated);

        return response()->json([
            'data' => $listing,
            'message' => 'Official IUU listing recorded.',
        ], 201);
    }

    // IUU Infraction Events
    public function infractions(Request $request): JsonResponse
    {
        $query = IuuInfractionEvent::withCoordinates()
            ->with(['vessel', 'maritimeJurisdiction', 'investigationCase', 'dataSource']);

        if ($type = $request->input('infraction_type')) {
            $query->where('infraction_type', $type);
        }

        if ($category = $request->input('event_category')) {
            $query->where('event_category', $category);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }

        if ($jurisdictionId = $request->input('maritime_jurisdiction_id')) {
            $query->where('maritime_jurisdiction_id', $jurisdictionId);
        }

        if ($caseId = $request->input('investigation_case_id')) {
            $query->where('investigation_case_id', $caseId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                  ->orWhere('reporting_agency', 'ILIKE', "%{$search}%")
                  ->orWhere('location_text', 'ILIKE', "%{$search}%")
                  ->orWhereHas('vessel', function ($vq) use ($search) {
                      $vq->where('name', 'ILIKE', "%{$search}%");
                  });
            });
        }

        $events = $query->orderByDesc('event_date')->paginate($request->input('per_page', 25));

        return response()->json($events);
    }

    public function storeInfraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_id' => 'required|exists:vessels,id',
            'infraction_type' => 'required|string|max:100',
            'event_category' => 'required|string|max:50',
            'severity' => 'required|string|in:minor,moderate,serious,critical',
            'event_date' => 'required|date',
            'location_text' => 'nullable|string|max:255',
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'maritime_jurisdiction_id' => 'nullable|exists:maritime_jurisdictions,id',
            'description' => 'required|string',
            'reporting_agency' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string',
            'investigation_case_id' => 'nullable|exists:investigation_cases,id',
            'data_source_id' => 'nullable|exists:data_sources,id',
        ]);

        $hasCoords = isset($validated['longitude']) && isset($validated['latitude']);

        // Auto-detect jurisdiction if coordinates are provided but jurisdiction is omitted
        $jurisdictionId = $validated['maritime_jurisdiction_id'] ?? null;
        if ($hasCoords && empty($jurisdictionId)) {
            $containing = MaritimeJurisdiction::whereRaw(
                'ST_Contains(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
                [$validated['longitude'], $validated['latitude']]
            )->first();
            if ($containing) {
                $jurisdictionId = $containing->id;
            }
        }

        $event = new IuuInfractionEvent([
            'vessel_id' => $validated['vessel_id'],
            'infraction_type' => $validated['infraction_type'],
            'event_category' => $validated['event_category'],
            'severity' => $validated['severity'],
            'event_date' => $validated['event_date'],
            'location_text' => $validated['location_text'] ?? null,
            'maritime_jurisdiction_id' => $jurisdictionId,
            'description' => $validated['description'],
            'reporting_agency' => $validated['reporting_agency'] ?? null,
            'action_taken' => $validated['action_taken'] ?? null,
            'investigation_case_id' => $validated['investigation_case_id'] ?? null,
            'data_source_id' => $validated['data_source_id'] ?? null,
            'verification_status' => 'unverified',
        ]);

        if ($hasCoords) {
            $event->geom = DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)");
        }

        $event->save();
        AuditLog::log('report_iuu_infraction', $event, null, $validated);

        return response()->json([
            'data' => $event,
            'message' => 'IUU infraction event registered.',
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vessel;
use App\Models\VesselFlag;
use App\Models\VesselIdentifier;
use App\Models\VesselName;
use App\Services\IdentityResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    public function __construct(
        protected IdentityResolutionService $identityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Vessel::with(['currentFlagCountry'])
            ->withCount(['identifiers', 'photos', 'licenses', 'conflictsAsA', 'conflictsAsB']);

        if ($flag = $request->input('flag_country_id')) {
            $query->where('current_flag_country_id', $flag);
        }

        if ($type = $request->input('vessel_type')) {
            $query->where('vessel_type', $type);
        }

        if ($status = $request->input('verification_status')) {
            $query->where('verification_status', $status);
        }

        if ($search = $request->input('search')) {
            $query->searchFuzzy($search);
        }

        $vessels = $query->orderBy('current_name')->paginate($request->input('per_page', 25));

        return response()->json($vessels);
    }

    public function show(string $id): JsonResponse
    {
        $vessel = Vessel::with([
            'currentFlagCountry',
            'identifiers.sourceRecord',
            'names.sourceRecord',
            'flags.country',
            'registrations.country',
            'licenses.issuingCountry',
            'photos',
            'aliases',
            'companyAssignments.company.countryOfIncorporation',
            'conflictsAsA.vesselB',
            'conflictsAsB.vesselA',
        ])->findOrFail($id);

        return response()->json(['data' => $vessel]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_name' => 'required|string|max:255',
            'current_flag_country_id' => 'nullable|exists:countries,id',
            'imo_number' => 'nullable|string|max:10',
            'current_mmsi' => 'nullable|string|max:9',
            'current_callsign' => 'nullable|string|max:20',
            'national_registration_number' => 'nullable|string|max:100',
            'vessel_type' => 'nullable|string|max:100',
            'fishing_gear_type' => 'nullable|string|max:100',
            'gross_tonnage' => 'nullable|numeric|min:0',
            'length_overall' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'draught' => 'nullable|numeric|min:0',
            'engine_power_kw' => 'nullable|numeric|min:0',
            'built_year' => 'nullable|integer|between:1900,2050',
            'shipyard' => 'nullable|string|max:255',
            'port_of_registry' => 'nullable|string|max:255',
            'verification_status' => 'nullable|in:unverified,verified,disputed,flagged_suspicious',
            'notes' => 'nullable|string',
            'structural_specs' => 'nullable|array',
        ]);

        $vessel = Vessel::create($validated);

        // Record initial temporal observations
        VesselName::create([
            'vessel_id' => $vessel->id,
            'name' => $vessel->current_name,
            'is_current' => true,
            'valid_from' => now()->toDateString(),
        ]);

        if ($vessel->current_flag_country_id) {
            VesselFlag::create([
                'vessel_id' => $vessel->id,
                'country_id' => $vessel->current_flag_country_id,
                'is_current' => true,
                'valid_from' => now()->toDateString(),
            ]);
        }

        if (!empty($vessel->imo_number)) {
            VesselIdentifier::create([
                'vessel_id' => $vessel->id,
                'identifier_type' => 'imo',
                'identifier_value' => $vessel->imo_number,
                'is_current' => true,
            ]);
        }

        if (!empty($vessel->current_mmsi)) {
            VesselIdentifier::create([
                'vessel_id' => $vessel->id,
                'identifier_type' => 'mmsi',
                'identifier_value' => $vessel->current_mmsi,
                'is_current' => true,
            ]);
        }

        // Automatic conflict detection
        $conflicts = $this->identityService->detectAndRecordConflicts($vessel);

        AuditLog::log('create', $vessel, null, $validated, ['conflicts_detected' => count($conflicts)]);

        return response()->json([
            'data' => $vessel->load(['currentFlagCountry', 'identifiers', 'names', 'flags']),
            'conflicts_detected' => count($conflicts),
            'message' => 'Vessel profile created successfully.',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::findOrFail($id);

        $validated = $request->validate([
            'current_name' => 'sometimes|required|string|max:255',
            'current_flag_country_id' => 'nullable|exists:countries,id',
            'imo_number' => 'nullable|string|max:10',
            'current_mmsi' => 'nullable|string|max:9',
            'current_callsign' => 'nullable|string|max:20',
            'national_registration_number' => 'nullable|string|max:100',
            'vessel_type' => 'nullable|string|max:100',
            'fishing_gear_type' => 'nullable|string|max:100',
            'gross_tonnage' => 'nullable|numeric|min:0',
            'length_overall' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'draught' => 'nullable|numeric|min:0',
            'engine_power_kw' => 'nullable|numeric|min:0',
            'built_year' => 'nullable|integer|between:1900,2050',
            'shipyard' => 'nullable|string|max:255',
            'port_of_registry' => 'nullable|string|max:255',
            'verification_status' => 'nullable|in:unverified,verified,disputed,flagged_suspicious',
            'notes' => 'nullable|string',
            'structural_specs' => 'nullable|array',
        ]);

        $old = $vessel->toArray();
        $vessel->update($validated);

        // Check if conflict arose from updated identifiers
        $conflicts = $this->identityService->detectAndRecordConflicts($vessel);

        AuditLog::log('update', $vessel, $old, $validated, ['conflicts_detected' => count($conflicts)]);

        return response()->json([
            'data' => $vessel,
            'conflicts_detected' => count($conflicts),
            'message' => 'Vessel updated successfully.',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $vessel = Vessel::findOrFail($id);
        $vessel->delete();

        AuditLog::log('delete', $vessel);

        return response()->json(['message' => 'Vessel soft deleted successfully.']);
    }

    public function timeline(string $id): JsonResponse
    {
        $vessel = Vessel::with([
            'names',
            'flags.country',
            'registrations.country',
            'licenses.issuingCountry',
            'companyAssignments.company',
        ])->findOrFail($id);

        $timeline = collect();

        foreach ($vessel->names as $name) {
            $timeline->push([
                'event_type' => 'name_change',
                'title' => "Name: {$name->name}",
                'valid_from' => $name->valid_from?->format('Y-m-d'),
                'valid_to' => $name->valid_to?->format('Y-m-d'),
                'is_current' => $name->is_current,
                'verification_status' => $name->verification_status?->value ?? 'unverified',
            ]);
        }

        foreach ($vessel->flags as $flag) {
            $timeline->push([
                'event_type' => 'flag_change',
                'title' => "Flag: {$flag->country->name}",
                'valid_from' => $flag->valid_from?->format('Y-m-d'),
                'valid_to' => $flag->valid_to?->format('Y-m-d'),
                'is_current' => $flag->is_current,
                'verification_status' => $flag->verification_status?->value ?? 'unverified',
            ]);
        }

        foreach ($vessel->registrations as $reg) {
            $timeline->push([
                'event_type' => 'registration',
                'title' => "Registered in {$reg->country->name} (#{$reg->registration_number})",
                'valid_from' => $reg->issue_date?->format('Y-m-d'),
                'valid_to' => $reg->expiry_date?->format('Y-m-d'),
                'status' => $reg->status,
                'verification_status' => $reg->verification_status?->value ?? 'unverified',
            ]);
        }

        foreach ($vessel->companyAssignments as $assignment) {
            $timeline->push([
                'event_type' => 'ownership_management',
                'title' => "{$assignment->assignment_type?->label()}: {$assignment->company->company_name}",
                'valid_from' => $assignment->valid_from?->format('Y-m-d'),
                'valid_to' => $assignment->valid_to?->format('Y-m-d'),
                'is_current' => $assignment->is_current,
                'verification_status' => $assignment->verification_status?->value ?? 'unverified',
            ]);
        }

        $sorted = $timeline->sortByDesc(fn($item) => $item['valid_from'] ?? '1900-01-01')->values();

        return response()->json(['data' => $sorted]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CrewEmployment;
use App\Models\LabourViolationReport;
use App\Models\ManningAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrewController extends Controller
{
    // Manning Agents
    public function manningAgents(Request $request): JsonResponse
    {
        $query = ManningAgent::with(['country'])->withCount(['employments']);

        if ($rating = $request->input('compliance_rating')) {
            $query->where('compliance_rating', $rating);
        }

        if ($countryId = $request->input('country_id')) {
            $query->where('country_id', $countryId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('agent_name', 'ILIKE', "%{$search}%")
                  ->orWhere('license_number', 'ILIKE', "%{$search}%");
            });
        }

        $agents = $query->orderBy('agent_name')->paginate($request->input('per_page', 25));

        return response()->json($agents);
    }

    public function storeManningAgent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_name' => 'required|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'country_id' => 'nullable|exists:countries,id',
            'registered_address' => 'nullable|string',
            'compliance_rating' => 'required|string|max:50',
            'past_violations_summary' => 'nullable|string',
        ]);

        $agent = ManningAgent::create($validated);
        AuditLog::log('create_manning_agent', $agent, null, $validated);

        return response()->json([
            'data' => $agent,
            'message' => 'Manning agency registered successfully.',
        ], 201);
    }

    // Crew Employments
    public function employments(Request $request): JsonResponse
    {
        $query = CrewEmployment::with([
            'person.nationalityCountry',
            'vessel',
            'manningAgent',
        ])->withCount(['labourReports']);

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }

        if ($personId = $request->input('person_id')) {
            $query->where('person_id', $personId);
        }

        if ($status = $request->input('contract_status')) {
            $query->where('contract_status', $status);
        }

        if ($role = $request->input('role_on_board')) {
            $query->where('role_on_board', $role);
        }

        if ($request->has('is_forced_labour_suspected')) {
            $query->where('is_forced_labour_suspected', filter_var($request->is_forced_labour_suspected, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('person', function ($pq) use ($search) {
                $pq->where('full_name', 'ILIKE', "%{$search}%");
            })->orWhereHas('vessel', function ($vq) use ($search) {
                $vq->where('name', 'ILIKE', "%{$search}%")
                   ->orWhere('imo', 'ILIKE', "%{$search}%");
            });
        }

        $employments = $query->orderByDesc('embarkation_date')->paginate($request->input('per_page', 25));

        return response()->json($employments);
    }

    public function storeEmployment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:persons,id',
            'vessel_id' => 'required|exists:vessels,id',
            'manning_agent_id' => 'nullable|exists:manning_agents,id',
            'role_on_board' => 'required|string|max:100',
            'embarkation_date' => 'nullable|date',
            'embarkation_port' => 'nullable|string|max:255',
            'disembarkation_date' => 'nullable|date',
            'disembarkation_port' => 'nullable|string|max:255',
            'contract_status' => 'required|string|max:50',
            'salary_currency' => 'nullable|string|size:3',
            'reported_monthly_wage' => 'nullable|numeric|min:0',
            'is_forced_labour_suspected' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $employment = CrewEmployment::create($validated);
        AuditLog::log('create_crew_employment', $employment, null, $validated);

        return response()->json([
            'data' => $employment,
            'message' => 'Crew embarkation record created successfully.',
        ], 201);
    }

    // Labour Violation Reports (ILO 11 Indicators)
    public function labourViolations(Request $request): JsonResponse
    {
        $query = LabourViolationReport::with([
            'person',
            'vessel',
            'crewEmployment',
            'investigationCase',
        ]);

        if ($indicator = $request->input('indicator_type')) {
            $query->where('indicator_type', $indicator);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($vesselId = $request->input('vessel_id')) {
            $query->where('vessel_id', $vesselId);
        }

        if ($caseId = $request->input('investigation_case_id')) {
            $query->where('investigation_case_id', $caseId);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN));
        }

        $reports = $query->orderByDesc('incident_date')->paginate($request->input('per_page', 25));

        return response()->json($reports);
    }

    public function storeLabourViolation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_id' => 'required|exists:vessels,id',
            'person_id' => 'nullable|exists:persons,id',
            'crew_employment_id' => 'nullable|exists:crew_employments,id',
            'indicator_type' => 'required|string|max:100',
            'severity' => 'required|string|in:low,medium,high,critical',
            'incident_date' => 'nullable|date',
            'incident_description' => 'required|string',
            'reported_by' => 'nullable|string|max:255',
            'reporting_organization' => 'nullable|string|max:255',
            'investigation_case_id' => 'nullable|exists:investigation_cases,id',
        ]);

        $report = LabourViolationReport::create($validated);

        // If high or critical, update flag on employment
        if (!empty($validated['crew_employment_id']) && in_array($validated['severity'], ['high', 'critical'])) {
            CrewEmployment::where('id', $validated['crew_employment_id'])->update(['is_forced_labour_suspected' => true]);
        }

        AuditLog::log('report_labour_violation', $report, null, $validated);

        return response()->json([
            'data' => $report,
            'message' => 'Labour violation report registered.',
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Services\CorporateIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateController extends Controller
{
    public function __construct(
        protected CorporateIntelligenceService $corporateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['countryOfIncorporation'])
            ->withCount(['officers', 'shareholders', 'vessels']);

        if ($country = $request->input('country_id')) {
            $query->where('country_of_incorporation_id', $country);
        }

        if ($status = $request->input('corporate_status')) {
            $query->where('corporate_status', $status);
        }

        if ($request->has('is_sanctioned')) {
            $query->where('is_sanctioned', filter_var($request->is_sanctioned, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->searchFuzzy($search);
        }

        $companies = $query->orderBy('company_name')->paginate($request->input('per_page', 25));

        return response()->json($companies);
    }

    public function show(string $id): JsonResponse
    {
        $company = Company::with([
            'countryOfIncorporation',
            'identifiers.issuingCountry',
            'officers.sourceRecord',
            'shareholders.sourceRecord',
            'subsidiaryRelationships.childCompany.countryOfIncorporation',
            'parentRelationships.parentCompany.countryOfIncorporation',
            'vesselAssignments.vessel.currentFlagCountry',
            'sourceRecord.dataSource',
        ])->findOrFail($id);

        return response()->json(['data' => $company]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'country_of_incorporation_id' => 'nullable|exists:countries,id',
            'incorporation_date' => 'nullable|date',
            'dissolution_date' => 'nullable|date',
            'legal_form' => 'nullable|string|max:100',
            'corporate_status' => 'nullable|in:active,dissolved,suspended,dormant,struck_off',
            'registered_address' => 'nullable|string',
            'is_sanctioned' => 'boolean',
            'sanction_details' => 'nullable|array',
            'verification_status' => 'nullable|in:unverified,verified,disputed,flagged_suspicious',
            'source_record_id' => 'nullable|exists:source_records,id',
        ]);

        $company = Company::create($validated);
        AuditLog::log('create', $company, null, $validated);

        return response()->json(['data' => $company, 'message' => 'Company created successfully.'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'country_of_incorporation_id' => 'nullable|exists:countries,id',
            'incorporation_date' => 'nullable|date',
            'dissolution_date' => 'nullable|date',
            'legal_form' => 'nullable|string|max:100',
            'corporate_status' => 'nullable|in:active,dissolved,suspended,dormant,struck_off',
            'registered_address' => 'nullable|string',
            'is_sanctioned' => 'boolean',
            'sanction_details' => 'nullable|array',
            'verification_status' => 'nullable|in:unverified,verified,disputed,flagged_suspicious',
            'source_record_id' => 'nullable|exists:source_records,id',
        ]);

        $old = $company->toArray();
        $company->update($validated);
        AuditLog::log('update', $company, $old, $validated);

        return response()->json(['data' => $company, 'message' => 'Company updated successfully.']);
    }

    public function destroy(string $id): JsonResponse
    {
        $company = Company::findOrFail($id);
        $company->delete();
        AuditLog::log('delete', $company);

        return response()->json(['message' => 'Company soft deleted successfully.']);
    }

    public function networkGraph(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:company,vessel',
            'id' => 'required|uuid',
        ]);

        $graph = $this->corporateService->getNetworkGraph($validated['type'], $validated['id']);

        return response()->json($graph);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\PersonAlias;
use App\Models\PersonCorporateTie;
use App\Models\PersonVesselTie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Person::with(['nationalityCountry', 'aliases'])
            ->withCount(['corporateTies', 'vesselTies', 'crewEmployments', 'labourReports']);

        if ($role = $request->input('primary_role')) {
            $query->where('primary_role', $role);
        }

        if ($status = $request->input('verification_status')) {
            $query->where('verification_status', $status);
        }

        if ($countryId = $request->input('nationality_country_id')) {
            $query->where('nationality_country_id', $countryId);
        }

        if ($request->has('is_sanctioned')) {
            $query->where('is_sanctioned', filter_var($request->is_sanctioned, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_pep')) {
            $query->where('is_pep', filter_var($request->is_pep, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                  ->orWhere('masked_identifier', 'ILIKE', "%{$search}%")
                  ->orWhereHas('aliases', function ($aq) use ($search) {
                      $aq->where('alias_name', 'ILIKE', "%{$search}%");
                  });
            });
        }

        $persons = $query->orderBy('full_name')->paginate($request->input('per_page', 25));

        // Redact sensitive details for non-authorized roles
        $user = $request->user();
        $roleValue = $user?->role instanceof \BackedEnum ? $user->role->value : ($user?->role ?? '');
        $canViewPii = in_array($roleValue, ['super_admin', 'admin', 'research_director', 'senior_investigator', 'investigator']);

        $persons->getCollection()->transform(function ($person) use ($canViewPii) {
            if (!$canViewPii) {
                $person->encrypted_national_id = null;
                $person->encrypted_passport_number = null;
                $person->encrypted_seaman_book_number = null;
                $person->encrypted_tax_id = null;
                $person->is_redacted = true;
            }
            return $person;
        });

        return response()->json($persons);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $person = Person::with([
            'nationalityCountry',
            'aliases',
            'identifiers.issuingCountry',
            'corporateTies.company',
            'vesselTies.vessel',
            'crewEmployments.vessel',
            'crewEmployments.manningAgent',
            'labourReports',
            'dataSource',
        ])
        ->withCount(['corporateTies', 'vesselTies', 'crewEmployments', 'labourReports'])
        ->findOrFail($id);

        $user = $request->user();
        $roleValue = $user?->role instanceof \BackedEnum ? $user->role->value : ($user?->role ?? '');
        $canViewPii = in_array($roleValue, ['super_admin', 'admin', 'research_director', 'senior_investigator', 'investigator']);

        if (!$canViewPii) {
            $person->encrypted_national_id = null;
            $person->encrypted_passport_number = null;
            $person->encrypted_seaman_book_number = null;
            $person->encrypted_tax_id = null;
            $person->is_redacted = true;

            // Mask identifiers
            foreach ($person->identifiers as $ident) {
                $ident->encrypted_value = null;
            }
        }

        AuditLog::log('view_person_profile', $person, null, ['viewed_pii' => $canViewPii]);

        return response()->json(['data' => $person]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'primary_role' => 'required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality_country_id' => 'nullable|exists:countries,id',
            'gender' => 'nullable|string|max:20',
            'national_id_number' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:100',
            'seaman_book_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'is_pep' => 'boolean',
            'is_sanctioned' => 'boolean',
            'sanction_details' => 'nullable|array',
            'data_source_id' => 'nullable|exists:data_sources,id',
            'notes' => 'nullable|string',
        ]);

        // Generate masked identifier
        $masked = null;
        if (!empty($validated['passport_number'])) {
            $masked = 'PPT-' . substr($validated['passport_number'], 0, 2) . '****' . substr($validated['passport_number'], -2);
        } elseif (!empty($validated['national_id_number'])) {
            $masked = 'ID-****' . substr($validated['national_id_number'], -4);
        } elseif (!empty($validated['seaman_book_number'])) {
            $masked = 'SB-****' . substr($validated['seaman_book_number'], -4);
        }

        $person = Person::create([
            'full_name' => $validated['full_name'],
            'primary_role' => $validated['primary_role'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'nationality_country_id' => $validated['nationality_country_id'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'encrypted_national_id' => $validated['national_id_number'] ?? null,
            'encrypted_passport_number' => $validated['passport_number'] ?? null,
            'encrypted_seaman_book_number' => $validated['seaman_book_number'] ?? null,
            'encrypted_tax_id' => $validated['tax_id'] ?? null,
            'masked_identifier' => $masked,
            'is_pep' => $validated['is_pep'] ?? false,
            'is_sanctioned' => $validated['is_sanctioned'] ?? false,
            'sanction_details' => $validated['sanction_details'] ?? null,
            'verification_status' => 'unverified',
            'data_source_id' => $validated['data_source_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log('create_person', $person, null, ['full_name' => $person->full_name]);

        return response()->json([
            'data' => $person,
            'message' => 'Person profile registered successfully.',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'primary_role' => 'sometimes|required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality_country_id' => 'nullable|exists:countries,id',
            'gender' => 'nullable|string|max:20',
            'is_pep' => 'boolean',
            'is_sanctioned' => 'boolean',
            'verification_status' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $old = $person->only(array_keys($validated));
        $person->update($validated);

        AuditLog::log('update_person', $person, $old, $validated);

        return response()->json([
            'data' => $person,
            'message' => 'Person profile updated successfully.',
        ]);
    }

    public function addAlias(Request $request, string $id): JsonResponse
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'alias_name' => 'required|string|max:255',
            'alias_type' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $alias = $person->aliases()->create($validated);
        AuditLog::log('add_person_alias', $person, null, $validated);

        return response()->json([
            'data' => $alias,
            'message' => 'Alias added to person profile.',
        ], 201);
    }

    public function addCorporateTie(Request $request, string $id): JsonResponse
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'role' => 'required|string|max:100',
            'shares_percentage' => 'nullable|numeric|between:0,100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
            'is_current' => 'boolean',
            'verification_status' => 'nullable|string',
        ]);

        $tie = $person->corporateTies()->create($validated);
        AuditLog::log('add_person_corporate_tie', $person, null, $validated);

        return response()->json([
            'data' => $tie,
            'message' => 'Corporate relationship linked.',
        ], 201);
    }

    public function addVesselTie(Request $request, string $id): JsonResponse
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'vessel_id' => 'required|exists:vessels,id',
            'role' => 'required|string|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
            'license_number' => 'nullable|string|max:100',
            'is_current' => 'boolean',
            'verification_status' => 'nullable|string',
        ]);

        $tie = $person->vesselTies()->create($validated);
        AuditLog::log('add_person_vessel_tie', $person, null, $validated);

        return response()->json([
            'data' => $tie,
            'message' => 'Vessel relationship linked.',
        ], 201);
    }
}

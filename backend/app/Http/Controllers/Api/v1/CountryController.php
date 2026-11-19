<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Country::query()
            ->withCount(['regulations', 'datasets', 'maritimeJurisdictions', 'vessels']);

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('iso_code_2', 'ILIKE', "%{$search}%")
                  ->orWhere('iso_code_3', 'ILIKE', "%{$search}%")
                  ->orWhere('official_name', 'ILIKE', "%{$search}%");
            });
        }

        $countries = $query->orderBy('name')->paginate($request->input('per_page', 25));

        return response()->json($countries);
    }

    public function show(string $id): JsonResponse
    {
        $country = Country::with(['regulations.sourceRecord', 'datasets.dataSource', 'maritimeJurisdictions'])
            ->withCount(['vessels', 'companies'])
            ->findOrFail($id);

        return response()->json(['data' => $country]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'iso_code_2' => 'required|string|size:2|unique:countries,iso_code_2',
            'iso_code_3' => 'required|string|size:3|unique:countries,iso_code_3',
            'numeric_code' => 'nullable|string|max:3',
            'name' => 'required|string|max:255',
            'official_name' => 'nullable|string|max:255',
            'flag_icon_url' => 'nullable|url',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $country = Country::create($validated);
        AuditLog::log('create', $country, null, $validated);

        return response()->json(['data' => $country, 'message' => 'Country created successfully.'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $country = Country::findOrFail($id);

        $validated = $request->validate([
            'numeric_code' => 'nullable|string|max:3',
            'name' => 'sometimes|required|string|max:255',
            'official_name' => 'nullable|string|max:255',
            'flag_icon_url' => 'nullable|url',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $old = $country->toArray();
        $country->update($validated);
        AuditLog::log('update', $country, $old, $validated);

        return response()->json(['data' => $country, 'message' => 'Country updated successfully.']);
    }

    public function regulations(string $id): JsonResponse
    {
        $country = Country::findOrFail($id);
        $regulations = $country->regulations()->with('sourceRecord')->orderBy('effective_date', 'desc')->get();

        return response()->json(['data' => $regulations]);
    }

    public function datasets(string $id): JsonResponse
    {
        $country = Country::findOrFail($id);
        $datasets = $country->datasets()->with('dataSource')->orderBy('dataset_name')->get();

        return response()->json(['data' => $datasets]);
    }
}

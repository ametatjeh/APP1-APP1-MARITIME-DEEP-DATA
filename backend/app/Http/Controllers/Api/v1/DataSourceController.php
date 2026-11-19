<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\SourceCategory;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class DataSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DataSource::with(['country'])
            ->withCount(['sourceRecords', 'evidences']);

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($countryId = $request->input('country_id')) {
            $query->where('country_id', $countryId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('publisher', 'ILIKE', "%{$search}%");
            });
        }

        $sources = $query->orderByDesc('reliability_score')->paginate($request->input('per_page', 25));

        return response()->json($sources);
    }

    public function show(string $id): JsonResponse
    {
        $source = DataSource::with([
            'country',
            'sourceRecords' => function ($q) {
                $q->orderByDesc('retrieval_date')->limit(20);
            },
            'evidences' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            },
        ])
        ->withCount(['sourceRecords', 'evidences'])
        ->findOrFail($id);

        return response()->json(['data' => $source]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:data_sources,code',
            'category' => ['required', new Enum(SourceCategory::class)],
            'organization_type' => 'nullable|string|max:100',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'reliability_score' => 'required|numeric|between:0.00,1.00',
            'source_url' => 'nullable|url',
            'description' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        if (empty($validated['organization_type'])) {
            $cat = $validated['category'] instanceof SourceCategory ? $validated['category']->value : $validated['category'];
            $validated['organization_type'] = $cat ?? 'ngo';
        }

        $source = DataSource::create($validated);
        AuditLog::log('create_data_source', $source, null, $validated);

        return response()->json(['data' => $source, 'message' => 'Data source registered in registry.'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $source = DataSource::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => ['sometimes', new Enum(SourceCategory::class)],
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'reliability_score' => 'sometimes|numeric|between:0.00,1.00',
            'source_url' => 'nullable|url',
            'description' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $old = $source->toArray();
        $source->update($validated);
        AuditLog::log('update_data_source', $source, $old, $validated);

        return response()->json(['data' => $source, 'message' => 'Data source metadata updated.']);
    }
}

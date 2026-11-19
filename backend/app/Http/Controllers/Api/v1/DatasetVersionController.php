<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\DatasetStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DatasetVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DatasetVersionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DatasetVersion::with([
            'dataSource:id,name,category,publisher',
            'country:id,name,iso_code_3',
            'creator:id,name',
        ])->withCount(['imports']);

        if ($sourceId = $request->input('data_source_id')) {
            $query->where('data_source_id', $sourceId);
        }

        if ($countryId = $request->input('country_id')) {
            $query->where('country_id', $countryId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('current_only')) {
            $query->where('is_current', true);
        }

        $versions = $query->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json($versions);
    }

    public function show(string $id): JsonResponse
    {
        $version = DatasetVersion::with([
            'dataSource',
            'country',
            'creator:id,name',
            'imports' => function ($q) {
                $q->orderByDesc('created_at')->limit(15);
            },
        ])->withCount(['imports'])->findOrFail($id);

        return response()->json(['data' => $version]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_source_id' => 'required|exists:data_sources,id',
            'country_id' => 'nullable|exists:countries,id',
            'dataset_name' => 'required|string|max:255',
            'version_tag' => 'required|string|max:50',
            'publication_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'deprecation_date' => 'nullable|date',
            'is_current' => 'nullable|boolean',
            'snapshot_sha256' => 'nullable|string|max:64',
            'schema_definition' => 'nullable|array',
            'changelog' => 'nullable|string',
            'status' => ['nullable', Rule::enum(DatasetStatus::class)],
        ]);

        $isCurrent = $validated['is_current'] ?? true;

        if ($isCurrent) {
            DatasetVersion::where('data_source_id', $validated['data_source_id'])
                ->where('dataset_name', $validated['dataset_name'])
                ->update(['is_current' => false]);
        }

        $sha256 = $validated['snapshot_sha256'] ?? null;
        if (empty($sha256)) {
            $contentToHash = json_encode([
                'source' => $validated['data_source_id'],
                'name' => $validated['dataset_name'],
                'tag' => $validated['version_tag'],
                'schema' => $validated['schema_definition'] ?? [],
                'time' => now()->toIso8601String(),
            ]);
            $sha256 = hash('sha256', $contentToHash);
        }

        $version = DatasetVersion::create([
            'data_source_id' => $validated['data_source_id'],
            'country_id' => $validated['country_id'] ?? null,
            'dataset_name' => $validated['dataset_name'],
            'version_tag' => $validated['version_tag'],
            'publication_date' => $validated['publication_date'] ?? null,
            'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
            'deprecation_date' => $validated['deprecation_date'] ?? null,
            'is_current' => $isCurrent,
            'snapshot_sha256' => $sha256,
            'records_count' => 0,
            'schema_definition' => $validated['schema_definition'] ?? null,
            'changelog' => $validated['changelog'] ?? 'Initial version registration.',
            'status' => $validated['status'] ?? DatasetStatus::ACTIVE,
            'created_by' => $request->user()?->id,
        ]);

        AuditLog::log('create_dataset_version', $version, null, [
            'dataset_name' => $version->dataset_name,
            'version_tag' => $version->version_tag,
            'snapshot_sha256' => $version->snapshot_sha256,
        ]);

        return response()->json([
            'data' => $version->load(['dataSource', 'country']),
            'message' => 'Dataset version registered successfully with provenance hash.',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $version = DatasetVersion::findOrFail($id);

        $validated = $request->validate([
            'deprecation_date' => 'nullable|date',
            'is_current' => 'nullable|boolean',
            'changelog' => 'nullable|string',
            'status' => ['nullable', Rule::enum(DatasetStatus::class)],
            'schema_definition' => 'nullable|array',
        ]);

        $old = $version->toArray();

        if (isset($validated['is_current']) && $validated['is_current'] && !$version->is_current) {
            DatasetVersion::where('data_source_id', $version->data_source_id)
                ->where('dataset_name', $version->dataset_name)
                ->where('id', '!=', $version->id)
                ->update(['is_current' => false]);
        }

        $version->update($validated);

        AuditLog::log('update_dataset_version', $version, $old, $validated);

        return response()->json([
            'data' => $version->load(['dataSource', 'country']),
            'message' => 'Dataset version updated successfully.',
        ]);
    }
}

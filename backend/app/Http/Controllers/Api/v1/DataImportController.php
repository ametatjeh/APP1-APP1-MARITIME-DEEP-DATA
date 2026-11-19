<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataImport;
use App\Models\DataSource;
use App\Models\DatasetVersion;
use App\Models\ImportQuarantinedRecord;
use App\Services\DataImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function __construct(
        protected DataImportService $importService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DataImport::with(['dataSource', 'datasetVersion', 'country', 'creator'])
            ->withCount(['quarantinedRecords']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($target = $request->input('target_entity_type')) {
            $query->where('target_entity_type', $target);
        }

        if ($sourceId = $request->input('data_source_id')) {
            $query->where('data_source_id', $sourceId);
        }

        $imports = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        return response()->json($imports);
    }

    public function show(string $id): JsonResponse
    {
        $import = DataImport::with([
            'dataSource',
            'datasetVersion',
            'country',
            'creator',
            'quarantinedRecords' => function ($q) {
                $q->limit(20);
            },
        ])
        ->withCount(['quarantinedRecords'])
        ->findOrFail($id);

        return response()->json(['data' => $import]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_source_id' => 'required|exists:data_sources,id',
            'dataset_version_id' => 'nullable|exists:dataset_versions,id',
            'country_id' => 'nullable|exists:countries,id',
            'target_entity_type' => 'required|string|in:vessel,company,person,iuu_listing',
            'rows' => 'required_without:file|array',
            'file' => 'nullable|file|mimes:csv,txt,json|max:51200', // max 50MB
            'filename' => 'nullable|string|max:255',
            'validation_rules' => 'nullable|array',
        ]);

        $source = DataSource::findOrFail($validated['data_source_id']);
        $version = !empty($validated['dataset_version_id']) ? DatasetVersion::find($validated['dataset_version_id']) : null;

        $rows = [];
        $filename = $validated['filename'] ?? 'api_batch_payload.json';
        $format = 'json';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            $format = $ext === 'json' ? 'json' : 'csv';

            if ($format === 'json') {
                $content = file_get_contents($file->getRealPath());
                $rows = json_decode($content, true) ?: [];
            } else {
                // Parse CSV
                $csvData = array_map('str_getcsv', file($file->getRealPath()));
                if (count($csvData) > 1) {
                    $headers = array_map('trim', array_shift($csvData));
                    foreach ($csvData as $csvRow) {
                        if (count($csvRow) === count($headers)) {
                            $rows[] = array_combine($headers, $csvRow);
                        }
                    }
                }
            }
        } else {
            $rows = $validated['rows'] ?? [];
        }

        if (count($rows) === 0) {
            return response()->json(['message' => 'No valid records found in payload or uploaded file.'], 422);
        }

        $import = $this->importService->processBatch(
            source: $source,
            filename: $filename,
            fileFormat: $format,
            targetEntityType: $validated['target_entity_type'],
            rows: $rows,
            datasetVersion: $version,
            countryId: $validated['country_id'] ?? null,
            user: $request->user(),
            validationRules: $validated['validation_rules'] ?? []
        );

        return response()->json([
            'data' => $import,
            'message' => 'Import pipeline executed successfully.',
        ], 201);
    }

    public function quarantine(Request $request, string $id): JsonResponse
    {
        $import = DataImport::findOrFail($id);

        $query = $import->quarantinedRecords()->with('reviewer:id,name');

        if ($reason = $request->input('quarantine_reason')) {
            $query->where('quarantine_reason', $reason);
        }

        if ($status = $request->input('resolution_status')) {
            $query->where('resolution_status', $status);
        }

        $records = $query->orderBy('row_index')->paginate($request->input('per_page', 25));

        return response()->json($records);
    }

    public function resolveQuarantine(Request $request, string $id, string $quarantineId): JsonResponse
    {
        $import = DataImport::findOrFail($id);
        $record = $import->quarantinedRecords()->findOrFail($quarantineId);

        $validated = $request->validate([
            'decision' => 'required|string|in:discard,correct_and_import',
            'corrected_payload' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $resolved = $this->importService->resolveQuarantineRecord(
            record: $record,
            decision: $validated['decision'],
            correctedPayload: $validated['corrected_payload'] ?? null,
            notes: $validated['notes'] ?? null,
            user: $request->user()
        );

        return response()->json([
            'data' => $resolved,
            'message' => 'Quarantine entry resolved successfully.',
        ]);
    }
}

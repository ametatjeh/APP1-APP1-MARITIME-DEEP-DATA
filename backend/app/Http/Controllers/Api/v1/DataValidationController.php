<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\DataValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataValidationController extends Controller
{
    public function __construct(
        protected DataValidationService $validator
    ) {}

    /**
     * Real-time single identifier syntax & checksum validation
     */
    public function validateIdentifier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:imo,mmsi,callsign',
            'value' => 'required|string',
        ]);

        $type = $validated['type'];
        $val = $validated['value'];

        $result = match ($type) {
            'imo' => $this->validator->validateImo($val),
            'mmsi' => $this->validator->validateMmsi($val),
            'callsign' => $this->validator->validateCallsign($val),
        };

        return response()->json([
            'type' => $type,
            'input' => $val,
            'result' => $result,
        ]);
    }

    /**
     * Pre-flight batch dry-run validation without writing to DB
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_entity_type' => 'required|string|in:vessel,company,person,iuu_listing',
            'rows' => 'required_without:file|array',
            'file' => 'nullable|file|mimes:csv,txt,json|max:20480', // max 20MB
            'validation_rules' => 'nullable|array',
        ]);

        $rows = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'json') {
                $rows = json_decode(file_get_contents($file->getRealPath()), true) ?: [];
            } else {
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

        $rules = $validated['validation_rules'] ?? [];
        $targetType = $validated['target_entity_type'];

        $validCount = 0;
        $invalidCount = 0;
        $reasonsSummary = [];
        $sampleValid = [];
        $sampleQuarantined = [];

        foreach ($rows as $index => $row) {
            $check = $this->validator->validateRow($row, $targetType, $rules);

            if ($check['is_valid']) {
                $validCount++;
                if (count($sampleValid) < 5) {
                    $sampleValid[] = [
                        'row_index' => $index + 1,
                        'data' => $row,
                    ];
                }
            } else {
                $invalidCount++;
                $errorMsg = $check['error_message'];
                $reasonsSummary[$errorMsg] = ($reasonsSummary[$errorMsg] ?? 0) + 1;

                if (count($sampleQuarantined) < 10) {
                    $sampleQuarantined[] = [
                        'row_index' => $index + 1,
                        'data' => $row,
                        'errors' => $check['errors'],
                        'error_message' => $errorMsg,
                    ];
                }
            }
        }

        return response()->json([
            'summary' => [
                'total_analyzed' => count($rows),
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'valid_percentage' => count($rows) > 0 ? round(($validCount / count($rows)) * 100, 2) : 0,
            ],
            'reasons_summary' => $reasonsSummary,
            'sample_valid' => $sampleValid,
            'sample_quarantined' => $sampleQuarantined,
        ]);
    }
}

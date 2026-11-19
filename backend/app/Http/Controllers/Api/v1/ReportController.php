<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ResearchReport;
use App\Models\Vessel;
use App\Services\DossierReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected DossierReportService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ResearchReport::with(['creator:id,name', 'investigationCase:id,title,case_reference'])
            ->withCount(['citations']);

        if ($type = $request->input('report_type')) {
            $query->where('report_type', $type);
        }

        if ($classification = $request->input('classification')) {
            $query->where('classification', $classification);
        }

        $reports = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        return response()->json($reports);
    }

    public function show(string $id): JsonResponse
    {
        $report = ResearchReport::with([
            'creator:id,name',
            'investigationCase',
            'citations.evidence',
            'citations.sourceRecord',
        ])
        ->withCount(['citations'])
        ->findOrFail($id);

        $res = $report->toArray();
        $res['target_entity'] = $report->target_entity;

        return response()->json(['data' => $res]);
    }

    public function generateVesselDossier(Request $request, string $vesselId): JsonResponse
    {
        $vessel = Vessel::findOrFail($vesselId);

        $report = $this->service->generateVesselDossier(
            vessel: $vessel,
            user: $request->user(),
            options: $request->only(['classification'])
        );

        return response()->json([
            'data' => $report->load('citations'),
            'message' => 'Evidence-linked vessel dossier compiled successfully.',
        ], 201);
    }

    public function generateCorporateDossier(Request $request, string $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        $report = $this->service->generateCorporateDossier(
            company: $company,
            user: $request->user(),
            options: $request->only(['classification'])
        );

        return response()->json([
            'data' => $report,
            'message' => 'Corporate intelligence report compiled successfully.',
        ], 201);
    }

    public function export(string $id): JsonResponse
    {
        $report = ResearchReport::with(['citations.evidence', 'citations.sourceRecord'])->findOrFail($id);

        return response()->json([
            'report_reference' => $report->report_reference,
            'title' => $report->title,
            'report_type' => $report->report_type,
            'classification' => $report->classification,
            'sha256_checksum' => $report->sha256_checksum,
            'executive_summary' => $report->executive_summary,
            'markdown' => $report->body_markdown,
            'snapshot' => $report->structured_payload,
            'citations' => $report->citations,
            'exported_at' => now()->toIso8601String(),
        ]);
    }
}

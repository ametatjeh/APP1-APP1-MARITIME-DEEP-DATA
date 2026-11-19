<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\EvidenceClassification;
use App\Enums\EvidenceType;
use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Policies\EvidencePolicy;
use App\Services\EvidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function __construct(
        protected EvidenceService $evidenceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $policy = new EvidencePolicy();
        if (!$policy->viewAny($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = Evidence::with(['case', 'dataSource', 'collectedByUser'])
            ->withCount(['relationships', 'custodyLogs']);

        if ($caseId = $request->input('case_id')) {
            $query->where('case_id', $caseId);
        }

        if ($type = $request->input('evidence_type')) {
            $query->where('evidence_type', $type);
        }

        if ($status = $request->input('verification_status')) {
            $query->where('verification_status', $status);
        }

        if ($classification = $request->input('classification')) {
            $query->where('classification', $classification);
        }

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        $evidences = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        return response()->json($evidences);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $evidence = Evidence::with([
            'case.leadInvestigator',
            'dataSource',
            'sourceRecord',
            'collectedByUser',
            'relationships',
            'custodyLogs.actor',
            'verificationReviews.reviewer',
        ])->findOrFail($id);

        $policy = new EvidencePolicy();
        if (!$policy->view($request->user(), $evidence)) {
            return response()->json(['message' => 'Forbidden. Access to this classified evidence is restricted.'], 403);
        }

        // Include current cryptographic integrity verification check
        $integrityVerified = $evidence->verifyFileIntegrity();

        return response()->json([
            'data' => $evidence,
            'cryptographic_integrity_verified' => $integrityVerified,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $policy = new EvidencePolicy();
        if (!$policy->create($request->user())) {
            return response()->json(['message' => 'Forbidden. Insufficient permissions to upload evidence.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,png,jpg,jpeg,tiff,geojson,json,csv,xlsx',
            'title' => 'required|string|max:255',
            'evidence_type' => ['required', new Enum(EvidenceType::class)],
            'classification' => ['nullable', new Enum(EvidenceClassification::class)],
            'case_id' => 'nullable|exists:investigation_cases,id',
            'data_source_id' => 'nullable|exists:data_sources,id',
            'source_url' => 'nullable|url',
            'collection_date' => 'nullable|date',
            'publication_date' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $evidence = $this->evidenceService->storeEvidence(
            $request->file('file'),
            $request->only([
                'title',
                'evidence_type',
                'classification',
                'case_id',
                'data_source_id',
                'source_url',
                'collection_date',
                'publication_date',
                'metadata',
            ]),
            $request->user()
        );

        return response()->json([
            'data' => $evidence,
            'sha256_hash' => $evidence->sha256_hash,
            'message' => 'Evidence securely vaulted and indexed with SHA-256 checksum.',
        ], 201);
    }

    public function download(Request $request, string $id): StreamedResponse|JsonResponse
    {
        $evidence = Evidence::findOrFail($id);
        $policy = new EvidencePolicy();
        if (!$policy->download($request->user(), $evidence)) {
            return response()->json(['message' => 'Forbidden. Not cleared to download this evidence file.'], 403);
        }

        return $this->evidenceService->downloadEvidence($evidence, $request->user());
    }

    public function linkRelationship(Request $request, string $id): JsonResponse
    {
        $evidence = Evidence::findOrFail($id);
        $policy = new EvidencePolicy();
        if (!$policy->create($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'target_type' => 'required|in:vessel,company,case_subject,case,evidence',
            'target_id' => 'required|uuid',
            'relationship_type' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $rel = $this->evidenceService->linkRelationship(
            $evidence,
            $validated['target_type'],
            $validated['target_id'],
            $validated['relationship_type'],
            $validated['notes'] ?? null
        );

        return response()->json(['data' => $rel, 'message' => 'Evidence linked to entity.']);
    }

    public function custodyLog(Request $request, string $id): JsonResponse
    {
        $evidence = Evidence::findOrFail($id);
        $policy = new EvidencePolicy();
        if (!$policy->view($request->user(), $evidence)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $custody = $evidence->custodyLogs()->with('actor')->get();

        return response()->json(['data' => $custody]);
    }
}
